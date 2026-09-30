const test = require('node:test');
const assert = require('node:assert/strict');

const {
    isAck,
    isGreetingOnly,
    mergeBatchText,
    splitForChat,
    waitForQuietPeriod,
} = require('../lib/chat-humanizer');

test('isGreetingOnly: sapaan basa-basi kena, pertanyaan tidak', () => {
    assert.equal(isGreetingOnly('halo kak'), true);
    assert.equal(isGreetingOnly('selamat pagi'), true);
    assert.equal(isGreetingOnly('permisi'), true);

    assert.equal(isGreetingOnly('halo kak ip 13 ready?'), false);
    assert.equal(isGreetingOnly('halo kak ip 13 ready'), false);
    assert.equal(isGreetingOnly('halo, ada apa saja yang ready?'), false);
    assert.equal(isGreetingOnly(''), false);
});

test('isAck: sapaan singkat dianggap ack, pertanyaan asli tidak', () => {
    assert.equal(isAck('ok'), true);
    assert.equal(isAck('ok makasih'), true);
    assert.equal(isAck('terima kasih ya kak'), true);
    assert.equal(isAck('🙏'), true);

    assert.equal(isAck('ip 12 ready kapan?'), false);
    assert.equal(isAck('berapa harga iphone 13'), false);
    assert.equal(isAck(''), false);
});

test('mergeBatchText: tiga bubble jadi satu pertanyaan utuh', () => {
    const merged = mergeBatchText([
        { text: 'ip 12' },
        { text: 'yang pro max' },
        { text: 'berapa?' },
    ]);
    assert.equal(merged, 'ip 12 yang pro max berapa?');
});

test('mergeBatchText: gabungan kepanjangan jatuh ke baris yang memang berupa pertanyaan', () => {
    const long = 'x'.repeat(200);
    const merged = mergeBatchText([
        { text: long },
        { text: 'ip 13 ready kapan?' },
    ]);
    assert.equal(merged, 'ip 13 ready kapan?');
});

test('splitForChat: balasan pendek tetap satu bubble', () => {
    assert.deepEqual(splitForChat('ip 12 ready kak'), ['ip 12 ready kak']);
    assert.deepEqual(splitForChat('  '), []);
});

test('splitForChat: balasan panjang dipecah, tiap pecakan di bawah batas', () => {
    const text = Array.from({ length: 6 }, (_, i) =>
        `Ini bagian nomor ${i + 1} yang isinya kalimat cukup panjang untuk dipecah.`
    ).join(' ');

    const chunks = splitForChat(text);
    assert.ok(chunks.length > 1, 'harus dipecah jadi beberapa bubble');
    assert.ok(chunks.length <= 3, 'tidak boleh lebih dari 3 bubble');
    for (const chunk of chunks) {
        assert.ok(chunk.length <= 400, `pecahan kepanjangan: ${chunk.length}`);
        assert.notEqual(chunk.trim(), '');
    }
    assert.equal(chunks.join(' ').replace(/\s+/g, ' '), text.replace(/\s+/g, ' '));
});

test('splitForChat: paragraf terpisah jadi bubble sendiri', () => {
    const paragraphs = [
        'Buka rentspacepurwokerto.my.id/booking untuk lihat daftar unit yang lagi kosong hari ini, semua unit ready tampil di halaman depan.',
        'Pilih tanggal dan durasinya dulu kak, nanti pilih unitnya, baru isi data pemesan sampai bagian pembayaran.',
        'Kalau mau bayar langsung, transfer ke nomor toko atau pakai QRIS, bukti pembayarannya tinggal diunggah di halaman itu juga.',
    ];
    const chunks = splitForChat(paragraphs.join('\n\n'));

    assert.ok(chunks.length >= 2, 'paragraf harus terpisah');
    assert.equal(chunks.length, 3);
    for (const paragraph of paragraphs) {
        assert.ok(chunks.includes(paragraph), `paragraf hilang: ${paragraph}`);
    }
});

