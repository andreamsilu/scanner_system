use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('recipient');
            $table->string('sender_id');
            $table->text('message');
            $table->string('status')->default('sent');
            $table->string('provider_status')->nullable();
            $table->string('provider_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_status_check')->nullable();
            $table->integer('status_check_count')->default(0);
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['recipient', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
}; 