<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkUpload;
use App\Models\BulkUpload;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BulkAttendeeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        Storage::fake('local');
        Storage::fake('public');
        $this->admin = User::factory()->create();
        $role = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin->roles()->attach($role);
        $this->event = Event::create([
            'name' => 'Conference', 'venue' => 'Hall', 'location' => 'Dar es Salaam',
            'start_date' => now()->subHour(), 'end_date' => now()->addHour(),
            'capacity' => 100, 'created_by' => $this->admin->id,
        ]);
    }

    private function upload(string $filename): BulkUpload
    {
        return BulkUpload::create(['event_id' => $this->event->id, 'user_id' => $this->admin->id, 'filename' => $filename, 'status' => 'pending']);
    }

    public function test_csv_template_headers_import_with_sequential_numbers_and_local_qr_codes(): void
    {
        $path = 'bulk-uploads/attendees.csv';
        Storage::put($path, "name (required),email (optional),phone (required),position (optional),ticket_type (required)\nAlice,,0712345678,,single\nBob,,0712345679,,double\n");
        $upload = $this->upload('attendees.csv');
        app(BulkAttendeeImporter::class)->import($upload, $path);
        $this->assertSame('completed', $upload->fresh()->status);
        $this->assertSame(['1', '2'], Ticket::orderBy('id')->pluck('ticket_number')->all());
        $this->assertSame(2, $upload->fresh()->processed_records);
        $attendee = Ticket::first()->attendee;
        $this->assertSame(48, strlen($attendee->token));
        Storage::disk('public')->assertExists($attendee->qr_code);
        $this->assertStringContainsString('<svg', Storage::disk('public')->get($attendee->qr_code));
    }

    public function test_xlsx_import_preserves_phone_strings_and_double_ticket_type(): void
    {
        $path = 'bulk-uploads/attendees.xlsx';
        Storage::makeDirectory('bulk-uploads');
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['name (required)', 'email (optional)', 'phone (required)', 'position (optional)', 'ticket_type (required)'],
            ['Alice', '', '0712345678', '', 'double'],
        ]);
        (new Xlsx($book))->save(Storage::path($path));
        $upload = $this->upload('attendees.xlsx');
        app(BulkAttendeeImporter::class)->import($upload, $path);
        $this->assertSame('completed', $upload->fresh()->status);
        $this->assertSame('double', Ticket::first()->type);
        $this->assertSame('0712345678', Ticket::first()->attendee->phone);
    }

    public function test_upload_endpoint_dispatches_a_durable_job(): void
    {
        $file = UploadedFile::fake()->createWithContent('attendees.csv', "name,phone,ticket_type\nAlice,0712345678,single\n");
        $this->actingAs($this->admin)->post(route('events.attendees.bulk-upload.store', $this->event), ['csv_file' => $file])
            ->assertSessionHasNoErrors()->assertRedirect();
        Queue::assertPushed(ProcessBulkUpload::class);
    }

    public function test_export_routes_resolve_to_the_correct_actions(): void
    {
        $router = app('router');
        $single = $router->getRoutes()->match(Request::create('/events/1/tickets/abc/export'));
        $all = $router->getRoutes()->match(Request::create('/events/1/tickets/export/excel'));
        $users = $router->getRoutes()->match(Request::create('/users/export'));
        $this->assertStringEndsWith('@exportTicket', $single->getActionName());
        $this->assertStringEndsWith('@exportAll', $all->getActionName());
        $this->assertStringEndsWith('@export', $users->getActionName());
        $this->assertStringContainsString('/abc/export', route('events.tickets.export-ticket', ['event' => 1, 'ticket' => 'abc']));
    }

    public function test_spreadsheet_and_pdf_exports_download_successfully(): void
    {
        $path = 'bulk-uploads/attendees.csv';
        Storage::put($path, "name,phone,ticket_type\nAlice,0712345678,single\n");
        app(BulkAttendeeImporter::class)->import($this->upload('attendees.csv'), $path);
        $this->actingAs($this->admin)->get(route('events.attendees.template', $this->event))->assertOk()->assertDownload();
        $this->get(route('events.tickets.export', ['event' => $this->event, 'format' => 'excel']))->assertOk()->assertDownload();
        $this->get(route('events.tickets.export', ['event' => $this->event, 'format' => 'pdf']))->assertOk()->assertDownload();
        $ticket = Ticket::first();
        $jpeg = $this->get(route('events.tickets.download-jpg', ['event' => $this->event, 'ticket' => $ticket->attendee->token]));
        $jpeg->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame("\xFF\xD8", substr($jpeg->getContent(), 0, 2));
    }

    public function test_ticket_numbers_are_sorted_numerically_in_the_table(): void
    {
        $path = 'bulk-uploads/many.csv';
        $csv = "name,phone,ticket_type\n";
        for ($index = 1; $index <= 12; $index++) {
            $csv .= "Guest {$index},07123456".str_pad((string) $index, 2, '0', STR_PAD_LEFT).",single\n";
        }
        Storage::put($path, $csv);
        app(BulkAttendeeImporter::class)->import($this->upload('many.csv'), $path);
        $query = http_build_query(['start' => 0, 'length' => 12, 'order' => [['column' => 0, 'dir' => 'asc']]]);
        $this->actingAs($this->admin)->getJson(route('events.tickets.data', $this->event).'?'.$query)
            ->assertOk()->assertJsonPath('data.0.ticket_number', '1')->assertJsonPath('data.9.ticket_number', '10');
    }
}
