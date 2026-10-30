<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'vira:owner {email} {--name=مدیر ویرا}';

    protected $description = 'Create an owner account without a public registration endpoint';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $password = getenv('VIRA_SETUP_PASSWORD') ?: $this->secret('رمز عبور مدیر (حداقل ۱۲ کاراکتر)');
        $validator = Validator::make(['email' => $email, 'password' => $password, 'name' => $this->option('name')], [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        DB::transaction(function () use ($email, $password): void {
            $this->callSilent('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            $user = User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
            $user->assignRole('owner');
            activity('administration')->causedBy($user)->performedOn($user)->log('حساب مدیر ایجاد شد');
        });
        $this->info('حساب مدیر ایجاد شد.');

        return self::SUCCESS;
    }
}
