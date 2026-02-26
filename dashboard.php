<?php
session_start();
require_once 'connectdb.php';

$userData = null;

if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Planner user fetch error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Rentique | Style Planner Dashboard</title>
<link rel="stylesheet" href="assets/global.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,sans-serif;
}

body{
    background:black;
    color:white;
    transition:background 1.2s ease;
}

body.style-low{ background:#050505; }
body.style-mid{ background:#0f2f0f; }
body.style-high{ background:#00aa33; }

.navbar {
display: flex;
justify-content: space-between;
align-items: center;
padding: 20px 60px;
background-color: #000;
border-bottom: 1px solid #1f1f1f;
}

                .navbar {
display: flex;
justify-content: space-between;
align-items: center;
padding: 20px 60px;
background-color: #000;
border-bottom: 1px solid #1f1f1f;
}

.logo {
display: flex;
align-items: center;
color: #a3ff00;
font-size: 1.6rem;
font-weight: bold;
text-transform: lowercase;
}

.logo img {
width: 45px;
margin-right: 10px;
}

.nav-links {
list-style: none;
display: flex;
gap: 20px;
}

.nav-links li a {
color: #eaeaea;
text-decoration: none;
font-weight: 500;
transition: color 0.3s;
}

.nav-links li a:hover {
color: #a3ff00;
}

.btn {
padding: 10px 18px;
border-radius: 30px;
text-decoration: none;
font-weight: 600;
transition: all 0.3s ease;
}

.btn.login {
border: 1px solid #a3ff00;
color: #a3ff00;
}

.btn.login:hover {
background-color: #a3ff00;
color: #000;
}

.btn.signup {
background-color: #a3ff00;
color: #000;
}

.btn.signup:hover {
background-color: #d2ff4c;
}

.cart-icon {
font-size: 1.3rem;
color: #a3ff00;
transition: 0.3s;
}

.cart-icon:hover {
color: #d2ff4c;
}

.banner{
    height:55vh;
    background:url("images/rentiquebanner2.png") center/cover no-repeat;
}

.banner-overlay{
    height:100%;
    background:linear-gradient(rgba(0,0,0,.4),rgba(0,0,0,.8));
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
}

.banner-overlay h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.banner-overlay p {
    font-size: 1.2rem;
    opacity: 0.9;
}

.dashboard{
    max-width:1200px;
    margin:-4rem auto 5rem;
    padding:4rem;
    background:rgba(0,0,0,0.4);
    backdrop-filter:blur(25px);
    border-radius:35px;
    box-shadow:0 0 60px rgba(0,255,120,.15);
}

.kpi-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:1.5rem;
    margin:3rem 0;
}

.kpi{
    background: linear-gradient(
    135deg,
    #020600,
    #051500
);
    border-radius:25px;
    padding:2rem;
    text-align:center;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(0,255,120,0.2);
}

.kpi h2{
    color:#a3ff00;
    font-size:2.5rem;
}

.kpi span {
    font-size: 1rem;
    opacity: 0.8;
}

.form-card{
    margin-bottom:3rem;
}

.form-card h3 {
    margin-bottom: 1.5rem;
    color: #a3ff00;
}

.form-card input{
    width:100%;
    padding:.9rem;
    margin-bottom:.8rem;
    border-radius:14px;
    border:none;
    background:rgba(0,0,0,.4);
    color:white;
    border: 1px solid rgba(255,255,255,0.1);
}

.form-card input:focus {
    outline: none;
    border-color: #a3ff00;
}

.form-card button{
    width:100%;
    padding:.9rem;
    background:#a3ff00;
    border:none;
    border-radius:30px;
    cursor:pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s ease;
}

.form-card button:hover {
    transform: translateY(-2px);
    background: #a3ff66;
}

.main-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:2rem;
}

.card{
background: linear-gradient(
    135deg,
    #020600,
    #051500
);
    backdrop-filter:blur(20px);
    border-radius:28px;
    padding:2.5rem;
    border: 2px solid rgba(0,255,120,0.1);
}

.card h3 {
    margin-bottom: 1.5rem;
    color: #a3ff00;
}

.log-item{
    background:rgba(0,255,120,.15);
    padding:1rem;
    border-radius:18px;
    margin-bottom:.8rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border: 1px solid rgba(255,255,255,0.1);
}

.log-item strong {
    color: #a3ff00;
}

.delete-btn{
    background:#a3ff00;
    border:none;
    padding:.3rem .8rem;
    border-radius:8px;
    color:white;
    cursor:pointer;
    transition: background 0.2s ease;
}

.delete-btn:hover {
    background: #ff3333;
}

#planChart{
    margin-top:2.5rem;
    max-height: 300px;
}

