<section class="panel chart-panel" aria-label="روند فروش" x-data="viraSalesChart(@js($data))">
    <div class="panel-head">
        <div>
            <div class="panel-title"><i data-lucide="chart-no-axes-combined"></i> روند فروش</div>
            <span class="panel-subtitle">جمع فروش ناخالص ثبت‌شده؛ پیش از کسر مرجوعی</span>
        </div>
        <div class="chart-head-left">
            <div class="chart-total num"><span x-text="total"></span> <small>تومان</small></div>
            <div class="segmented" role="group" aria-label="بازه نمودار">
                <button type="button" :class="{active: period === 'week'}" :aria-pressed="period === 'week'" @click="period='week'">۷ روز</button>
                <button type="button" :class="{active: period === 'month'}" :aria-pressed="period === 'month'" @click="period='month'">۳۰ روز</button>
            </div>
        </div>
    </div>
    <div class="chart-area">
        <div class="chart-legend"><i class="legend-dot"></i> فروش ثبت‌شده</div>
        <div class="v4-chart-no-data" x-show="empty" x-cloak>در این بازه هنوز فروشی ثبت نشده است.</div>
        <svg class="chart-svg" x-show="!empty" x-cloak viewBox="0 0 720 180" preserveAspectRatio="none" role="img" aria-label="نمودار فروش واقعی در بازه انتخاب‌شده">
            <defs><linearGradient id="viraSalesChartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#13b7a6" stop-opacity=".26"/><stop offset="1" stop-color="#13b7a6" stop-opacity="0"/></linearGradient></defs>
            <path class="gridline" d="M8 35H712M8 82H712M8 129H712"/>
            <path class="chart-area-fill" :d="shape.area" style="fill:url(#viraSalesChartFill)"/>
            <path class="chart-line" :d="shape.line"/>
        </svg>
        <div class="chart-footer" x-show="!empty" x-cloak>
            <template x-for="(point, index) in points.filter((point, index) => period === 'week' || index % 5 === 0 || index === points.length - 1)" :key="index">
                <span x-text="point.label"></span>
            </template>
        </div>
    </div>
</section>
