// A small notice, shown when someone plays content that is hosted outside Europe (a YouTube video, say).
// "Got it" is remembered in this browser only (localStorage): no account, no cookie, nothing sent anywhere.

export const KEY_PREFIX = 'amural:external-notice:';

/**
 * Where the choice is kept. Browsers can refuse localStorage (private windows, blocked site data), so
 * reading it is wrapped: when it is unavailable the notice simply shows each time.
 */
export function browserStorage(win = globalThis) {
    try {
        return win.localStorage ?? null;
    } catch {
        return null;
    }
}

/**
 * Remembers whether the notice for one provider ("youtube") has been dismissed.
 */
export function createNotice(storage, provider) {
    const key = KEY_PREFIX + provider;

    return {
        dismissed() {
            try {
                return storage?.getItem(key) === '1';
            } catch {
                return false;
            }
        },

        dismiss() {
            try {
                storage?.setItem(key, '1');
            } catch {
                // Not remembered; the notice will come back next time, which is harmless.
            }
        },
    };
}

/**
 * The Alpine component on a video: `play()` starts it and shows the notice unless it was dismissed before.
 */
export function externalMedia(provider) {
    const notice = createNotice(browserStorage(), provider);

    return {
        playing: false,
        notice: false,

        play() {
            this.playing = true;
            this.notice = ! notice.dismissed();
        },

        dismissNotice() {
            notice.dismiss();
            this.notice = false;
        },
    };
}
