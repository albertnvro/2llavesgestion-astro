import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

let initialized = false;

export function initAnimations() {
  if (initialized) return;
  initialized = true;

  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  if (prefersReducedMotion) return;

  gsap.registerPlugin(ScrollTrigger);

  gsap.config({
    nullTargetWarn: false,
  });

  const heroTimeline = gsap.timeline({
    defaults: {
      ease: "power3.out",
    },
  });

  heroTimeline
    .from(".site-header", {
      y: -24,
      opacity: 0,
      duration: 0.7,
    })
    .from(
      ".hero .eyebrow",
      {
        y: 20,
        opacity: 0,
        duration: 0.55,
      },
      "-=0.25"
    )
    .from(
      ".hero h1",
      {
        y: 64,
        opacity: 0,
        duration: 0.95,
        ease: "power4.out",
      },
      "-=0.15"
    )
    .from(
      ".hero-lead",
      {
        y: 28,
        opacity: 0,
        duration: 0.7,
      },
      "-=0.35"
    )
    .from(
      ".hero-actions .btn",
      {
        y: 22,
        opacity: 0,
        duration: 0.6,
        stagger: 0.08,
      },
      "-=0.35"
    )
    .from(
      ".pills span",
      {
        y: 16,
        opacity: 0,
        duration: 0.5,
        stagger: 0.05,
      },
      "-=0.25"
    )
    .from(
      ".control-card",
      {
        x: 42,
        y: 24,
        rotate: 2,
        opacity: 0,
        duration: 0.85,
        ease: "power4.out",
      },
      "-=0.55"
    );

  gsap.to(".control-card", {
    y: -14,
    duration: 4.2,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
  });

  gsap.to(".story-orbit", {
    rotate: 360,
    duration: 34,
    repeat: -1,
    ease: "none",
  });

  gsap.to(".story-visual-card", {
    y: -8,
    duration: 3.4,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    stagger: 0.22,
  });

  const storySteps = gsap.utils.toArray<HTMLElement>(".story-step");
  const storyCards = gsap.utils.toArray<HTMLElement>(".story-visual-card");
  const progress = document.querySelector<HTMLElement>(".story-progress-inner");

  function setStoryStep(index: number) {
    storySteps.forEach((step, stepIndex) => {
      step.classList.toggle("is-active", stepIndex === index);
    });

    storyCards.forEach((card, cardIndex) => {
      card.classList.toggle(
        "is-active",
        cardIndex === Math.min(index, storyCards.length - 1)
      );
    });

    if (progress && storySteps.length > 0) {
      gsap.to(progress, {
        scaleY: (index + 1) / storySteps.length,
        duration: 0.35,
        ease: "power2.out",
      });
    }
  }

  storySteps.forEach((step, index) => {
    ScrollTrigger.create({
      trigger: step,
      start: "top center",
      end: "bottom center",
      onEnter: () => setStoryStep(index),
      onEnterBack: () => setStoryStep(index),
    });
  });

  const revealElements = gsap.utils.toArray<HTMLElement>(
    ".section-heading, .feature-card, .service-card, .process-card, .contact-copy, .contact-form, .story-heading, .story-stage, .story-step"
  );

  revealElements.forEach((element, index) => {
    gsap.from(element, {
      scrollTrigger: {
        trigger: element,
        start: "top 86%",
        once: true,
      },
      y: 42,
      opacity: 0,
      duration: 0.75,
      delay: Math.min((index % 4) * 0.05, 0.15),
      ease: "power3.out",
    });
  });

  const buttons = document.querySelectorAll<HTMLElement>(
    ".btn, .service-card a, .contact-form button"
  );

  buttons.forEach((button) => {
    if (button.dataset.gsapBound === "true") return;
    button.dataset.gsapBound = "true";

    button.addEventListener("mouseenter", () => {
      gsap.to(button, {
        scale: 1.035,
        duration: 0.22,
        ease: "power2.out",
      });
    });

    button.addEventListener("mouseleave", () => {
      gsap.to(button, {
        scale: 1,
        x: 0,
        y: 0,
        duration: 0.28,
        ease: "power2.out",
      });
    });

    button.addEventListener("mousemove", (event) => {
      const rect = button.getBoundingClientRect();

      gsap.to(button, {
        x: (event.clientX - rect.left - rect.width / 2) * 0.06,
        y: (event.clientY - rect.top - rect.height / 2) * 0.08,
        duration: 0.2,
        ease: "power2.out",
      });
    });
  });

  const cards = document.querySelectorAll<HTMLElement>(
    ".feature-card, .service-card, .process-card"
  );

  cards.forEach((card) => {
    if (card.dataset.tiltBound === "true") return;
    card.dataset.tiltBound = "true";

    card.addEventListener("mousemove", (event) => {
      const rect = card.getBoundingClientRect();

      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;

      gsap.to(card, {
        rotateX: -y * 3,
        rotateY: x * 4,
        y: -4,
        duration: 0.25,
        ease: "power2.out",
      });
    });

    card.addEventListener("mouseleave", () => {
      gsap.to(card, {
        rotateX: 0,
        rotateY: 0,
        y: 0,
        duration: 0.35,
        ease: "power2.out",
      });
    });
  });

  setStoryStep(0);
}
