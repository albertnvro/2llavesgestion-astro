let initialized = false;

export function initHeaderMenu() {
  if (initialized) return;
  initialized = true;

  const mobileMenus = document.querySelectorAll<HTMLDetailsElement>(".dl-mobile-menu");

  mobileMenus.forEach((menu) => {
    menu.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        menu.removeAttribute("open");
      });
    });
  });

  document.addEventListener("click", (event) => {
    mobileMenus.forEach((menu) => {
      if (!menu.contains(event.target as Node)) {
        menu.removeAttribute("open");
      }
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    mobileMenus.forEach((menu) => {
      menu.removeAttribute("open");
    });
  });
}
