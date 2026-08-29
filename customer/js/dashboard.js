// ===============================
// PUSTAKMANDI CUSTOMER DASHBOARD
// dashboard.js
// ===============================
// Rewritten to match the actual dashboard.php markup:
// a top navbar (.top-bar / .navbar), no sidebar.
// ===============================

document.addEventListener("DOMContentLoaded", () => {
  // ===============================
  // Live Clock (added into the top bar)
  // ===============================

  const topBar = document.querySelector(".top-bar");

  if (topBar) {
    const clock = document.createElement("div");
    clock.className = "clock";
    topBar.appendChild(clock);

    function updateClock() {
      clock.innerText = new Date().toLocaleTimeString();
    }

    setInterval(updateClock, 1000);
    updateClock();
  }

  // ===============================
  // Welcome Message
  // ===============================

  const welcomeHeading = document.querySelector(".welcome h1");
  const userNameEl = document.querySelector(".profile-info strong");

  if (welcomeHeading && userNameEl) {
    const hour = new Date().getHours();
    let greeting = "Welcome";

    if (hour < 12) {
      greeting = "Good Morning ☀️";
    } else if (hour < 17) {
      greeting = "Good Afternoon 🌞";
    } else {
      greeting = "Good Evening 🌙";
    }

    welcomeHeading.innerHTML = greeting + ", " + userNameEl.innerText + "!";
  }

  // ===============================
  // Card Animation
  // (category-card, action-card, product-card all exist on this page)
  // ===============================

  const cards = document.querySelectorAll(
    ".category-card, .action-card, .product-card",
  );

  cards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";

    setTimeout(() => {
      card.style.transition = ".5s";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });

  // ===============================
  // Mobile Menu Toggle
  // (style.css already has .menu-toggle / .nav-links.show rules —
  // this just adds the button since dashboard.php doesn't have one yet)
  // ===============================

  const navbar = document.querySelector(".navbar");
  const navLinks = document.querySelector(".nav-links");

  if (navbar && navLinks && !document.querySelector(".menu-toggle")) {
    const menuBtn = document.createElement("button");
    menuBtn.className = "menu-toggle";
    menuBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
    menuBtn.style.border = "none";
    menuBtn.style.background = "none";
    menuBtn.style.cursor = "pointer";
    navbar.appendChild(menuBtn);

    menuBtn.addEventListener("click", () => {
      navLinks.classList.toggle("show");
    });
  }

  console.log("Customer Dashboard Loaded Successfully.");
});
