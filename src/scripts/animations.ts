import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

let initialized = false;

export function initAnimations() {
  if (initialized) return;
  initialized = true;

  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) return;

  gsap.registerPlugin(ScrollTrigger);
  gsap.config({ nullTargetWarn: false });

  const hero = gsap.timeline({ defaults: { ease: "power3.out" } });

  hero
    .from(".header-shell", {
      y: -18,
      opacity: 0,
      duration: 0.55,
    })
    .from(".hero .eyebrow", {
      y: 18,
      opacity: 0,
      duration: 0.45,
    })
    .from(
      ".hero h1",
      {
        y: 44,
        opacity: 0,
        duration: 0.8,
      },
      "-=0.2"
    )
    .from(
      ".hero-lead, .hero-search, .hero-proof",
      {
        y: 24,
        opacity: 0,
        duration: 0.58,
        stagger: 0.08,
      },
      "-=0.35"
    )
    .from(
      ".image-card-main",
      {
        y: 26,
        opacity: 0,
        rotate: -3,
        duration: 0.72,
      },
      "-=0.45"
    )
    .from(
      ".float-card, .mini-form-card",
      {
        y: 22,
        opacity: 0,
        duration: 0.48,
        stagger: 0.09,
      },
      "-=0.25"
    )
    .from(
      ".brand-object, .brand-pill",
      {
        opacity: 0,
        scale: 0.86,
        duration: 0.8,
        stagger: 0.08,
      },
      "-=0.7"
    );

  gsap.to(".image-card-main", {
    y: -10,
    duration: 5,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
  });

  gsap.to(".float-card, .mini-form-card", {
    y: -7,
    duration: 3.8,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    stagger: 0.2,
  });

  gsap.to(".brand-object", {
    y: -22,
    rotate: "+=8",
    duration: 7,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    stagger: 0.45,
  });

  gsap.to(".brand-pill", {
    x: 42,
    duration: 7.2,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    stagger: 0.5,
  });

  const revealItems = gsap.utils.toArray<HTMLElement>(".reveal");

  revealItems.forEach((item, index) => {
    gsap.from(item, {
      scrollTrigger: {
        trigger: item,
        start: "top 86%",
        once: true,
      },
      y: 28,
      opacity: 0,
      duration: 0.62,
      delay: Math.min((index % 3) * 0.04, 0.12),
      ease: "power3.out",
    });
  });

  const timeline = document.querySelector<HTMLElement>(".process-timeline");
  const timelineProgress = document.querySelector<HTMLElement>(".timeline-progress");
  const timelineSteps = gsap.utils.toArray<HTMLElement>(".timeline-step");

  if (timeline && timelineProgress && timelineSteps.length > 0) {
    gsap.fromTo(
      timelineProgress,
      { scaleY: 0 },
      {
        scaleY: 1,
        ease: "none",
        scrollTrigger: {
          trigger: timeline,
          start: "top 70%",
          end: "bottom 46%",
          scrub: true,
        },
      }
    );

    function setActiveStep(index: number) {
      timelineSteps.forEach((step, stepIndex) => {
        step.classList.toggle("is-active", stepIndex === index);
      });
    }

    timelineSteps.forEach((step, index) => {
      ScrollTrigger.create({
        trigger: step,
        start: "top 58%",
        end: "bottom 58%",
        onEnter: () => setActiveStep(index),
        onEnterBack: () => setActiveStep(index),
      });
    });

    setActiveStep(0);
  }

  const buttons = document.querySelectorAll<HTMLElement>("a, button");

  buttons.forEach((button) => {
    button.addEventListener("mouseenter", () => {
      gsap.to(button, {
        y: -2,
        duration: 0.18,
        ease: "power2.out",
      });
    });

    button.addEventListener("mouseleave", () => {
      gsap.to(button, {
        y: 0,
        duration: 0.22,
        ease: "power2.out",
      });
    });
  });
}