.note-section{
    margin-top:3rem;
}

textarea{
    width:100%;
    height:120px;
    padding:1rem;
    border-radius:15px;
    border:none;
    background:rgba(0,0,0,.4);
    color:white;
    resize:none;
    border: 1px solid rgba(255,255,255,0.1);
    margin-top: 1rem;
}

textarea:focus {
    outline: none;
    border-color:#a3ff00;
}

.note-section button {
    margin-top:1rem;
    width:100%;
    background: #a3ff00;
    border:none;
    padding:.8rem;
    border-radius:25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s ease;
}

.note-section button:hover {
    transform: translateY(-2px);
    background: #a3ff66;
}

footer{
    text-align:center;
    padding:2rem;
    opacity:.8;
}


.nav-links i {
    margin-right: 5px;
}

.kpi i {
    font-size: 2rem;
    color: #a3ff00;
    margin-bottom: 1rem;
}

 .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-icon svg {
            width: 20px;
            height: 20px;
            stroke: #eaeaea;
            transition: all 0.3s ease;
        }
        html.light-mode .cart-icon svg {
            stroke: #000000;
        }
        .cart-icon:hover svg {
            stroke: #00FF00;
        }
      
</style>


<link rel="icon" type="image/png" href="/images/rentique_logo.png">



</head>

<body>

<header>
<nav class="navbar">
    <div class="logo">
        <a href="index.php">
            <img src="images/rentique_logo.png" alt="Rentique Logo">
        </a>
        <span>rentique.</span>
    </div>

    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="productsPage.php">Shop</a></li>
        <li><a href="AboutUs.php">About</a></li>
        <li><a href="Contact.php">Contact</a></li>
        <li><a href="FAQTestimonials.php">FAQ</a></li>
<li><a href="game.php" class="active">Game</a></li>
   

        <?php if (isset($userData)): ?>
            <li><a href="dashboard.php">Style Planner</a></li>
        <?php else: ?>
    <a href="login.php" class="active">Style Planner</a>
<?php endif; ?>

        <li><a href="basketPage.php" class="cart-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
            </svg>
        </a></li>


        <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
            <li><a href="seller_dashboard.php">Sell</a></li>
            <li><a href="user_dashboard.php">
                <?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?>
            </a></li>
            <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

        <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
            <li><a href="admin_dashboard.php">Admin</a></li>
            <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

        <?php else: ?>
            <li><a href="login.php" class="btn login">Login</a></li>
            <li><a href="signup.php" class="btn signup">Sign Up</a></li>
        <?php endif; ?>
    </ul>
</nav>
</header>

<section class="banner">
    <div class="banner-overlay">
        <h1>Rentique Style Planner</h1>
        <p>Build your perfect rental wardrobe strategy</p>
    </div>
</section>

<section class="dashboard">

<h1 style="margin-bottom: 2rem; color: #a3ff00;">Welcome Style Planner</h1>

<div class="kpi-grid">

<div class="kpi">
    <i class="fas fa-calendar-alt"></i>
    <h2 id="totalDays">0d</h2>
    <span>Planned Rental Days</span>
</div>

<div class="kpi">
    <i class="fas fa-tshirt"></i>
    <h2 id="totalItems">0</h2>
    <span>Clothing Items Planned</span>
</div>

<div class="kpi">
    <i class="fas fa-pound-sign"></i>
    <h2 id="totalPrice">£0</h2>
    <span>Total Plan Price</span>
</div>

</div>

<div class="main-grid">

<div class="card form-card">
<h3><i class="fas fa-plus-circle"></i> Add Style Plan</h3>

<input id="date" type="date" min="0" placeholder="Select date">
<input id="item" placeholder="Clothing Item (e.g., Summer Dress)">
<input id="days" type="number" min="0" placeholder="Rental Days">
<input id="price" type="number" min="0"placeholder="Price (£)">

<button onclick="addPlan()"><i class="fas fa-save"></i> Save Plan</button>
</div>

<div class="card">
<h3><i class="fas fa-history"></i> Style Plan History</h3>

<div id="logList"></div>

<canvas id="planChart"></canvas>

</div>

</div>

<div class="note-section card">
<h3><i class="fas fa-pen"></i> Style Notes</h3>
<textarea id="userNotes" placeholder="Write your style thoughts, outfit combinations, or rental ideas..."></textarea>
<button onclick="saveNotes()"><i class="fas fa-save"></i> Save Notes</button>
</div>

