<?php

namespace App\Services;

use App\Models\SmtpSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SmtpSettingService extends AdminCrudService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $this->deactivateOthersWhenActive($data);

            return parent::create($data);
        });
    }

    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $this->deactivateOthersWhenActive($data, $model->getKey());

            return parent::update($model, $data);
        });
    }

    public function testConnection(SmtpSetting $setting, string $email): void
    {
        Config::set('mail.mailers.smtp_test', [
            'transport' => 'smtp',
            'host' => $setting->host,
            'port' => $setting->port,
            'encryption' => $setting->encryption,
            'username' => $setting->username,
            'password' => $setting->password,
            'timeout' => 10,
        ]);

        Mail::mailer('smtp_test')->raw('SMTP setting test email from Recruitment System.', function ($message) use ($email, $setting) {
            $message->from($setting->from_address, $setting->from_name)
                ->to($email)
                ->subject('SMTP Test Connection');
        });
    }

    protected function modelClass(): string
    {
        return SmtpSetting::class;
    }

    private function deactivateOthersWhenActive(array $data, ?int $exceptId = null): void
    {
        if (($data['is_active'] ?? false) !== true) {
            return;
        }

        SmtpSetting::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['is_active' => false]);
    }
}
