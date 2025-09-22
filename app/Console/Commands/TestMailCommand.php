<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Send a test email to check SMTP configuration';

    public function handle()
    {
        $to = $this->argument('email');

        try {
            Mail::raw('This is a test email from Laravel on Railway.', function ($message) use ($to) {
                $message->to($to)->subject('SMTP Test');
            });

            $this->info("✅ Mail sent successfully to {$to}");
        } catch (\Exception $e) {
            $this->error("❌ Mail failed: " . $e->getMessage());
        }
    }
}