</section>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column brand-column">
            <div class="footer-logo">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
                <span>rentique.</span>
            </div>
            <p class="footer-description">Rent. Wear. Return.<br>Fashion freedom. Sustainable choice.</p>
            <div class="footer-social">
                <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://pinterest.com" target="_blank"><i class="fab fa-pinterest-p"></i></a>
            </div>
        </div>

        <div class="footer-column links-column">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About Us</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="FAQTestimonials.php">FAQ</a></li>
            </ul>
        </div>

        <div class="footer-column contact-column">
            <h4>Stay Connected</h4>
            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> dtblations@gmail.com</p>
                <p><i class="fas fa-phone-alt"></i> 0121-875-3543</p>
                <p><i class="fas fa-map-marker-alt"></i> Aston University, Birmingham</p>
            </div>
            
            <div class="newsletter">
                <p>Subscribe for exclusive offers</p>
                <div class="newsletter-input">
                    <input type="email" id="subscribeEmail" placeholder="Your email address">
                    <button type="button" id="subscribeBtn">→</button>
                </div>
                <div id="subscribeMessage" class="subscribe-message"></div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>© 2025 Rentique. All Rights Reserved.</p>
    </div>
</footer>

<style>
.footer {
    background: #000;
    color: #fff;
    padding: 2.5rem 0 0;
    margin-top: 3rem;
    border-top: 3px solid #00FF00;
    width: 100%;
}

.footer-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 2fr 1fr 2fr;
    gap: 1rem;
    align-items: start;
}

.footer-column {
    display: flex;
    flex-direction: column;
}

.brand-column {
    align-items: flex-start;
}

.links-column {
    align-items: center;
    text-align: center;
}

.contact-column {
    align-items: flex-end;
    text-align: right;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.footer-logo img {
    width: 40px;
    height: auto;
}

.footer-logo span {
    font-size: 1.8rem;
    font-weight: bold;
    color: #00FF00;
    text-transform: lowercase;
}

.footer-description {
    color: #b0b0b0;
    line-height: 1.5;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
    text-align: left;
}

.footer-social {
    display: flex;
    gap: 0.8rem;
}

.footer-social a {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1rem;
    border: 1px solid rgba(0, 255, 0, 0.2);
}

.footer-social a:hover {
    background: #00FF00;
    color: #000;
    transform: translateY(-3px);
    border-color: transparent;
}

.footer-column h4 {
    color: #00FF00;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    font-weight: 600;
    width: 100%;
}

.links-column h4 {
    text-align: center;
}

.contact-column h4 {
    text-align: right;
}

.footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.links-column ul {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.footer-column ul li {
    margin-bottom: 0.6rem;
}

.footer-column ul li a {
    color: #d0d0d0;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-column ul li a:hover {
    color: #00FF00;
}

.contact-info {
    margin-bottom: 1.2rem;
    width: 100%;
}

.contact-info p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    justify-content: flex-end;
}

.contact-info i {
    color: #00FF00;
    width: 18px;
    text-align: center;
}

.newsletter {
    width: 100%;
}

.newsletter p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    text-align: right;
}

.newsletter-input {
    display: flex;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 0, 0.2);
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
    max-width: 260px;
    margin-left: auto;
}

.newsletter-input input {
    flex: 1;
    padding: 0.7rem;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 0.9rem;
}

.newsletter-input input:focus {
    outline: none;
}

.newsletter-input input::placeholder {
    color: #666;
}

.newsletter-input button {
    background: #00FF00;
    border: none;
    color: #000;
    padding: 0.7rem 1rem;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: bold;
    transition: background 0.3s ease;
}

.newsletter-input button:hover {
    background: #d2ff4c;
}

.subscribe-message {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    min-height: 1.2rem;
    color: #00FF00;
    text-align: right;
}

.footer-bottom {
    margin-top: 2rem;
    padding: 1.2rem 0;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    width: 100%;
}

.footer-bottom p {
    color: #aaa;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
}

html.light-mode .footer {
    background: #f8f8f8;
    color: #333;
}

html.light-mode .footer-description {
    color: #666;
}

