import { europeMap } from './europe-map.js';
import { externalMedia } from './external-notice.js';

// Alpine ships with Livewire; components are registered when it starts.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('europeMap', europeMap);
    window.Alpine.data('externalMedia', externalMedia);
});
