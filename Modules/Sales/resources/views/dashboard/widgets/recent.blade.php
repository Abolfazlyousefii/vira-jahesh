<section class="panel invoice-panel" aria-label="آخرین فاکتورها">
    <div class="panel-head">
        <div>
            <div class="panel-title"><i data-lucide="receipt-text"></i> آخرین فاکتورها</div>
            <span class="panel-subtitle">سه فاکتور ثبت‌شده اخیر مجموعه</span>
        </div>
        <a class="text-link" href="{{ route('panel.sales') }}">مشاهده همه <i data-lucide="arrow-left"></i></a>
    </div>
    @if(count($data['invoices']))
        <div class="invoice-table-wrap">
            <table>
                <thead><tr><th>شماره فاکتور</th><th>مشتری</th><th>تاریخ</th><th>مبلغ (تومان)</th><th>کانال</th></tr></thead>
                <tbody>
                    @foreach($data['invoices'] as $invoice)
                        <tr>
                            <td><strong>#{{ $invoice['id'] }}</strong></td>
                            <td><div class="customer-cell"><span class="customer-avatar">{{ mb_substr($invoice['customer'], 0, 1) }}</span>{{ $invoice['customer'] }}</div></td>
                            <td>{{ $invoice['date'] }}</td>
                            <td class="num"><strong>{{ number_format($invoice['amount']) }}</strong></td>
                            <td><span class="status paid">{{ $invoice['channel'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="v4-empty">هنوز فاکتوری ثبت نشده است. برای شروع از بخش فروش استفاده کنید.</div>
    @endif
</section>
