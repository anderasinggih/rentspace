/**
 * Pembantu untuk balasan chat yang terasa seperti orang, bukan bot.
 *
 * Semua fungsi di sini murni (tidak menyentuh WhatsApp) supaya bisa diuji
 * tanpa perlu koneksi ke server WhatsApp.
 */

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const randomBetween = (min, max) => min + Math.floor(Math.random() * Math.max(1, max - min + 1));

/**
 * Jeda default sebelum AI dipanggil.
 *
 * Sifatnya "tunggu sampai customer selesai ngetik": dihitung ulang tiap ada
 * pesan baru, bukan timer yang jalan terus. Hard cap-nya mencegah chat yang
 * ngetik terus tidak pernah dibalas.
 */
const TIMING = {
    quietMinMs: 5000,     // jeda tenang setelah pesan terakhir
    quietMaxMs: 12000,    // (acak per burst, jadi tidak selalu sama)
    ackMinMs: 1000,       // burst yang isinya cuma "ok/makasih" tetap cepet
    ackMaxMs: 2000,
    maxWaitMs: 25000,     // batas keras sejak pesan pertama yang belum terjawab
};

/** Batas panjang balasan sebelum dipecah jadi beberapa bubble. */
const BUBBLES = {
    singleMax: 220,       // di bawah ini cukup dikirim sebagai satu bubble
    chunkMax: 400,        // panjang maksimum tiap pecahan
    maxBubbles: 3,
    gapMinMs: 1500,       // jeda acak antar-bubble
    gapMaxMs: 4500,
};

// Sapaan singkat yang bukan pertanyaan baru: jawaban yang sedang jalan tetap dikirim.
// Dicek per kata (bukan regex utuh) supaya "ok makasih", "terima kasih ya kak",
// dan emoji doang tetap kena, sementara "kak ip 13 ready?" tetap dianggap pertanyaan.
const ACK_WORDS = new Set([
    'ok', 'oke', 'okay', 'sip', 'sipp', 'siap', 'ya', 'iya', 'yes', 'nah', 'good', 'mantap',
    'mksh', 'makasih', 'terima', 'kasih', 'terimaksih', 'thanks', 'thank', 'you', 'banyak',
    'ntar', 'tunggu', 'wait', 'haha', 'hihi', 'hehe', 'wkwk', 'betul', 'benar',
    'kak', 'kakak', 'sih', 'dong', 'dear',
]);

const isAck = (text) => {
    const raw = String(text || '').trim();
    if (!raw || raw.length > 40) return false;
    const words = raw.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim().split(/\s+/).filter(Boolean);
    if (!words.length) return true;   // cuma emoji, mis. "🙏"
    if (words.length > 5) return false;
    return words.every((w) => ACK_WORDS.has(w));
};

/**
 * Sapaan yang isinya cuma basa-basi, tanpa pertanyaan ("halo kak", "selamat
 * pagi", "permisi").
 *
 * Yang penting: "halo kak ip 13 ready?" TIDAK boleh kena, soalnya itu
 * pertanyaan sungguhan. Karena itu hanya pesan pendek yang semua kata-katanya
 * berasal dari daftar sapaan yang lolos.
 */
const GREETING_WORDS = new Set([
    'halo', 'hai', 'hi', 'hello', 'hey', 'permisi', 'pagi', 'siang', 'sore',
    'malam', 'salam', 'assalamualaikum', 'waalaikumsalam', 'selamat', 'datang',
    'mohon', 'maaf', 'admin', 'min', 'kak', 'kakak', 'bang', 'mas', 'bro',
]);

const isGreetingOnly = (text) => {
    const raw = String(text || '').trim().toLowerCase();
    if (!raw || raw.length > 24) return false;
    if (/\d/.test(raw)) return false;      // ada angka = kemungkinan pertanyaan
    if (/[?？]/.test(raw)) return false;   // ada tanda tanya = pertanyaan

    const words = raw.replace(/[^\p{L}\s]+/gu, ' ').trim().split(/\s+/).filter(Boolean);
    if (!words.length || words.length > 3) return false;

    return words.every((w) => GREETING_WORDS.has(w));
};

/**
 * Gabung beberapa pesan beruntun jadi satu pertanyaan.
 *
 * Kebanyakan orang yang ngetik cepat mengirim potongan ("ip 12" lalu "yang pro
 * max" lalu "harga berapa?"), jadi lebih baik dirangkai utuh daripada diambil
 * satu. Kalau gabungannya sudah panjang, pakai pesan terakhir yang memang
 * pertanyaannya supaya tidak dijawab dua kali.
 */
