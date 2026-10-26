import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const workspace = document.getElementById('scannerWorkspace');
if (workspace) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const startButton = document.getElementById('startCamera');
    const stopButton = document.getElementById('stopCamera');
    const status = document.getElementById('scannerStatus');
    const placeholder = document.getElementById('cameraPlaceholder');
    const result = document.getElementById('scannerResult');
    const form = document.getElementById('manualEntryForm');
    const manualButton = document.getElementById('manualSubmit');
    let camera = null;
    let scanning = false;
    let changingCamera = false;
    let admitting = false;

    function feedback(heading, message, tone = 'info') {
        result.hidden = false;
        result.dataset.status = tone;
        document.getElementById('resultHeading').textContent = heading;
        document.getElementById('resultMessage').textContent = message;
    }

    async function scannerState(endpoint, keepalive = false) {
        const response = await fetch(endpoint, {
            method: 'POST', keepalive,
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Could not update scanner status.');
    }

    async function admit(token) {
        if (admitting) return;
        admitting = true;
        manualButton.disabled = true;
        manualButton.textContent = 'Checking…';
        try {
            const data = await window.submitAdmission(token, workspace.dataset.validate, csrf);
            if (!data) return;
            if (data.code === 'CANCELLED') {
                feedback('Admission cancelled', data.message);
            } else if (data.success) {
                feedback('Entry confirmed', `${data.attendee.name} · Ticket #${data.ticket.ticket_number}. ${data.message}`, 'success');
                document.getElementById('manualToken').value = '';
                appendAdmission(data);
            } else {
                feedback('Entry not confirmed', data.message, data.code === 'TICKET_USED' ? 'warning' : 'danger');
            }
        } catch {
            feedback('Unable to confirm entry', 'Check your connection and ticket status before trying again.', 'danger');
        } finally {
            admitting = false;
            manualButton.disabled = false;
            manualButton.textContent = 'Check ticket';
        }
    }

    function appendAdmission(data) {
        const list = document.getElementById('recentAdmissions');
        document.getElementById('recentEmpty')?.remove();
        const row = document.createElement('div');
        row.className = 'recent-admission';
        const name = document.createElement('h3');
        name.textContent = `${data.attendee.name} · #${data.ticket.ticket_number}`;
        const detail = document.createElement('p');
        detail.textContent = `Just now · ${data.message}`;
        row.append(name, detail);
        list.prepend(row);
        while (list.children.length > 10) list.lastElementChild.remove();
    }

    startButton.addEventListener('click', async () => {
        if (changingCamera || scanning) return;
        changingCamera = true;
        startButton.disabled = true;
        startButton.textContent = 'Starting camera…';
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('Use a secure HTTPS connection to enable the camera. You can also enter a ticket number below.');
            camera ??= new Html5Qrcode('reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
            placeholder.hidden = true;
            await camera.start({ facingMode: 'environment' }, {
                fps: 10,
                qrbox: (width, height) => ({ width: Math.min(240, width * .75, height * .75), height: Math.min(240, width * .75, height * .75) }),
            }, (text) => { if (scanning) admit(text); }, () => {});
            scanning = true;
            await scannerState(workspace.dataset.start);
            startButton.hidden = true;
            stopButton.hidden = false;
            status.textContent = 'Camera is live';
            feedback('Ready to scan', 'Hold a ticket QR code inside the frame.');
        } catch (error) {
            if (scanning) await camera.stop().catch(() => {});
            scanning = false;
            placeholder.hidden = false;
            const message = error.name === 'NotAllowedError' ? 'Allow camera access in your browser, or enter the ticket number below.'
                : error.name === 'NotFoundError' ? 'No camera found. Enter the ticket number below.'
                : error.message || 'Could not start the camera. Check camera access or enter the ticket number below.';
            feedback('Camera unavailable', message, 'warning');
        } finally {
            changingCamera = false;
            startButton.disabled = false;
            startButton.textContent = 'Start camera';
        }
    });

    stopButton.addEventListener('click', async () => {
        if (changingCamera || !scanning) return;
        changingCamera = true;
        stopButton.disabled = true;
        try {
            await camera.stop();
            scanning = false;
            placeholder.hidden = false;
            startButton.hidden = false;
            stopButton.hidden = true;
            status.textContent = 'Camera paused';
            await scannerState(workspace.dataset.stop);
            feedback('Camera paused', 'Start the camera whenever you are ready. Manual entry is still available.');
        } catch {
            feedback('Unable to pause scanner', 'Check your connection and try again.', 'warning');
        } finally {
            changingCamera = false;
            stopButton.disabled = false;
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const token = document.getElementById('manualToken').value.trim();
        if (token) admit(token);
    });

    window.addEventListener('pagehide', () => {
        if (scanning) {
            camera.stop().catch(() => {});
            scannerState(workspace.dataset.stop, true).catch(() => {});
        }
    });
}
