// Scroll-driven 3D "journey" for the group gateway: the camera travels forward on the
// Z axis and every business unit is a layer waiting deeper in space. The server renders
// plain stacked sections; this script opts into the 3D scene by adding .is-3d.
const DEPTH = 1700;
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

function initJourney(journey) {
    const stage = journey.querySelector('.journey-stage');
    const layers = [...journey.querySelectorAll('.journey-layer')];
    const links = [...journey.querySelectorAll('[data-journey-jump]')];
    const counter = journey.querySelector('[data-journey-count]');
    const bar = journey.querySelector('[data-journey-bar]');
    const lastIndex = Math.max(1, layers.length - 1);

    let current = 0;
    let activeIndex = -1;
    let pending = false;

    const scrollSpan = () => journey.offsetHeight - stage.offsetHeight;
    const progress = () => clamp(-journey.getBoundingClientRect().top / scrollSpan(), 0, 1);

    // Each unit "holds" in focus for the middle of its scroll slot.
    function focusAt(p) {
        const x = p * lastIndex;
        const slot = Math.floor(x);
        const fraction = x - slot;
        const t = fraction < .3 ? 0 : fraction > .7 ? 1 : (fraction - .3) / .4;

        return Math.min(layers.length - 1, slot + t * t * (3 - 2 * t));
    }

    function render() {
        const cameraZ = current * DEPTH;

        layers.forEach((layer, i) => {
            const z = cameraZ - i * DEPTH; // 0 = in focus, <0 = still ahead, >0 = passed
            const opacity = z < 0 ? Math.max(0, 1 + z / (DEPTH * 1.15)) : Math.max(0, 1 - z / 520);
            const rotation = clamp(z / DEPTH, -1, 1) * (i % 2 ? -18 : 18);
            const active = Math.abs(z) < DEPTH * .3;

            layer.style.transform = `translateZ(${z}px) rotateY(${rotation}deg)`;
            layer.style.opacity = opacity.toFixed(3);
            layer.style.visibility = opacity < .01 ? 'hidden' : 'visible';
            layer.classList.toggle('is-active', active);
            layer.inert = !active;
        });

        const index = Math.round(current);
        if (index !== activeIndex) {
            activeIndex = index;
            journey.style.setProperty('--tint', layers[index].style.getPropertyValue('--c'));
            links.forEach((link, i) => link.classList.toggle('is-current', i === index));
            if (counter) counter.textContent = String(index + 1).padStart(2, '0');
        }

        const rect = journey.getBoundingClientRect();
        journey.classList.toggle('is-visible', rect.top < innerHeight * .5 && rect.bottom > innerHeight * .5);
        if (bar) bar.style.width = `${progress() * 100}%`;
    }

    // Ease the camera toward the scroll position, and stop looping once it has settled.
    function frame() {
        pending = false;
        const target = focusAt(progress());
        current += (target - current) * .12;
        if (Math.abs(target - current) < .0005) current = target;
        render();
        if (current !== target) schedule();
    }

    function schedule() {
        if (pending) return;
        pending = true;
        requestAnimationFrame(frame);
    }

    links.forEach((link, i) => link.addEventListener('click', event => {
        event.preventDefault();
        const top = journey.getBoundingClientRect().top + scrollY + scrollSpan() * (i / lastIndex);
        scrollTo({ top, behavior: 'smooth' });
    }));

    journey.classList.add('is-3d');
    current = focusAt(progress());
    render();
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule);
}

if (!reducedMotion.matches) {
    document.querySelectorAll('[data-journey]').forEach(initJourney);
}
