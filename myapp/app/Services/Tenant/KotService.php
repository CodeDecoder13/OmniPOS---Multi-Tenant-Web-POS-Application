<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class KotService
{
    public function generatePdf(Tenant $tenant, Order $order): Response
    {
        $order->load(['items.variations', 'items.itemAddons', 'table:id,name']);

        $pdf = Pdf::loadView('kitchen.kot', [
            'tenant' => $tenant,
            'order' => $order,
        ]);

        $pdf->setPaper([0, 0, 226, 800]);

        return $pdf->download("kot-{$order->order_number}.pdf");
    }
}
