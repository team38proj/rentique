document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("themeToggle");

    if (!toggle) return;

    // Load saved state
    const saved = localStorage.getItem("rentique_theme");
    if (saved === "light") {
        document.documentElement.classList.add("light-mode");
    }

    // Toggle on click
    toggle.addEventListener("click", function () {
        document.documentElement.classList.toggle("light-mode");

        const isLight = document.documentElement.classList.contains("light-mode");
        localStorage.setItem("rentique_theme", isLight ? "light" : "dark");
    });
});
