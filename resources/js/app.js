import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Pages bring their own components only when they need them: /flopzer, and blog posts with an embedded calculator.
const pending = [];

if (document.querySelector('[x-data^="flopzer"]')) {
    pending.push(import('./flopzer/register.js').then((module) => module.registerFlopzer(Alpine)));
}

if (document.querySelector('[data-calculator]')) {
    pending.push(import('./calculators/register.js').then((module) => module.registerCalculators(Alpine)));
}

Promise.allSettled(pending).finally(() => Alpine.start());
