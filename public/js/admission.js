(function () {
    let pending = false;
    let resumeAt = 0;

    function chooseEntries(ticket, attendee) {
        const dialog = document.getElementById('entryChoiceDialog');
        document.getElementById('entryChoiceName').textContent = attendee.name;
        document.getElementById('entryChoiceRemaining').textContent =
            `Ticket #${ticket.ticket_number}: ${ticket.remaining_entries} entries remaining.`;
        const doubleButton = document.getElementById('doubleEntryButton');
        doubleButton.disabled = ticket.remaining_entries < 2;
        return new Promise(resolve => {
            let chosen = null;
            document.getElementById('singleEntryButton').onclick = () => { chosen = 1; dialog.close(); };
            doubleButton.onclick = () => { chosen = 2; dialog.close(); };
            document.getElementById('cancelEntryButton').onclick = () => dialog.close();
            dialog.addEventListener('close', () => resolve(chosen), { once: true });
            dialog.showModal();
            document.getElementById('singleEntryButton').focus();
        });
    }

    window.submitAdmission = async function (token, endpoint, csrfToken) {
        if (pending || Date.now() < resumeAt) return null;
        pending = true;
        const send = async payload => {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok && !data.message) throw new Error('Unable to validate this ticket.');
            return data;
        };
        try {
            let result = await send({ token });
            if (result.code === 'ENTRY_CHOICE_REQUIRED') {
                const entries = await chooseEntries(result.ticket, result.attendee);
                if (entries === null) return { success: false, code: 'CANCELLED', message: 'Cancelled. No admission recorded.' };
                result = await send({ token, entries, expected_entries_used: result.ticket.entries_used });
            }
            return result;
        } finally {
            pending = false;
            // Ignore repeated camera frames and quick double clicks.
            resumeAt = Date.now() + 2500;
        }
    };
})();
