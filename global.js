const glow = document.createElement("div");

glow.style.position = "fixed";
glow.style.width = "120px";
glow.style.height = "120px";
glow.style.pointerEvents = "none";
glow.style.transform = "translate(-50%, -50%)";
glow.style.borderRadius = "50%";
glow.style.zIndex = "0";

glow.style.background = `
radial-gradient(circle,
rgba(163,255,0,0.25) 0%,
rgba(163,255,0,0.15) 30%,
rgba(163,255,0,0.08) 50%,
transparent 70%)
`;

glow.style.filter = "blur(35px)";
glow.style.transition = "left 0.2s ease-out, top 0.2s ease-out";

document.body.appendChild(glow);

document.addEventListener("mousemove", e => {
    glow.style.left = e.clientX + "px";
    glow.style.top = e.clientY + "px";
});



for (let i = 0; i < 15; i++) {
    let dot = document.createElement("div");

    dot.style.position = "fixed";
    dot.style.width = "3px";
    dot.style.height = "3px";
    dot.style.background = "#a3ff00";
    dot.style.left = Math.random() * 100 + "vw";
    dot.style.top = Math.random() * 100 + "vh";
    dot.style.opacity = 0.3;
    dot.style.pointerEvents = "none";
    dot.style.animation = `float ${5 + Math.random()*5}s linear infinite`;

    dot.style.zIndex = "-2";  

    document.body.appendChild(dot);
}

window.addEventListener("load", () => {
    document.body.classList.add("loaded");
});

document.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function(e) {
        if (this.hostname === window.location.hostname) {
            e.preventDefault();
            document.body.classList.remove("loaded");
            setTimeout(() => {
                window.location = this.href;
            }, 120);
        }
    });
});