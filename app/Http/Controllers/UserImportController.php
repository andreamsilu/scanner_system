<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class UserImportController extends Controller
{
    public function index()
    {
        return view('users.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $csv = Reader::createFromPath($request->file('csv_file')->getPathname());
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();
        $requiredHeaders = ['name', 'email', 'password', 'role'];
        
        // Validate CSV headers
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            return back()->withErrors([
                'csv_file' => 'The CSV file is missing required headers: ' . implode(', ', $missingHeaders)
            ]);
        }

        $records = $csv->getRecords();
        $imported = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            $validator = Validator::make($record, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,user',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                continue;
            }

            try {
                User::create([
                    'name' => $record['name'],
                    'email' => $record['email'],
                    'password' => Hash::make($record['password']),
                    'role' => $record['role'],
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        if ($imported > 0) {
            $message = "Successfully imported {$imported} users.";
            if (!empty($errors)) {
                $message .= " However, there were some errors:";
            }
            session()->flash('success', $message);
        }

        if (!empty($errors)) {
            session()->flash('import_errors', $errors);
        }

        return back();
    }

    public function downloadTemplate()
    {
        $csv = \League\Csv\Writer::createFromString('');
        $csv->insertOne(['name', 'email', 'password', 'role']);
        $csv->insertOne(['John Doe', 'john@example.com', 'password123', 'user']);
        $csv->insertOne(['Admin User', 'admin@example.com', 'adminpass123', 'admin']);

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user-import-template.csv"'
        ]);
    }
} 