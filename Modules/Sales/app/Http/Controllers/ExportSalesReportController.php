<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Sales\Actions\SalesReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportSalesReportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $report = app(SalesReport::class)->handle($request->only(['from', 'to', 'channel', 'warehouse']));

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['نوع رویداد', 'شمارهٔ سند', 'فاکتور مرجع', 'تاریخ شمسی تهران', 'ساعت تهران', 'انبار', 'کانال', 'فروش — تومان', 'بازپرداخت — تومان', 'خالص — تومان'], ',', '"', '');
            foreach ($report['events'] as $event) {
                $warehouse = $event['warehouse'];
                if (preg_match('/^(?:[\s\p{Z}]*[=+\-@]|[\t\r\n])/u', $warehouse)) {
                    $warehouse = "'".$warehouse;
                }
                fputcsv($stream, [$event['type'] === 'sale' ? 'فروش' : 'مرجوعی', $event['id'], $event['sale_id'], $event['date'], $event['time'], $warehouse,
                    $event['channel'] === 'pos' ? 'صندوق' : 'مستقیم', $event['gross'], $event['refunds'], $event['net']], ',', '"', '');
            }
            fclose($stream);
        }, 'vira-sales-'.str_replace('/', '', $report['filters']['from']).'-'.str_replace('/', '', $report['filters']['to']).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