function mergeBatchText(batch, maxLen = 160) {
    if (batch.length === 1) return batch[0].text;

    const joined = batch.map((m) => m.text).join(' ');
    if (joined.length <= maxLen) return joined;

    for (let i = batch.length - 1; i >= 0; i--) {
        if (/[?？]\s*$/.test(batch[i].text) || /\b(kapan|berapa|harga|mana|ready|ada|tersedia|boleh|bisa)\b/i.test(batch[i].text)) {
            return batch[i].text;
        }
    }
    return joined;
}

/**
 * Pecah balasan panjang jadi beberapa bubble.
 *
 * Urutan pemotongan: paragraf (baris kosong) → baris → kalimat. Isi teks
 * tidak pernah dibuang: kalau pecakannya melebihi maxBubbles, sisanya
 * digabung ke bubble terakhir supaya bot tidak jadi machine gun sekaligus
 * tidak memotong jawaban.
 */
function splitForChat(text, opts = {}) {
    const singleMax = opts.singleMax ?? BUBBLES.singleMax;
    const chunkMax = opts.chunkMax ?? BUBBLES.chunkMax;
    const maxBubbles = opts.maxBubbles ?? BUBBLES.maxBubbles;

    const clean = String(text || '').trim();
    if (!clean) return [];
    if (clean.length <= singleMax) return [clean];

    let parts = clean.split(/\n{2,}/).map((s) => s.trim()).filter(Boolean);
    if (parts.length === 1) {
        parts = clean.split(/\n+/).map((s) => s.trim()).filter(Boolean);
    }

    const out = [];
    for (const part of parts) {
        if (part.length <= chunkMax) {
            out.push(part);
            continue;
        }

        let current = '';
        for (const sentence of part.split(/(?<=[.!?])\s+/)) {
            if (current && (current + ' ' + sentence).length > chunkMax) {
                out.push(current.trim());
                current = sentence;
            } else {
                current = current ? current + ' ' + sentence : sentence;
            }
        }
        if (current.trim()) out.push(current.trim());
    }

    if (out.length <= maxBubbles) return out;

    // Sisanya digabung ke bubble terakhir, bukan dibuang.
    return [...out.slice(0, maxBubbles - 1), out.slice(maxBubbles - 1).join(' ')];
}

/**
 * Tunggu sampai customer berhenti ngetik (quiet period).
 *
 * Kalau ada pesan baru selama menunggu, jeda dihitung ulang dari nol. Itulah
 * yang bikin "ip 12" + "yang pro max" + "berapa?" digabung jadi satu
 * pertanyaan, bukan tiga balasan terpisah. `maxWaitMs` sebagai pagu supaya chat
 * yang ngetik terus tidak menggantung tanpa balasan.
 *
 * @param {{pending: Array}} state        antrean chat (hanya butuh .pending)
 * @param {object}  [opts]
 * @param {boolean} [opts.quick]          perintah admin, tidak perlu jeda lama
 * @param {object}  [opts.timing]
 * @param {Function} [opts.now]           injeksi jam untuk tes
 * @param {Function} [opts.sleep]
 * @returns {Promise<{waitedMs: number, capped: boolean, resets: number}>}
 */
async function waitForQuietPeriod(state, opts = {}) {
    const timing = opts.timing ?? TIMING;
    const now = opts.now ?? Date.now;
    const doSleep = opts.sleep ?? sleep;
    const quick = !!opts.quick;

    const startedAt = now();
    let seen = state.pending.length;
    let resets = 0;

    for (;;) {
        const elapsed = now() - startedAt;
        if (elapsed >= timing.maxWaitMs) {
            return { waitedMs: elapsed, capped: true, resets };
        }

        const onlyAck = state.pending.length > 0 && state.pending.every((m) => isAck(m.text));
        const wait = (quick || onlyAck)
            ? randomBetween(timing.ackMinMs, timing.ackMaxMs)
            : randomBetween(timing.quietMinMs, timing.quietMaxMs);

        await doSleep(Math.max(0, Math.min(wait, timing.maxWaitMs - elapsed)));

        if (state.pending.length === seen) {
            return { waitedMs: now() - startedAt, capped: false, resets };
        }

        seen = state.pending.length;
        resets++;
    }
}

module.exports = {
    ACK_WORDS,
    BUBBLES,
    TIMING,
    isAck,
    isGreetingOnly,
    mergeBatchText,
    randomBetween,
    sleep,
    splitForChat,
    waitForQuietPeriod,
};
