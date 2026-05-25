let initialized = false;

export async function initSmoothScroll() {
  if (initialized) return;
  initialized = true;

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  const isFinePointer = window.matchMedia("(pointer: fine)").matches;

  if (prefersReducedMotion || !isFinePointer) {
    document.documentElement.style.scrollBehavior = "smooth";
    return;
  }

  const { default: Lenis } = await import("lenis");

  const lenis = new Lenis({
    duration: 1.12,
    easing: (t: number) => 1 - Math.pow(1 - t, 4),
    smoothWheel: true,
    wheelMultiplier: 0.9,
    touchMultiplier: 1,
  });

  window.__dosllavesLenis = lenis;

  function raf(time: number) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }

  requestAnimationFrame(raf);

  document.querySelectorAll<HTMLAnchorElement>('a[href^="#"], a[href^="/#"]').forEach((link) => {
    if (link.dataset.smoothBound === "true") return;
    link.dataset.smoothBound = "true";

    link.addEventListener("click", (event) => {
      const href = link.getAttribute("href");

      if (!href) return;

      const id = href.startsWith("/#") ? href.replace("/", "") : href;

      if (!id.startsWith("#") || id.length <= 1) return;

      const target = document.querySelector(id);

      if (!target) return;

      event.preventDefault();

      lenis.scrollTo(target, {
        offset: -120,
        duration: 1.1,
      });
    });
  });
}

declare global {
  interface Window {
    __dosllavesLenis?: any;
  }
}
