document.addEventListener("DOMContentLoaded", function () {

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


    let liveFeedActive = true;
    let feedTimeout;

    let feed = document.getElementById("liveFeed");

    if (!feed) {
        feed = document.createElement("div");
        feed.id = "liveFeed";
        document.body.appendChild(feed);
    }

    const liveMessages = [
        { user: "Emma", action: "just rented", item: "Vintage Jacket" },
        { user: "Daniel", action: "added to cart", item: "Designer Bag" },
        { user: "Sofia", action: "is checking out", item: "Sneakers" },
        { user: "Lucas", action: "completed renting of", item: "Winter Coat" },
        { user: "Olivia", action: "added to wishlist", item: "Leather Boots" },
        { user: "Noah", action: "just rented", item: "Oversized Hoodie" },
        { user: "Ava", action: "applied discount to", item: "Summer Dress" },
        { user: "Ethan", action: "viewed", item: "Limited Edition Sneakers" },
        { user: "Mia", action: "is checking out", item: "Denim Jacket" },
        { user: "James", action: "just rented", item: "Premium Blazer" },
        { user: "Isabella", action: "added to cart", item: "Luxury Handbag" },
        { user: "Benjamin", action: "completed rent of", item: "Classic Trench Coat" }
    ];

    function generateTime() {
        const seconds = Math.floor(Math.random() * 50) + 1;
        return seconds + "s ago";
    }

    function createNotification() {

        const random = liveMessages[Math.floor(Math.random() * liveMessages.length)];
        const time = generateTime();

        const notification = document.createElement("div");
        notification.classList.add("live-notification");

        notification.innerHTML = `
            <div class="live-header">
                <span class="live-dot"></span>
                <strong>${random.user}</strong>
                <span class="live-time">${time}</span>
            </div>
            <div class="live-action">
                ${random.action} <strong>${random.item}</strong>
            </div>
        `;

        feed.appendChild(notification);

        setTimeout(() => {
            notification.classList.add("show");
        }, 50);

        setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => notification.remove(), 400);
        }, 5000);
    }

    function loopFeed() {
        if (!liveFeedActive) return;

        const randomDelay = Math.random() * 5000 + 5000;

        feedTimeout = setTimeout(() => {
            createNotification();
            loopFeed();
        }, randomDelay);
    }

    // ðŸ”¥ HANDLE TOGGLE SWITCH
    const feedSwitch = document.getElementById("feedSwitch");

    // Load saved state
    const savedState = localStorage.getItem("feedEnabled");

    if (savedState !== null) {
        liveFeedActive = savedState === "true";
    }

    if (feedSwitch) {
        feedSwitch.checked = liveFeedActive;

        feedSwitch.addEventListener("change", function () {

            liveFeedActive = this.checked;
            localStorage.setItem("feedEnabled", liveFeedActive);

            if (liveFeedActive) {
                createNotification();
                loopFeed();
            } else {
                clearTimeout(feedTimeout);
                feed.innerHTML = "";
            }
        });
    }

   
    if (liveFeedActive) {
        createNotification();
        loopFeed();
    }

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


document.addEventListener("DOMContentLoaded", function () {

    const chatBtn = document.getElementById("aiChatBtn");
    const chatBox = document.getElementById("chatBox");
    const closeChat = document.getElementById("closeChat");
    const sendBtn = document.getElementById("sendMessage");
    const userInput = document.getElementById("userMessage");
    const chatMessages = document.getElementById("chatMessages");

    if (!chatBtn) return; // safety check

    chatBtn.addEventListener("click", function () {
        chatBox.classList.toggle("active");
    });

    closeChat.addEventListener("click", function () {
        chatBox.classList.remove("active");
    });

    sendBtn.addEventListener("click", sendMessage);

    userInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            sendMessage();
        }
    });

    function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    appendMessage("You", message);
    userInput.value = "";

    const typing = document.createElement("div");
    typing.classList.add("message", "ai");
    typing.textContent = "Typing...";
    chatMessages.appendChild(typing);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    fetch("ai_chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: message })
    })
    .then(res => res.json())
    .then(data => {
        typing.remove();
        appendMessage("AI", data.reply);
    })
    .catch(err => {
        typing.remove();
        appendMessage("AI", "Error connecting to server.");
    });
}

   function appendMessage(sender, text) {
    const div = document.createElement("div");
    div.classList.add("message");

    if (sender === "You") {
        div.classList.add("user");
    } else {
        div.classList.add("ai");
    }

    div.textContent = text;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

});


