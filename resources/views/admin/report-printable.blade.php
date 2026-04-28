<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .page {
            background: white;
            margin: 0 auto;
            width: 297mm;
            min-height: auto;
            padding: 10mm 15mm;
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .header {
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
        }

        .header-left h1 {
            font-size: 32px;
            font-weight: 900;
            color: #1e1b4b;
            margin: 0;
        }

        .header-left p {
            font-size: 12px;
            color: #64748b;
            margin: 5px 0 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: bold;
        }

        .header-right {
            text-align: right;
        }

        .confidential {
            display: inline-block;
            padding: 6px 15px;
            background: #1e1b4b;
            color: white;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            background: #ffffff;
        }

        .kpi-label {
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
        }

        .kpi-mom {
            margin-top: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.8fr 0.8fr;
            gap: 25px;
            align-items: start;
        }

        h3 {
            font-size: 13px;
            border-left: 4px solid #4f46e5;
            padding-left: 12px;
            margin-bottom: 15px;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th {
            background: #f8fafc;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .chart-container {
            height: 180px;
            margin-bottom: 20px;
            position: relative;
        }

        .payment-card {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .progress-bg {
            height: 6px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #4f46e5;
        }

        .asset-card {
            padding: 10px 15px;
            background: #f8fafc;
            border-left: 4px solid #4f46e5;
            border-radius: 0 8px 8px 0;
            margin-bottom: 10px;
            border: 1px solid #f1f5f9;
            border-left-width: 4px;
        }

        .asset-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e1b4b;
        }

        .asset-info {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
        }

        @media print {
            body {
                background: white;
            }

            .page {
                box-shadow: none;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div class="header-left">
                <h1>RENTSPACE <span style="color: #4f46e5;">SYSTEMS</span></h1>
                <p>{{ strtoupper($title) }}</p>
            </div>
            <div class="header-right">
                <div class="confidential">CONFIDENTIAL ANALYTICS</div>
                <div style="font-size: 10px; color: #64748b;">Issued by <strong
                        style="color: #1e1b4b;">{{ $admin_name }}</strong> &bull; {{ $generated_at }}</div>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Gross Revenue</div>
                <div class="kpi-value">Rp {{ number_format($total_revenue, 0, ',', '.') }}</div>
                @if(isset($mom_growth))
                    <div class="kpi-mom" style="color: {{ $mom_growth >= 0 ? '#16a34a' : '#dc2626' }}">
                        {{ $mom_growth >= 0 ? '▲' : '▼' }} {{ number_format(abs($mom_growth), 1) }}% <span
                            style="color: #94a3b8; font-weight: normal; font-size: 10px;">growth vs prev</span>
                    </div>
                @endif
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Net Profit</div>
                <div class="kpi-value" style="color: #166534">Rp {{ number_format($total_net, 0, ',', '.') }}</div>
                <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">After Commission & Fees</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value">{{ $total_trx }} Success</div>
                <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Verified Completed Rentals</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Avg Ticket Size</div>
                <div class="kpi-value">Rp {{ number_format($avg_ticket, 0, ',', '.') }}</div>
                <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Rev per Successful Order</div>
            </div>
        </div>

        <!-- Panoramic Chart Section -->
        <div
            style="background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 20px; margin-bottom: 25px;">
            <h3>Revenue Performance Trend</h3>
            <div class="chart-container" style="height: 220px;">
                <canvas id="mainChart"></canvas>
            </div>

            <!-- Dynamic Executive Summary moved here -->
            <div style="margin-top: 25px; padding: 20px; background: #fdf2f8; border: 1px solid #fce7f3; border-radius: 12px; font-size: 10px; line-height: 1.8; color: #86198f;">
                <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Strategic Performance Overview</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    @php
                        $bestUnit = $top_units[0] ?? null;
                        $isPositive = ($mom_growth ?? 0) >= 0;
                        $growthVerb = $isPositive ? 'kenaikan' : 'penurunan';
                        $growthText = 'menyumbang ' . $growthVerb . ' sebesar ' . number_format(abs($mom_growth), 1) . '%';
                        $nominalGainText = ' (Rp ' . number_format(abs($revenue_gain ?? 0), 0, ',', '.') . ')';
                        $efficiencyRate = $total_revenue > 0 ? (($total_discounts + $total_commission) / $total_revenue) * 100 : 0;
                    @endphp
                    <ul style="margin: 0; padding-left: 15px;">
                        <li>Kinerja finansial periode ini mencatat <strong>{{ $growthText }}{{ $nominalGainText }}</strong> dibanding periode sebelumnya.</li>
                        @if($bestUnit)
                            <li>Aset <strong>{{ $bestUnit['name'] }}</strong> mendominasi produktivitas dengan <strong>{{ $bestUnit['count'] }} sesi penyewaan</strong> sukses.</li>
                        @endif
                    </ul>
                    <ul style="margin: 0; padding-left: 15px;">
                        <li>Efisiensi biaya (Diskon & Komisi) terjaga optimal pada angka <strong>{{ number_format($efficiencyRate, 1) }}%</strong> dari total pendapatan.</li>
                        <li>Sistem pembayaran digital memperkuat stabilitas arus kas dan akurasi verifikasi transaksi perusahaan.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- 1. Trend Table -->
            <div style="background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 20px;">
                <h3>Performance Data Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th style="text-align: right">Trx</th>
                            <th style="text-align: right">Revenue (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $row)
                            <tr>
                                <td style="font-weight: bold; color: #475569;">{{ $row['label'] }}</td>
                                <td style="text-align: right">{{ $row['trx'] }}</td>
                                <td style="text-align: right; font-weight: 700; color: #1e1b4b;">
                                    {{ number_format($row['rev'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 2. Payment -->
            <div style="background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 20px;">
                <h3>Payment Distribution</h3>
                @php $maxPay = count($payments) > 0 ? max(array_column($payments, 'total')) : 1; @endphp
                @foreach($payments as $p)
                    <div class="payment-card">
                        <div class="payment-header">
                            <span style="text-transform: uppercase; color: #1e1b4b;">{{ $p['metode_pembayaran'] }}</span>
                            <span style="color: #4f46e5;">Rp {{ number_format($p['total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: {{ ($p['total'] / $maxPay) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach

                <div
                    style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #e2e8f0; font-size: 9px; color: #64748b; line-height: 1.4;">
                    <strong>Verification:</strong> All payment data is cross-referenced with bank settlement reports.
                </div>

                <div style="margin-top: 30px;">
                    <h3>Operational Efficiency</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="padding: 12px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 8px;">
                            <div
                                style="font-size: 8px; font-weight: bold; color: #9a3412; text-transform: uppercase; margin-bottom: 5px;">
                                Discount Impact</div>
                            <div style="font-size: 14px; font-weight: 900; color: #c2410c;">Rp
                                {{ number_format($total_discounts, 0, ',', '.') }}</div>
                            <div style="font-size: 9px; color: #ea580c; margin-top: 3px;">
                                {{ $total_revenue > 0 ? number_format(($total_discounts / $total_revenue) * 100, 1) : 0 }}%
                                of Gross</div>
                        </div>
                        <div style="padding: 12px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 8px;">
                            <div
                                style="font-size: 8px; font-weight: bold; color: #166534; text-transform: uppercase; margin-bottom: 5px;">
                                Affiliate Payout</div>
                            <div style="font-size: 14px; font-weight: 900; color: #15803d;">Rp
                                {{ number_format($total_commission, 0, ',', '.') }}</div>
                            <div style="font-size: 9px; color: #16a34a; margin-top: 3px;">Commission to Partners</div>
                        </div>
                    </div>
                    <p style="font-size: 8px; color: #94a3b8; margin-top: 10px; font-style: italic;">Note: Net profit is
                        calculated after deducting these operational adjustments.</p>
                </div>
            </div>

            <!-- 3. Assets -->
            <div style="background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 20px;">
                <h3>Top Performing Assets</h3>
                @foreach($top_units as $u)
                    <div class="asset-card">
                        <div class="asset-name">{{ $u['name'] }}</div>
                        <div class="asset-info">{{ $u['count'] }} Success &bull; <strong>Rp
                                {{ number_format($u['revenue'], 0, ',', '.') }}</strong></div>
                    </div>
                @endforeach

                <div style="margin-top: 30px;">
                    <h3>Top Tenants</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($top_tenants as $t)
                            <div
                                style="padding: 6px 12px; background: #eff6ff; color: #1e40af; border-radius: 40px; font-size: 9px; font-weight: bold; border: 1px solid #dbeafe;">
                                {{ explode(' ', $t['nama'])[0] }} &bull; {{ number_format($t['total_spent'] / 1000, 0) }}k
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div
            style="margin-top: 40px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px; font-size: 9px; color: #94a3b8; letter-spacing: 2px;">
            &copy; {{ date('Y') }} RENTSPACE SYSTEM ANALYTICS &bull; DATA VERIFIED SECURE &bull; BOARDROOM-READY
            DOCUMENT
        </div>
    </div>

    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');
        const chartData = @json($breakdown);

        const myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(d => d.label),
                datasets: [{
                    label: 'Revenue',
                    data: chartData.map(d => d.rev),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: true, grid: { display: false }, ticks: { font: { size: 8 } } },
                    y: { display: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 8 }, callback: (val) => 'Rp ' + (val / 1000) + 'k' } }
                }
            }
        });

        window.onload = function () {
            setTimeout(() => {
                window.print();
            }, 1500);
        }
    </script>
</body>

</html>