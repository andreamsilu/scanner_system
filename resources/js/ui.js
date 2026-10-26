document.addEventListener('alpine:init', () => {
    Alpine.data('ticketInvitation', (endpoint) => ({
        ticket: null,
        sending: false,
        message: '',
        failed: false,
        open(ticket) {
            this.ticket = ticket;
            this.message = '';
            this.failed = false;
            this.$refs.dialog.showModal();
        },
        async send() {
            if (this.sending || !this.ticket) return;
            this.sending = true;
            this.message = '';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ticket_id: this.ticket.id, attendee_id: this.ticket.attendeeId }),
                });
                const data = await response.json();
                this.failed = !response.ok || !data.success;
                this.message = data.message || (this.failed ? 'Invitation could not be sent. Please try again.' : 'Invitation sent successfully.');
            } catch {
                this.failed = true;
                this.message = 'Connection lost. Check the invitation status before trying again.';
            } finally {
                this.sending = false;
            }
        },
    }));
});
