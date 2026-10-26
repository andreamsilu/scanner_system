<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = json_decode(getenv('SCANNER_TEST_DB'), true, flags: JSON_THROW_ON_ERROR);
if (! str_ends_with($connection['database'], '_test')) {
    throw new RuntimeException('Concurrency workers require an isolated test database.');
}
config(['database.default' => 'mysql', 'database.connections.mysql' => $connection]);
DB::purge('mysql');
while (microtime(true) < (float) $argv[5]) {
    usleep(10000);
}
if ($argv[1] === 'issue') {
    $ticket = Ticket::create(['event_id' => (int) $argv[3], 'attendee_id' => (int) $argv[4], 'type' => 'single', 'status' => 'active']);
    echo json_encode(['ticket_number' => $ticket->ticket_number], JSON_THROW_ON_ERROR);
} else {
    $scanner = User::findOrFail((int) $argv[2]);
    echo json_encode(app(CheckInService::class)->admit($scanner, (int) $argv[3], $argv[4], 1, 0), JSON_THROW_ON_ERROR);
}
