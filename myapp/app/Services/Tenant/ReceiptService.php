<?php

namespace App\Services\Tenant;

use App\Jobs\SendReceiptEmail;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptService
{
    public function generatePdf(Tenant $tenant, Order $order): Response
    {
        $order->load(['items', 'payments', 'customer', 'creator']);

        $pdf = Pdf::loadView('receipts.thermal', [
            'tenant' => $tenant,
            'order' => $order,
        ]);

        $pdf->setPaper([0, 0, 226, 800]);

        return $pdf->download("receipt-{$order->order_number}.pdf");
    }

    public function getShareableUrl(Order $order): string
    {
        return url("/receipts/{$order->receipt_token}");
    }

    public function emailReceipt(Tenant $tenant, Order $order, string $email): void
    {
        $receiptUrl = $this->getShareableUrl($order);

        SendReceiptEmail::dispatch($order, $tenant, $email, $receiptUrl);
    }
}
