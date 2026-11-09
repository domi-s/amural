import assert from 'node:assert/strict';
import { test } from 'node:test';

import { browserStorage, createNotice, KEY_PREFIX } from './external-notice.js';

const fakeStorage = () => {
    const data = new Map();

    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, String(value)),
        data,
    };
};

test('shows the notice until it is dismissed, then never again', () => {
    const storage = fakeStorage();
    const notice = createNotice(storage, 'youtube');

    assert.equal(notice.dismissed(), false);

    notice.dismiss();

    assert.equal(notice.dismissed(), true);
    assert.equal(storage.data.get(KEY_PREFIX + 'youtube'), '1');
});

test('remembers each provider separately', () => {
    const storage = fakeStorage();

    createNotice(storage, 'youtube').dismiss();

    assert.equal(createNotice(storage, 'youtube').dismissed(), true);
    assert.equal(createNotice(storage, 'vimeo').dismissed(), false);
});

test('keeps the choice for a later visit', () => {
    const storage = fakeStorage();

    createNotice(storage, 'youtube').dismiss();

    assert.equal(createNotice(storage, 'youtube').dismissed(), true);
});

test('shows the notice every time when there is no storage', () => {
    const notice = createNotice(null, 'youtube');

    notice.dismiss();

    assert.equal(notice.dismissed(), false);
});

test('copes with storage that throws on read or write', () => {
    const broken = {
        getItem() {
            throw new Error('blocked');
        },
        setItem() {
            throw new Error('full');
        },
    };
    const notice = createNotice(broken, 'youtube');

    assert.doesNotThrow(() => notice.dismiss());
    assert.equal(notice.dismissed(), false);
});

test('copes with a browser that refuses localStorage altogether', () => {
    const win = {
        get localStorage() {
            throw new Error('SecurityError');
        },
    };

    assert.equal(browserStorage(win), null);
    assert.equal(browserStorage({}), null);
});
