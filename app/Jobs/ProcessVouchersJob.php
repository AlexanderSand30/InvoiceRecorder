<?php

namespace App\Jobs;

use App\Services\VoucherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVouchersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private array $xmlContents;
    private $user;

    public function __construct(array $xmlContents, $user)
    {
        $this->xmlContents = $xmlContents;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(VoucherService $voucherService): void
    {
        $voucherService->storeVouchersFromXmlContents($this->xmlContents, $this->user);
    }
}
