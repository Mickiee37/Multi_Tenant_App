<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Tenant; // Adjust if your tenant model is elsewhere

class PullUpdates extends Command
{
    protected $signature = 'app:pull-updates';
    protected $description = 'Pull latest updates from GitHub and notify tenants via email';

    public function handle()
    {
        $output = [];
        $returnVar = 0;
        
        // First try a normal pull
        exec('git pull origin main', $output, $returnVar);
        
        // If it fails with unrelated histories, try with --allow-unrelated-histories
        if ($returnVar !== 0 && strpos(implode("\n", $output), 'refusing to merge unrelated histories') !== false) {
            $output = [];
            $returnVar = 0;
            exec('git pull origin main --allow-unrelated-histories', $output, $returnVar);
        }
        
        $this->info(implode(PHP_EOL, $output));

        if ($returnVar === 0) {
            // Notify all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                if ($tenant->email) {
                    Mail::raw('A new update is now available in your SaaS application. Please check your dashboard for new features and improvements.', function ($message) use ($tenant) {
                        $message->to($tenant->email)
                                ->subject('New Update Available!');
                    });
                }
            }
            $this->info('Tenants notified via email.');
        } else {
            $this->error('Failed to pull updates. Please check your Git configuration.');
        }
        return $returnVar === 0 ? Command::SUCCESS : Command::FAILURE;
    }
} 