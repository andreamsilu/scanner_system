<?php

namespace App\Services;

use App\Jobs\SendSingleSms;
use App\Models\Attendee;
use App\Models\BulkUpload;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkAttendeeImporter
{
    public function import(BulkUpload $upload, string $path): void
    {
        if ($upload->status === 'completed') {
            return;
        }
        $errors = [];
        $processed = 0;
        $total = 0;
        $upload->update(['status' => 'processing']);
        foreach ($this->records($path) as $record) {
            $total++;
            if ($total > 10000) {
                throw new \RuntimeException('Uploads may contain at most 10,000 rows.');
            }
            try {
                $record = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $record);
                $record['ticket_type'] = strtolower((string) ($record['ticket_type'] ?? ''));
                $record['email'] = ($record['email'] ?? '') ?: null;
                $valid = Validator::make($record, [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'email' => 'nullable|email|max:255',
                    'position' => 'nullable|string|max:255',
                    'ticket_type' => 'required|in:single,double,regular,vip',
                ])->validate();
                DB::transaction(function () use ($valid, $upload) {
                    $event = Event::whereKey($upload->event_id)->lockForUpdate()->firstOrFail();
                    $existing = $event->attendees()->where('phone', $valid['phone'])->first();
                    if ($existing) {
                        // A retry resumes completed rows without issuing another ticket or SMS.
                        if ((int) $existing->bulk_upload_id === $upload->id) {
                            return;
                        }
                        throw new \RuntimeException('Phone number already registered for this event.');
                    }
                    $attendee = Attendee::create(array_merge($valid, [
                        'event_id' => $event->id, 'bulk_upload_id' => $upload->id,
                        'registration_status' => 'unchecked_in',
                    ]));
                    $ticket = Ticket::create([
                        'event_id' => $event->id, 'attendee_id' => $attendee->id,
                        'type' => $valid['ticket_type'], 'status' => 'active',
                    ]);
                    SendSingleSms::dispatch(
                        $attendee->phone,
                        "{$event->name}: Ticket #{$ticket->ticket_number} (".ucfirst($ticket->type).'). '.route('tickets.verify', $attendee->token),
                        'invitation',
                        ['event_id' => $event->id, 'attendee_id' => $attendee->id, 'ticket_id' => $ticket->id]
                    )->afterCommit();
                }, 3);
                $processed++;
            } catch (ValidationException $exception) {
                $errors[] = ['row' => $total + 1, 'error' => implode(' ', $exception->validator->errors()->all())];
            } catch (QueryException $exception) {
                // Database outages are retryable; do not silently turn them into row errors.
                throw $exception;
            } catch (\RuntimeException $exception) {
                $errors[] = ['row' => $total + 1, 'error' => $exception->getMessage()];
            }
            $upload->update(['total_records' => $total, 'processed_records' => $processed, 'failed_records' => count($errors)]);
        }
        $upload->update([
            'status' => $errors ? 'failed' : 'completed', 'error_log' => json_encode($errors),
            'error_message' => $errors ? 'Some rows could not be imported. See row errors.' : null,
            'completed_at' => now(),
        ]);
        Storage::delete($path);
    }

    private function header(mixed $value): string
    {
        return preg_replace('/\s*\([^)]*\)\s*$/', '', strtolower(trim((string) $value)));
    }

    private function records(string $path): \Generator
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv') {
            $reader = Reader::createFromPath(Storage::path($path), 'r');
            $reader->setHeaderOffset(0);
            foreach ($reader->getRecords() as $record) {
                yield array_combine(array_map($this->header(...), array_keys($record)), array_values($record));
            }

            return;
        }
        $reader = IOFactory::createReaderForFile(Storage::path($path));
        $reader->setReadDataOnly(true);
        $book = $reader->load(Storage::path($path));
        try {
            $sheet = $book->getActiveSheet();
            $headers = null;
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = (string) $cell->getValue();
                }
                if ($headers === null) {
                    $headers = array_map($this->header(...), $cells);

                    continue;
                }
                if (array_filter($cells, fn ($value) => trim($value) !== '')) {
                    yield array_combine($headers, array_slice(array_pad($cells, count($headers), ''), 0, count($headers)));
                }
            }
        } finally {
            $book->disconnectWorksheets();
        }
    }
}