test('splitForChat: isi teks tidak pernah dibuang walau pecahan kebanyakan', () => {
    const paragraphs = Array.from({ length: 7 }, (_, i) =>
        `Baris tabel harga ke-${i + 1} untuk unit yang sedang ready di toko sekarang.`
    );
    const chunks = splitForChat(paragraphs.join('\n\n'));

    assert.equal(chunks.length, 3, 'dibatasi 3 bubble');
    const joined = chunks.join(' ');
    for (const paragraph of paragraphs) {
        assert.ok(joined.includes(paragraph), `paragraf hilang: ${paragraph}`);
    }
});

test('waitForQuietPeriod: jeda dihitung ulang tiap pesan baru', async () => {
    const state = { pending: [{ text: 'ip 12' }] };

    // Setiap sleep mensimulasikan 1 detik yang lewat.
    let virtualNow = 0;
    const result = await waitForQuietPeriod(state, {
        timing: { quietMinMs: 5000, quietMaxMs: 5000, ackMinMs: 1000, ackMaxMs: 1000, maxWaitMs: 60000 },
        now: () => virtualNow,
        sleep: async (ms) => { virtualNow += ms; },
    });

    assert.equal(result.resets, 0, 'tidak ada pesan baru, jadi jeda tidak diulang');
    assert.equal(result.capped, false);
    assert.equal(virtualNow, 5000, 'harus menunggu tepat 5 detik');
});

test('waitForQuietPeriod: pesan baru di tengah jeda, jeda diulang', async () => {
    const state = { pending: [{ text: 'ip 12' }] };

    let virtualNow = 0;
    let firstSleep = true;
    const result = await waitForQuietPeriod(state, {
        timing: { quietMinMs: 5000, quietMaxMs: 5000, ackMinMs: 1000, ackMaxMs: 1000, maxWaitMs: 60000 },
        now: () => virtualNow,
        sleep: async (ms) => {
            virtualNow += ms;
            if (firstSleep) {
                firstSleep = false;
                state.pending.push({ text: 'yang pro max' });   // customer masih ngetik
            }
        },
    });

    assert.equal(result.resets, 1, 'jeda harus dihitung ulang sekali');
    assert.equal(virtualNow, 10000, 'total menunggu 10 detik (5 + 5)');
});

test('waitForQuietPeriod: burst yang isinya cuma ack tetap cepet', async () => {
    const state = { pending: [{ text: 'ok makasih' }] };

    let virtualNow = 0;
    await waitForQuietPeriod(state, {
        timing: { quietMinMs: 5000, quietMaxMs: 12000, ackMinMs: 1000, ackMaxMs: 1000, maxWaitMs: 60000 },
        now: () => virtualNow,
        sleep: async (ms) => { virtualNow += ms; },
    });

    assert.equal(virtualNow, 1000, 'sapaan singkat tidak perlu jeda panjang');
});

test('waitForQuietPeriod: customer ngetik terus tetap dibalas lewat maxWaitMs', async () => {
    const state = { pending: [{ text: 'ip 12' }] };

    let virtualNow = 0;
    const result = await waitForQuietPeriod(state, {
        timing: { quietMinMs: 5000, quietMaxMs: 5000, ackMinMs: 1000, ackMaxMs: 1000, maxWaitMs: 25000 },
        now: () => virtualNow,
        sleep: async (ms) => {
            virtualNow += ms;
            state.pending.push({ text: 'lagi dong' });   // ngetik terus, tidak pernah sunyi
        },
    });

    assert.equal(result.capped, true, 'harus kena batas keras');
    assert.equal(virtualNow, 25000, 'tidak boleh melewati 25 detik');
});

test('waitForQuietPeriod: perintah admin tidak ikut jeda panjang', async () => {
    const state = { pending: [{ text: '/broadcast groups' }] };

    let virtualNow = 0;
    await waitForQuietPeriod(state, {
        quick: true,
        timing: { quietMinMs: 5000, quietMaxMs: 12000, ackMinMs: 1000, ackMaxMs: 1000, maxWaitMs: 60000 },
        now: () => virtualNow,
        sleep: async (ms) => { virtualNow += ms; },
    });

    assert.equal(virtualNow, 1000, 'perintah admin harus terasa responsif');
});
