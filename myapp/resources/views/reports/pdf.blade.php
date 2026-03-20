<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 11px;
        color: #1a1a1a;
        line-height: 1.5;
        padding: 40px;
    }
    .header {
        border-bottom: 2px solid #14b8a6;
        padding-bottom: 16px;
        margin-bottom: 24px;
    }
    .store-name {
        font-size: 20px;
        font-weight: bold;
        color: #0f172a;
    }
    .report-title {
        font-size: 14px;
        color: #475569;
        margin-top: 4px;
    }
    .report-meta {
        font-size: 10px;
        color: #94a3b8;
        margin-top: 8px;
    }
    .summary-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 24px;
    }
    .summary-grid {
        width: 100%;
    }
    .summary-grid td {
        padding: 6px 12px;
        vertical-align: top;
    }
    .summary-label {
        font-size: 10px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .summary-value {
        font-size: 16px;
        font-weight: bold;
        color: #0f172a;
    }
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    table.data-table thead th {
        background: #f1f5f9;
        border-bottom: 2px solid #e2e8f0;
        padding: 8px 10px;
        text-align: left;
        font-size: 10px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    table.data-table tbody td {
        padding: 7px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 11px;
    }
    table.data-table tbody tr:nth-child(even) {
        background: #fafafa;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-danger { background: #fef2f2; color: #dc2626; }
    .badge-success { background: #f0fdf4; color: #16a34a; }
    .footer {
        position: fixed;
        bottom: 30px;
        left: 40px;
        right: 40px;
        font-size: 9px;
        color: #94a3b8;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
        text-align: center;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="store-name">{{ $tenant->data['store_name'] ?? $tenant->name }}</div>
        <div class="report-title">{{ $typeLabel }} Report</div>
        <div class="report-meta">
            Period: {{ $dateFrom->format('M d, Y') }} &mdash; {{ $dateTo->format('M d, Y') }}
            &nbsp;&bull;&nbsp; Generated: {{ now()->format('M d, Y h:i A') }}
        </div>
    </div>

    @if($type === 'sales_summary')
        <div class="summary-box">
            <table class="summary-grid">
                <tr>
                    <td>
                        <div class="summary-label">Total Revenue</div>
                        <div class="summary-value">&#8369;{{ number_format($data['total_revenue'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Total Orders</div>
                        <div class="summary-value">{{ number_format($data['order_count'] ?? 0) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Avg Order Value</div>
                        <div class="summary-value">&#8369;{{ number_format($data['avg_order_value'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Items Sold</div>
                        <div class="summary-value">{{ number_format($data['items_sold'] ?? 0) }}</div>
                    </td>
                </tr>
            </table>
        </div>

    @elseif($type === 'products')
        <h3 style="margin-bottom: 8px; color: #334155;">Top Products by Revenue</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-right">Qty Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_revenue'] ?? [] as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td class="text-right">{{ number_format($item['total_quantity']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['total_revenue'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type === 'payments')
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Transactions</th>
                    <th class="text-right">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td class="text-right">{{ number_format($item['count']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type === 'operators')
        <table class="data-table">
            <thead>
                <tr>
                    <th>Operator</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Avg Order</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item['user_name'] }}</td>
                    <td class="text-right">{{ number_format($item['order_count']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['total_revenue'], 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['avg_order_value'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type === 'branches')
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Avg Order</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item['branch_name'] }}</td>
                    <td class="text-right">{{ number_format($item['order_count']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['total_revenue'], 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['avg_order_value'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type === 'inventory')
        <div class="summary-box">
            <table class="summary-grid">
                <tr>
                    <td>
                        <div class="summary-label">Total Stock Value</div>
                        <div class="summary-value">&#8369;{{ number_format($data['total_stock_value'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Total Items</div>
                        <div class="summary-value">{{ count($data['stock_levels'] ?? []) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Low Stock Alerts</div>
                        <div class="summary-value" style="color: #dc2626;">{{ count($data['low_stock_items'] ?? []) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <h3 style="margin-bottom: 8px; color: #334155;">Stock Levels</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Branch</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Threshold</th>
                    <th class="text-right">Value</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['stock_levels'] ?? [] as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['category_name'] }}</td>
                    <td>{{ $item['branch_name'] }}</td>
                    <td class="text-right">{{ number_format($item['quantity_on_hand']) }}</td>
                    <td class="text-right">{{ number_format($item['low_stock_threshold']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['stock_value'], 2) }}</td>
                    <td class="text-center">
                        @if($item['is_low_stock'])
                            <span class="badge badge-danger">Low</span>
                        @else
                            <span class="badge badge-success">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type === 'tax')
        <div class="summary-box">
            <table class="summary-grid">
                <tr>
                    <td>
                        <div class="summary-label">Total Tax Collected</div>
                        <div class="summary-value">&#8369;{{ number_format($data['total_tax'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Total Taxable Sales</div>
                        <div class="summary-value">&#8369;{{ number_format($data['total_taxable'] ?? 0, 2) }}</div>
                    </td>
                    <td>
                        <div class="summary-label">Effective Rate</div>
                        <div class="summary-value">{{ $data['effective_rate'] ?? 0 }}%</div>
                    </td>
                    <td>
                        <div class="summary-label">Tax Type</div>
                        <div class="summary-value">{{ ($data['tax_inclusive'] ?? false) ? 'Inclusive' : 'Exclusive' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <h3 style="margin-bottom: 8px; color: #334155;">Tax Collection by Period</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Taxable Amount</th>
                    <th class="text-right">Tax Collected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_period'] ?? [] as $item)
                <tr>
                    <td>{{ $item['period'] }}</td>
                    <td class="text-right">{{ number_format($item['order_count']) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['taxable_amount'], 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($item['tax_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $tenant->data['store_name'] ?? $tenant->name }} &bull; Report generated by OmniPOS
    </div>
</body>
</html>
