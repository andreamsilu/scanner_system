import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Progress } from '@/Components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { AlertCircle, CheckCircle2, XCircle, Loader2 } from 'lucide-react';
import axios from 'axios';

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

    useEffect(() => {
        let pollInterval;

        const pollStatus = async () => {
            try {
                const response = await axios.get(route('events.attendees.bulk-upload.status.get', {
                    event: event.id,
                    bulkUpload: bulkUpload.id
                }));

                const data = response.data;
                setStatus(data.status);
                setProgress(data.progress);
                setMessage(data.message);
                if (data.errors && data.errors.length > 0) {
                    setErrors(data.errors);
                }

                // Stop polling if upload is complete or failed
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollInterval);
                }
            } catch (error) {
                console.error('Error polling status:', error);
                clearInterval(pollInterval);
            }
        };

        // Start polling if still processing
        if (status === 'processing') {
            pollStatus(); // Initial poll
            pollInterval = setInterval(pollStatus, 2000); // Poll every 2 seconds
        }

        return () => {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
        };
    }, [event.id, bulkUpload.id, status]);

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
                                    onClick={() => router.visit(route('events.attendees.index', event))}
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