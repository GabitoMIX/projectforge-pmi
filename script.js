const navToggle = document.querySelector(".nav-toggle");
const navLinks = document.querySelector(".nav-links");

if (navToggle && navLinks) {
  navToggle.addEventListener("click", () => {
    const isOpen = navLinks.classList.toggle("open");
    navToggle.setAttribute("aria-expanded", String(isOpen));
  });
}

document.querySelectorAll(".copy-btn").forEach((button) => {
  button.addEventListener("click", async () => {
    const target = document.getElementById(button.dataset.copy);
    if (!target) return;

    const text = target.innerText.trim();

    try {
      await navigator.clipboard.writeText(text);
      const original = button.innerText;
      button.innerText = "Copiado";
      setTimeout(() => (button.innerText = original), 1400);
    } catch {
      alert("No se pudo copiar automáticamente. Selecciona el texto manualmente.");
    }
  });
});
