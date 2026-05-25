let initialized = false;

export function initHeaderMenu() {
  if (initialized) return;
  initialized = true;

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    const active = document.activeElement;

    if (active instanceof HTMLElement) {
      active.blur();
    }
  });
}
