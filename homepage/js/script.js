// =======================================
// PUSTAKMANDI
// script.js
// =======================================

// ==========================
// CART COUNTER
// ==========================

let cartCount = 0;

const cartIcon = document.querySelector(".fa-cart-shopping");
const cartButtons = document.querySelectorAll(".cart-btn");

cartButtons.forEach((button) => {
  button.addEventListener("click", () => {
    cartCount++;

    updateCart();

    showToast("Product added to cart!");
  });
});

function updateCart() {
  const cartSpan = document.querySelector(".icons .icon:nth-child(2) span");

  cartSpan.innerText = `Cart (${cartCount})`;
}

// ==========================
// WISHLIST
// ==========================

const wishlistButtons = document.querySelectorAll(".wishlist-btn");

wishlistButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const icon = button.querySelector("i");

    if (icon.classList.contains("fa-regular")) {
      icon.classList.remove("fa-regular");

      icon.classList.add("fa-solid");

      icon.style.color = "#ef4444";

      showToast("Added to Wishlist ❤️");
    } else {
      icon.classList.remove("fa-solid");

      icon.classList.add("fa-regular");

      icon.style.color = "";

      showToast("Removed from Wishlist");
    }
  });
});

// ==========================
// PRODUCT SEARCH
// ==========================

const searchInput = document.querySelector(".search-box input");

const productCards = document.querySelectorAll(".product-card");

searchInput.addEventListener("keyup", () => {
  const value = searchInput.value.toLowerCase();

  productCards.forEach((card) => {
    const title = card.querySelector("h3").innerText.toLowerCase();

    if (title.includes(value)) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
});

// ==========================
// SMOOTH SCROLL
// ==========================

document.querySelectorAll("a[href^='#']").forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();

    const target = document.querySelector(this.getAttribute("href"));

    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
      });
    }
  });
});

// ==========================
// TOAST MESSAGE
// ==========================

function showToast(message) {
  const toast = document.createElement("div");

  toast.innerText = message;

  toast.style.position = "fixed";

  toast.style.top = "25px";

  toast.style.right = "25px";

  toast.style.background = "#2563eb";

  toast.style.color = "white";

  toast.style.padding = "15px 25px";

  toast.style.borderRadius = "10px";

  toast.style.fontWeight = "600";

  toast.style.boxShadow = "0 10px 20px rgba(0,0,0,.2)";

  toast.style.zIndex = "9999";

  toast.style.opacity = "0";

  toast.style.transition = ".3s";

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "1";
  }, 100);

  setTimeout(() => {
    toast.style.opacity = "0";

    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 2000);
}
// =======================================
// DARK MODE
// =======================================

const darkButton = document.createElement("button");

darkButton.innerHTML = '<i class="fa-solid fa-moon"></i>';

darkButton.className = "dark-mode-btn";

document.body.appendChild(darkButton);

darkButton.addEventListener("click", () => {
  document.body.classList.toggle("dark-mode");

  if (document.body.classList.contains("dark-mode")) {
    darkButton.innerHTML = '<i class="fa-solid fa-sun"></i>';
  } else {
    darkButton.innerHTML = '<i class="fa-solid fa-moon"></i>';
  }
});

// =======================================
// SCROLL TO TOP
// =======================================

const topBtn = document.createElement("button");

topBtn.innerHTML = "↑";

topBtn.style.position = "fixed";

topBtn.style.bottom = "30px";

topBtn.style.right = "30px";

topBtn.style.width = "50px";

topBtn.style.height = "50px";

topBtn.style.border = "none";

topBtn.style.borderRadius = "50%";

topBtn.style.background = "#0066ff";

topBtn.style.color = "white";

topBtn.style.fontSize = "22px";

topBtn.style.cursor = "pointer";

topBtn.style.display = "none";

topBtn.style.boxShadow = "0 5px 15px rgba(0,0,0,.3)";

document.body.appendChild(topBtn);

window.addEventListener("scroll", () => {
  if (window.scrollY > 300) {
    topBtn.style.display = "block";
  } else {
    topBtn.style.display = "none";
  }
});

topBtn.addEventListener("click", () => {
  window.scrollTo({
    top: 0,

    behavior: "smooth",
  });
});

// =======================================
// SCROLL REVEAL
// =======================================

const revealItems = document.querySelectorAll(
  ".category-card,.product-card,.feature-card,.seller-card,.testimonial-card",
);

const reveal = () => {
  revealItems.forEach((item) => {
    const top = item.getBoundingClientRect().top;

    if (top < window.innerHeight - 100) {
      item.classList.add("active");
    }
  });
};

window.addEventListener("scroll", reveal);

reveal();

// =======================================
// COUNTER ANIMATION
// =======================================

const counters = document.querySelectorAll(".hero-stats h3");

counters.forEach((counter) => {
  const update = () => {
    const target = parseInt(counter.innerText);

    let current = Number(counter.dataset.count) || 0;

    current += Math.ceil(target / 40);

    if (current < target) {
      counter.innerText = current + "+";

      counter.dataset.count = current;

      requestAnimationFrame(update);
    } else {
      counter.innerText = target + "+";
    }
  };

  update();
});

// =======================================
// MOBILE MENU
// =======================================

const navbar = document.querySelector(".navbar");

const navLinks = document.querySelector(".nav-links");

const menu = document.createElement("div");

menu.className = "menu-toggle";

menu.innerHTML = '<i class="fa-solid fa-bars"></i>';

navbar.prepend(menu);

menu.addEventListener("click", () => {
  navLinks.classList.toggle("show");
});
