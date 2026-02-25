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
    background:url("./rentiquebanner.png") center/cover no-repeat;
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
    background:rgba(0,255,120,.1);
    border-radius:25px;
    padding:2rem;
    text-align:center;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(0,255,120,0.2);
}

.kpi h2{
    color:#00ff66;
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
    color: #00ff66;
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
    border-color: #00ff66;
}

.form-card button{
    width:100%;
    padding:.9rem;
    background:#00ff66;
    border:none;
    border-radius:30px;
    cursor:pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s ease;
}

.form-card button:hover {
    transform: translateY(-2px);
    background: #00cc52;
}

.main-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:2rem;
}

.card{
    background:rgba(0,255,120,.08);
    backdrop-filter:blur(20px);
    border-radius:28px;
    padding:2.5rem;
    border: 1px solid rgba(0,255,120,0.1);
}

.card h3 {
    margin-bottom: 1.5rem;
    color: #00ff66;
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
    color: #00ff66;
}

.delete-btn{
    background:#ff5252;
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
    border-color: #00ff66;
}

.note-section button {
    margin-top:1rem;
    width:100%;
    background:#00ff66;
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
    background: #00cc52;
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
    color: #00ff66;
    margin-bottom: 1rem;
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

        <li>
            <button id="themeToggle" onclick="toggleTheme()">ðŸŒ™</button>
        </li>

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

<h1 style="margin-bottom: 2rem; color: #00ff66;">Welcome Style Planner</h1>

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
    <h2 id="totalPrice">Â£0</h2>
    <span>Total Plan Price</span>
</div>

</div>

<div class="main-grid">

<div class="card form-card">
<h3><i class="fas fa-plus-circle"></i> Add Style Plan</h3>

<input id="date" type="date" placeholder="Select date">
<input id="item" placeholder="Clothing Item (e.g., Summer Dress)">
<input id="days" type="number" placeholder="Rental Days">
<input id="price" type="number" placeholder="Price (Â£)">

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

<footer>
    <i class="fas fa-copyright"></i> 2026 Rentique Style Planner. All rights reserved.
</footer>

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
<i class="fas fa-calendar"></i> ${p.date} â€¢ <i class="fas fa-clock"></i> ${p.days}d â€¢ <i class="fas fa-pound-sign"></i> Â£${p.price}
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
document.getElementById("totalPrice").innerText="Â£"+totalPrice;

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
let days=+document.getElementById("days").value;
let price=+document.getElementById("price").value;

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
alert("Notes saved successfully! âœ¨");
}

renderPlans();

</script>

</body>
</html>
