<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console & Scheduled Tasks - Simpera
|--------------------------------------------------------------------------
| Menjalankan pemeriksaan berkala pengingat jatuh tempo secara harian.
*/
Schedule::command('simpera:send-reminders')
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->description('Pemeriksaan harian pengingat jatuh tempo Simpera (H-30, H-7, H-1, Hari H)');
