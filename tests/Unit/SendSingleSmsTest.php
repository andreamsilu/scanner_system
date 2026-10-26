<?php

namespace Tests\Unit;

use App\Jobs\SendSingleSms;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SendSingleSmsTest extends TestCase
{
    public function test_last_provider_failure_is_reported_as_a_failed_job(): void
    {
        Log::spy();
        $queued = Mockery::mock(Job::class);
        $queued->shouldReceive('getJobId')->andReturn('test-job');
        $queued->shouldReceive('attempts')->andReturn(3);
        $service = Mockery::mock(SmsService::class);
        $service->shouldReceive('send')->once()->andReturn(['success' => false, 'error' => 'Provider rejected the message']);
        $job = new SendSingleSms('255712345678', 'Test message', 'notification');
        $job->setJob($queued);
        $this->expectException(\RuntimeException::class);
        $job->handle($service);
    }
}
