import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

function scanner(responses) {
    const elements = new Map();
    for (const id of ['entryChoiceDialog', 'entryChoiceName', 'entryChoiceRemaining', 'singleEntryButton', 'doubleEntryButton', 'cancelEntryButton']) {
        elements.set(id, { disabled: false, focus() {}, addEventListener(name, callback) { this.onclose = callback; }, showModal() {}, close() { this.onclose(); } });
    }
    const requests = [];
    const context = { window: {}, document: { getElementById: id => elements.get(id) }, Date,
        fetch: async (url, options) => { requests.push(JSON.parse(options.body)); return { ok: true, json: async () => responses.shift() }; } };
    vm.runInNewContext(fs.readFileSync(new URL('../../public/js/admission.js', import.meta.url), 'utf8'), context);
    return { submit: context.window.submitAdmission, elements, requests };
}

const choice = remaining => ({ success: false, code: 'ENTRY_CHOICE_REQUIRED', attendee: { name: '<Guest>' }, ticket: { ticket_number: '1', entries_used: 2 - remaining, remaining_entries: remaining } });
const tick = () => new Promise(resolve => setImmediate(resolve));

test('a double ticket prompts before admission and single entry sends the observed version', async () => {
    const s = scanner([choice(2), { success: true }]);
    const pending = s.submit('token', '/scan', 'csrf');
    await tick();
    assert.equal(s.requests.length, 1);
    assert.equal(s.elements.get('entryChoiceName').textContent, '<Guest>');
    s.elements.get('singleEntryButton').onclick();
    assert.equal((await pending).success, true);
    assert.equal(s.requests[1].entries, 1);
    assert.equal(s.requests[1].expected_entries_used, 0);
});

test('double entry submits two admissions', async () => {
    const s = scanner([choice(2), { success: true }]);
    const pending = s.submit('token', '/scan', 'csrf');
    await tick();
    s.elements.get('doubleEntryButton').onclick();
    await pending;
    assert.equal(s.requests[1].entries, 2);
});

test('only single entry is available when one admission remains', async () => {
    const s = scanner([choice(1), { success: true }]);
    const pending = s.submit('token', '/scan', 'csrf');
    await tick();
    assert.equal(s.elements.get('doubleEntryButton').disabled, true);
    s.elements.get('singleEntryButton').onclick();
    await pending;
    assert.equal(s.requests[1].expected_entries_used, 1);
});

test('cancelling and repeated camera frames do not consume admissions', async () => {
    const s = scanner([choice(2)]);
    const pending = s.submit('token', '/scan', 'csrf');
    await tick();
    assert.equal(await s.submit('token', '/scan', 'csrf'), null);
    s.elements.get('cancelEntryButton').onclick();
    assert.equal((await pending).code, 'CANCELLED');
    assert.equal(s.requests.length, 1);
});
