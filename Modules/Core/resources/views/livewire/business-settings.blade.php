<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">فضای کار شما</span><h1>تنظیمات مجموعه</h1><p class="s-muted">اطلاعات اصلی کسب‌وکارتان را یک‌بار وارد کنید.</p></div></div>
    <x-workspace.feedback :notice="$notice" />
    <form wire:submit="save" class="s-panel v-form-panel">
        <h2>مشخصات کسب‌وکار</h2>
        <div class="v-form-grid">
            <x-workspace.field label="نام مجموعه *" name="form.name"><input wire:model="form.name" maxlength="120" autocomplete="organization" required></x-workspace.field>
            <x-workspace.field label="شمارهٔ تماس" name="form.phone"><input wire:model="form.phone" inputmode="tel" dir="ltr" autocomplete="tel" maxlength="30"></x-workspace.field>
            <x-workspace.field label="شهر" name="form.city"><input wire:model="form.city" maxlength="100" autocomplete="address-level2"></x-workspace.field>
            <x-workspace.field label="واحد مبلغ"><input value="تومان — عدد صحیح" disabled><small class="s-muted">تمام قیمت‌ها و فاکتورها در این نسخه به تومان ثبت می‌شوند.</small></x-workspace.field>
        </div>
        <x-workspace.field label="نشانی" name="form.address"><textarea wire:model="form.address" rows="3" maxlength="1000" autocomplete="street-address"></textarea></x-workspace.field>
        <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="save">ذخیرهٔ اطلاعات</button><span class="s-muted">موارد ستاره‌دار ضروری‌اند.</span></div>
    </form>
</div>
