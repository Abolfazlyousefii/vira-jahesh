<?php

namespace Modules\Crm\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Crm\Models\FollowUp;
use Morilog\Jalali\Jalalian;

final class SaveFollowUp
{
    public function handle(array $form, ?int $id = null): FollowUp
    {
        WorkspaceAccess::check('Crm', 'crm.manage');
        $form['title'] = is_string($form['title'] ?? null) ? trim($form['title']) : ($form['title'] ?? null);
        $form['due_date'] = is_string($form['due_date'] ?? null) ? WorkspaceAccess::digits($form['due_date']) : ($form['due_date'] ?? null);
        $data = Validator::make(['form' => $form], [
            'form.customer_id' => 'required|integer|exists:customers,id', 'form.title' => 'required|string|max:160',
            'form.due_date' => ['required', 'regex:/^1[34][0-9]{2}\/[0-9]{2}\/[0-9]{2}$/'], 'form.note' => 'nullable|string|max:3000',
        ], ['*.required' => 'این بخش را تکمیل کنید.', '*.max' => 'مقدار واردشده بیش از حد مجاز است.', '*.string' => 'متن معتبر وارد کنید.', '*.exists' => 'مشتری انتخاب‌شده در دسترس نیست.', 'form.due_date.*' => 'تاریخ شمسی معتبر مانند ۱۴۰۵/۰۸/۲۰ وارد کنید.'])->validate()['form'];
        try {
            $jalali = Jalalian::fromFormat('Y/m/d', $data['due_date']);
            if ($jalali->format('Y/m/d') !== $data['due_date']) {
                throw new \InvalidArgumentException;
            }
            $dueAt = Carbon::createFromFormat('!Y-m-d', $jalali->toCarbon()->format('Y-m-d'), 'Asia/Tehran')->utc();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['form.due_date' => 'تاریخ شمسی معتبر مانند ۱۴۰۵/۰۸/۲۰ وارد کنید.']);
        }
        unset($data['due_date']);
        $record = $id ? FollowUp::findOrFail($id) : new FollowUp(['user_id' => auth()->id()]);
        $record->fill([...$data, 'due_at' => $dueAt])->save();

        return $record;
    }
}
