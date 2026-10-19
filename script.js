/* ============================================================
   script.js — Aura Wellness Spa
   Navigation mobile uniquement.
   Aucune interception du formulaire de réservation :
   le formulaire est traité côté serveur par
   traitement_reservation.php → confirmation.php
   ============================================================ */

const navToggle = document.querySelector(".nav-toggle");
const mainNav   = document.querySelector(".main-nav");

if (navToggle && mainNav) {
  const closeMenu = () => {
    mainNav.classList.remove("is-open");
    navToggle.setAttribute("aria-expanded", "false");
  };

  navToggle.addEventListener("click", (event) => {
    event.stopPropagation();
    const isOpen = mainNav.classList.toggle("is-open");
    navToggle.setAttribute("aria-expanded", String(isOpen));
  });

  mainNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });

  document.addEventListener("click", (event) => {
    if (!mainNav.contains(event.target) && !navToggle.contains(event.target)) {
      closeMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenu();
    }
  });
}