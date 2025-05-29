import { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Progress } from '@/Components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { AlertCircle, CheckCircle2, XCircle, Loader2 } from 'lucide-react';
import Echo from 'laravel-echo';

export default function BulkUploadStatus({ auth, event, bulkUpload }) {
    const [status, setStatus] = useState(bulkUpload.status);
    const [progress, setProgress] = useState({
        percent: 0,
        processed: bulkUpload.processed_records || 0,
        failed: bulkUpload.failed_records || 0,
        total: bulkUpload.total_records || 0
    });
    const [message, setMessage] = useState('');
    const [errors, setErrors] = useState([]);
    const [isSubscribed, setIsSubscribed] = useState(false);

    useEffect(() => {
        // Subscribe to the private channel for this bulk upload
        const echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS: true
        });

        const channel = echo.private(`bulk-upload.${bulkUpload.id}`);

        channel.listen('.bulk-upload.status', (e) => {
            console.log('Received status update:', e);
            setStatus(e.status);
            setProgress(e.progress);
            setMessage(e.message);
            if (e.errors && e.errors.length > 0) {
                setErrors(prev => [...prev, ...e.errors]);
            }
        });

        setIsSubscribed(true);

        return () => {
            channel.stopListening('.bulk-upload.status');
            echo.leave(`bulk-upload.${bulkUpload.id}`);
        };
    }, [bulkUpload.id]);

    const getStatusIcon = () => {
        switch (status) {
            case 'completed':
                return <CheckCircle2 className="h-6 w-6 text-green-500" />;
            case 'failed':
                return <XCircle className="h-6 w-6 text-red-500" />;
            case 'processing':
                return <Loader2 className="h-6 w-6 text-blue-500 animate-spin" />;
            default:
                return <AlertCircle className="h-6 w-6 text-gray-500" />;
        }
    };

    const getStatusColor = () => {
        switch (status) {
            case 'completed':
                return 'bg-green-50 text-green-800 border-green-200';
            case 'failed':
                return 'bg-red-50 text-red-800 border-red-200';
            case 'processing':
                return 'bg-blue-50 text-blue-800 border-blue-200';
            default:
                return 'bg-gray-50 text-gray-800 border-gray-200';
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Bulk Upload Status</h2>}
        >
            <Head title="Bulk Upload Status" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Bulk Upload Status</CardTitle>
                                    <CardDescription>
                                        {event.name} - {bulkUpload.filename}
                                    </CardDescription>
                                </div>
                                <div className={`flex items-center gap-2 px-4 py-2 rounded-full ${getStatusColor()}`}>
                                    {getStatusIcon()}
                                    <span className="capitalize">{status}</span>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {message && (
                                <Alert>
                                    <AlertTitle>Status</AlertTitle>
                                    <AlertDescription>{message}</AlertDescription>
                                </Alert>
                            )}

                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span>Progress</span>
                                    <span>{progress.percent}%</span>
                                </div>
                                <Progress value={progress.percent} className="h-2" />
                                <div className="flex justify-between text-sm text-gray-500">
                                    <span>Processed: {progress.processed}</span>
                                    <span>Failed: {progress.failed}</span>
                                    <span>Total: {progress.total}</span>
                                </div>
                            </div>

                            {errors.length > 0 && (
                                <div className="space-y-2">
                                    <h3 className="font-medium text-red-600">Errors</h3>
                                    <div className="max-h-60 overflow-y-auto space-y-2">
                                        {errors.map((error, index) => (
                                            <Alert key={index} variant="destructive">
                                                <AlertCircle className="h-4 w-4" />
                                                <AlertDescription>{error}</AlertDescription>
                                            </Alert>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>

                        <CardFooter className="flex justify-between">
                            <Button
                                variant="outline"
                                onClick={() => window.history.back()}
                            >
                                Back
                            </Button>
                            {status === 'completed' && (
                                <Button
                                    onClick={() => window.location.href = route('events.attendees.index', event)}
                                >
                                    View Attendees
                                </Button>
                            )}
                        </CardFooter>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 