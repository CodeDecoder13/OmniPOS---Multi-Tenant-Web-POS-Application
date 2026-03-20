<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(private readonly ReportService $reportService) {}

    public function exportCsv(string $type, Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, string $period, ?int $branchId): StreamedResponse
    {
        $data = $this->getReportData($type, $tenant, $dateFrom, $dateTo, $period, $branchId);
        $rows = $this->formatCsvRows($type, $data);
        $filename = "{$type}_report_{$dateFrom->format('Y-m-d')}_to_{$dateTo->format('Y-m-d')}.csv";

        return new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf(string $type, Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, string $period, ?int $branchId): \Illuminate\Http\Response
    {
        $data = $this->getReportData($type, $tenant, $dateFrom, $dateTo, $period, $branchId);
        $filename = "{$type}_report_{$dateFrom->format('Y-m-d')}_to_{$dateTo->format('Y-m-d')}.pdf";

        $typeLabels = [
            'sales_summary' => 'Sales Summary',
            'products' => 'Top Products',
            'payments' => 'Payment Breakdown',
            'operators' => 'Operator Performance',
            'branches' => 'Branch Comparison',
            'inventory' => 'Inventory Report',
            'tax' => 'Tax Report',
        ];

        $pdf = Pdf::loadView('reports.pdf', [
            'tenant' => $tenant,
            'type' => $type,
            'typeLabel' => $typeLabels[$type] ?? ucfirst($type),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    private function getReportData(string $type, Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, string $period, ?int $branchId): array
    {
        return match ($type) {
            'sales_summary' => $this->reportService->getSummary($tenant, $dateFrom, $dateTo, $branchId),
            'products' => $this->reportService->getTopProducts($tenant, $dateFrom, $dateTo, $branchId),
            'payments' => $this->reportService->getPaymentBreakdown($tenant, $dateFrom, $dateTo, $branchId),
            'operators' => $this->reportService->getOperatorPerformance($tenant, $dateFrom, $dateTo, $branchId),
            'branches' => $this->reportService->getBranchComparison($tenant, $dateFrom, $dateTo),
            'inventory' => $this->reportService->getInventoryReport($tenant, $dateFrom, $dateTo, $branchId),
            'tax' => $this->reportService->getTaxReport($tenant, $dateFrom, $dateTo, $period, $branchId),
            default => [],
        };
    }

    private function formatCsvRows(string $type, array $data): array
    {
        return match ($type) {
            'sales_summary' => $this->formatSalesSummaryCsv($data),
            'products' => $this->formatProductsCsv($data),
            'payments' => $this->formatPaymentsCsv($data),
            'operators' => $this->formatOperatorsCsv($data),
            'branches' => $this->formatBranchesCsv($data),
            'inventory' => $this->formatInventoryCsv($data),
            'tax' => $this->formatTaxCsv($data),
            default => [['No data']],
        };
    }

    private function formatSalesSummaryCsv(array $data): array
    {
        return [
            ['Metric', 'Value'],
            ['Total Revenue', $data['total_revenue'] ?? 0],
            ['Order Count', $data['order_count'] ?? 0],
            ['Average Order Value', $data['avg_order_value'] ?? 0],
            ['Items Sold', $data['items_sold'] ?? 0],
            ['Total Discounts', $data['total_discounts'] ?? 0],
        ];
    }

    private function formatProductsCsv(array $data): array
    {
        $rows = [['Product', 'Quantity Sold', 'Revenue']];
        foreach ($data['by_revenue'] ?? [] as $item) {
            $rows[] = [$item['product_name'], $item['total_quantity'], $item['total_revenue']];
        }
        return $rows;
    }

    private function formatPaymentsCsv(array $data): array
    {
        $rows = [['Payment Method', 'Transaction Count', 'Total Amount']];
        foreach ($data as $item) {
            $rows[] = [$item['label'], $item['count'], $item['total_amount']];
        }
        return $rows;
    }

    private function formatOperatorsCsv(array $data): array
    {
        $rows = [['Operator', 'Orders', 'Revenue', 'Avg Order Value']];
        foreach ($data as $item) {
            $rows[] = [$item['user_name'], $item['order_count'], $item['total_revenue'], $item['avg_order_value']];
        }
        return $rows;
    }

    private function formatBranchesCsv(array $data): array
    {
        $rows = [['Branch', 'Orders', 'Revenue', 'Avg Order Value']];
        foreach ($data as $item) {
            $rows[] = [$item['branch_name'], $item['order_count'], $item['total_revenue'], $item['avg_order_value']];
        }
        return $rows;
    }

    private function formatInventoryCsv(array $data): array
    {
        $rows = [['Product', 'Category', 'Branch', 'Qty On Hand', 'Low Stock Threshold', 'Cost Price', 'Price', 'Stock Value', 'Low Stock']];
        foreach ($data['stock_levels'] ?? [] as $item) {
            $rows[] = [
                $item['product_name'], $item['category_name'], $item['branch_name'],
                $item['quantity_on_hand'], $item['low_stock_threshold'],
                $item['cost_price'], $item['price'], $item['stock_value'],
                $item['is_low_stock'] ? 'Yes' : 'No',
            ];
        }
        return $rows;
    }

    private function formatTaxCsv(array $data): array
    {
        $rows = [
            ['Tax Summary'],
            ['Total Tax Collected', $data['total_tax'] ?? 0],
            ['Total Taxable Sales', $data['total_taxable'] ?? 0],
            ['Effective Tax Rate', ($data['effective_rate'] ?? 0) . '%'],
            [],
            ['Period', 'Orders', 'Taxable Amount', 'Tax Collected'],
        ];
        foreach ($data['by_period'] ?? [] as $item) {
            $rows[] = [$item['period'], $item['order_count'], $item['taxable_amount'], $item['tax_amount']];
        }
        return $rows;
    }
}