html.light-mode .footer-social a {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

html.light-mode .footer-social a:hover {
    background: #00FF00;
    color: #000;
}

html.light-mode .footer-column ul li a {
    color: #555;
}

html.light-mode .contact-info p {
    color: #555;
}

html.light-mode .newsletter p {
    color: #555;
}

html.light-mode .newsletter-input {
    background: #fff;
}

html.light-mode .newsletter-input input {
    color: #333;
}

html.light-mode .newsletter-input input::placeholder {
    color: #999;
}

html.light-mode .subscribe-message {
    color: #00FF00;
}

html.light-mode .footer-bottom {
    background: rgba(0, 0, 0, 0.02);
}

html.light-mode .footer-bottom p {
    color: #666;
}

@media (max-width: 900px) {
    .footer-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .brand-column {
        grid-column: span 2;
        align-items: center;
        text-align: center;
    }
    
    .footer-description {
        text-align: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .contact-column {
        align-items: center;
        text-align: center;
    }
    
    .contact-column h4 {
        text-align: center;
    }
    
    .contact-info p {
        justify-content: center;
    }
    
    .newsletter p {
        text-align: center;
    }
    
    .newsletter-input {
        margin: 0 auto;
    }
    
    .subscribe-message {
        text-align: center;
    }
}

@media (max-width: 600px) {
    .footer-container {
        grid-template-columns: 1fr;
    }
    
    .brand-column {
        grid-column: span 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribeBtn');
    const subscribeEmail = document.getElementById('subscribeEmail');
    const subscribeMessage = document.getElementById('subscribeMessage');
    
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', function() {
            const email = subscribeEmail.value.trim();
            
            if (!email) {
                showMessage('Please enter your email address', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }
            
            showMessage('Thank you for subscribing!', 'success');
            subscribeEmail.value = '';
            
            setTimeout(() => {
                subscribeMessage.innerHTML = '';
            }, 3000);
        });
        
        subscribeEmail.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                subscribeBtn.click();
            }
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showMessage(text, type) {
        subscribeMessage.innerHTML = text;
        subscribeMessage.style.color = type === 'success' ? '#00FF00' : '#ff4444';
    }
});
</script>

<script>

let plans = JSON.parse(localStorage.getItem("rentiquePlans")) || [];
let notes = localStorage.getItem("rentiqueNotes") || "";

let totalDays=0;
let totalPrice=0;
let chartInstance;

document.getElementById("userNotes").value = notes;

function renderPlans(){

let logList=document.getElementById("logList");
logList.innerHTML="";

totalDays=0;
totalPrice=0;

plans.forEach((p,index)=>{

totalDays+=p.days;
totalPrice+=p.price;

let div=document.createElement("div");
div.className="log-item";

div.innerHTML=`
<div>
<strong>${p.item}</strong><br>
<i class="fas fa-calendar"></i> ${p.date} • <i class="fas fa-clock"></i> ${p.days}d • <i class="fas fa-pound-sign"></i> £${p.price}
</div>

<div>
<button class="delete-btn" onclick="deletePlan(${index})"><i class="fas fa-trash"></i> Delete</button>
</div>
`;

logList.appendChild(div);

});

updateStats();
updateChart();
}

function updateStats(){

document.getElementById("totalDays").innerText=totalDays+"d";
document.getElementById("totalItems").innerText=plans.length;
document.getElementById("totalPrice").innerText="£"+totalPrice;

updateBackground(totalDays);

}

function updateBackground(days){

let maxDays = 50;
let progress = Math.min(days / maxDays, 1);

let greenValue = Math.floor(progress * 255);

document.body.style.background = `
radial-gradient(circle at center,
rgba(0,${greenValue},0,0.8),
black 70%)
`;

}

function updateChart(){

let ctx=document.getElementById("planChart").getContext("2d");

let labels=plans.map(p=>p.date);
let data=plans.map(p=>p.days);

if(chartInstance) chartInstance.destroy();

chartInstance=new Chart(ctx,{
type:"line",
data:{
labels:labels,
datasets:[{
label:"Rental Planning Trend (Days)",
data:data,
fill:true,
borderColor:"#00ff66",
backgroundColor:"rgba(0,255,102,0.2)",
tension:0.35
}]
},
options:{
responsive:true,
scales:{ y:{beginAtZero:true} }
}
});

}

function addPlan(){

let d=document.getElementById("date").value;
let item=document.getElementById("item").value;
let days = Math.max(0, Number(document.getElementById("days").value));
let price = Math.max(0, Number(document.getElementById("price").value));

if(!d||!item||!days||!price) {
    alert("Please fill in all fields!");
    return;
}

plans.push({ date:d, item:item, days:days, price:price });

localStorage.setItem("rentiquePlans",JSON.stringify(plans));

document.querySelectorAll("input").forEach(i=>i.value="");

renderPlans();

}

function deletePlan(index){

plans.splice(index,1);

localStorage.setItem("rentiquePlans",JSON.stringify(plans));

renderPlans();

}

function saveNotes(){

let text=document.getElementById("userNotes").value;
localStorage.setItem("rentiqueNotes",text);
alert("Notes saved successfully! ✨");
}

renderPlans();
          
            
 document.querySelectorAll('input[type="number"]').forEach(input=>{
    input.addEventListener("input",()=>{
        if(input.value < 0){
            input.value = 0;
        }
    });
});

</script>
<script src="assets/global.js"></script>
</body>
</html>
