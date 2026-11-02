import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// The /flopzer page brings its own component; every other page loads nothing extra.
const flopzer = document.querySelector('[x-data^="flopzer"]')
    ? import('./flopzer/register.js').then((module) => module.registerFlopzer(Alpine))
    : Promise.resolve();

flopzer.finally(() => Alpine.start());
