import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


// // Alpine.jsがすでに読み込まれているか確認
// if (typeof window.Alpine === 'undefined') {
//     import Alpine from 'alpinejs';
//     window.Alpine = Alpine;
//     Alpine.start();
// }