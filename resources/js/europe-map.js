/**
 * The map of Europe in the language switcher.
 *
 * The map (public/svg/europe-map.svg) is fetched the first time the Map tab opens. Each served
 * country is a <path class="country"> with its ISO code as the id and its name, local name and
 * languages as data attributes. Choosing a country offers its languages; choosing one of those
 * runs the same switch as the list.
 *
 * The functions that need no browser are exported and tested with `npm test`.
 */

/**
 * Describe a country from its map element (anything with an id and a dataset).
 *
 * @param {{id: string, dataset: Record<string, string|undefined>}} element
 */
export function countryFromElement(element) {
    return {
        code: element.id,
        name: element.dataset.name || element.id,
        local: element.dataset.local || '',
        languages: (element.dataset.langs || '').split(/\s+/).filter(Boolean),
    };
}

/**
 * The content languages to offer for a country: those of its languages we have, without
 * repeats, in the order the map lists them.
 *
 * @param {{languages: string[]}} country
 * @param {Record<string, Array<{code: string}>>} byLanguage
 */
export function localesFor(country, byLanguage) {
    const seen = new Set();
    const offered = [];

    for (const language of country.languages) {
        for (const locale of byLanguage[language] || []) {
            if (!seen.has(locale.code)) {
                seen.add(locale.code);
                offered.push(locale);
            }
        }
    }

    return offered;
}

/**
 * How many of a country's languages we do not have as content languages (Romansh, Luxembourgish).
 */
export function unavailableCount(country, byLanguage) {
    return country.languages.filter((language) => !(byLanguage[language] || []).length).length;
}

/** Does this country speak the given language? */
export function speaks(country, language) {
    return country.languages.includes(language);
}

/** The text shown above the map: the country under the pointer, or what to do. */
export function captionFor(hovered, selected, labels) {
    const country = hovered || selected;

    if (!country) {
        return labels.choose;
    }

    return country.local && country.local !== country.name ? `${country.name} · ${country.local}` : country.name;
}

/**
 * The Alpine component for the Map tab.
 *
 * @param {{mapUrl: string, flagsUrl: string, flags: string[], current: string|null, currentCode: string|null,
 *          languages: Record<string, Array<object>>, labels: Record<string, string>}} config
 */
export function europeMap(config) {
    return {
        config,
        markup: '',
        status: 'idle', // idle, loading, ready, failed
        hovered: null,
        country: null,
        selectedElement: null,

        get caption() {
            return captionFor(this.hovered, this.country, config.labels);
        },

        get locales() {
            return this.country ? localesFor(this.country, config.languages) : [];
        },

        get unavailable() {
            return this.country ? unavailableCount(this.country, config.languages) : 0;
        },

        get hasFlag() {
            return this.country !== null && config.flags.includes(this.country.code);
        },

        async load() {
            if (this.status !== 'idle') {
                return;
            }

            this.status = 'loading';

            try {
                const response = await fetch(config.mapUrl, { credentials: 'same-origin' });

                if (!response.ok) {
                    throw new Error(`Map request failed with ${response.status}`);
                }

                this.markup = await response.text();
                this.status = 'ready';

                await this.$nextTick();
                this.enhance();
            } catch (error) {
                console.error(error);
                this.status = 'failed';
            }
        },

        /** Make every served country reachable and named for keyboards and screen readers. */
        enhance() {
            for (const element of this.$refs.map.querySelectorAll('#served .country')) {
                const country = countryFromElement(element);

                element.setAttribute('tabindex', '0');
                element.setAttribute('role', 'button');
                element.setAttribute(
                    'aria-label',
                    country.local && country.local !== country.name ? `${country.name} · ${country.local}` : country.name,
                );

                if (config.current && speaks(country, config.current)) {
                    element.classList.add('speaks-current');
                }
            }
        },

        countryAt(event) {
            const element = event.target.closest ? event.target.closest('.country') : null;

            return element && this.$refs.map.contains(element) ? element : null;
        },

        hover(event) {
            const element = this.countryAt(event);
            this.hovered = element ? countryFromElement(element) : null;
        },

        choose(event) {
            const element = this.countryAt(event);

            if (!element) {
                return;
            }

            this.selectedElement?.classList.remove('is-selected');
            element.classList.add('is-selected');
            this.selectedElement = element;
            this.country = countryFromElement(element);
        },
    };
}
