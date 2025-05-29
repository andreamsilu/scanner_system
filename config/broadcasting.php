'channels' => [
    'private-bulk-upload.{id}' => function ($user, $id) {
        $bulkUpload = \App\Models\BulkUpload::find($id);
        return $bulkUpload && $bulkUpload->user_id === $user->id;
    },
], 