<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Rentique | Style Planner Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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

.navbar{
    display:flex;
    justify-content:space-between;
    padding:1rem 3rem;
    background:rgba(0,0,0,0.6);
    backdrop-filter:blur(10px);
    position:sticky;
    top:0;
    z-index:100;
}

.logo a {
    display: inline-block;
    line-height: 0;
}

.logo img{ 
    height:60px;
    width: auto;
    display: block;
}

.nav-links{
    display:flex;
    gap:2rem;
    list-style:none;
    align-items: center;
}

.nav-links a{
    color:#00ff66;
    text-decoration:none;
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
}

.kpi h2{
    color:#00ff66;
    font-size:2.5rem;
}

.form-card{
    margin-bottom:3rem;
}

.form-card input{
    width:100%;
    padding:.9rem;
    margin-bottom:.8rem;
    border-radius:14px;
    border:none;
    background:rgba(0,0,0,.4);
    color:white;
}

.form-card button{
    width:100%;
    padding:.9rem;
    background:#00ff66;
    border:none;
    border-radius:30px;
    cursor:pointer;
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
}

.log-item{
    background:rgba(0,255,120,.15);
    padding:1rem;
    border-radius:18px;
    margin-bottom:.8rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.delete-btn{
    background:#ff5252;
    border:none;
    padding:.3rem .8rem;
    border-radius:8px;
    color:white;
    cursor:pointer;
}

#planChart{
    margin-top:2.5rem;
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
}

footer{
    text-align:center;
    padding:2rem;
    opacity:.8;
}

</style>

<link rel="icon" type="image/png" href="/images/rentique_logo.png">

</head>

<body>

<nav class="navbar">
    <div class="logo">
   
        <a href="index.php">
            <img src="/images/rentique_logo.png" alt="Rentique logo">
        </a>
    </div>

    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="game.php">Game</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
    </ul>
</nav>

<section class="banner">
    <div class="banner-overlay">
        <h1>Rentique Style Planner</h1>
        <p>Build your perfect rental wardrobe strategy</p>
    </div>
</section>

<section class="dashboard">

<h1>Welcome Style Planner</h1>

<div class="kpi-grid">

<div class="kpi">
<h2 id="totalDays">0d</h2>
<span>Planned Rental Days</span>
</div>

<div class="kpi">
<h2 id="totalItems">0</h2>
<span>Clothing Items Planned</span>
</div>

<div class="kpi">
<h2 id="totalPrice">£0</h2>
<span>Total Plan Price</span>
</div>

</div>

<div class="main-grid">

<div class="card form-card">
<h3>Add Style Plan</h3>

<input id="date" type="date">
<input id="item" placeholder="Clothing Item">
<input id="days" type="number" placeholder="Rental Days">
<input id="price" type="number" placeholder="Price (£)">

<button onclick="addPlan()">Save Plan</button>
</div>

<div class="card">
<h3>Style Plan History</h3>

<div id="logList"></div>

<canvas id="planChart"></canvas>

</div>

</div>

<div class="note-section card">
<h3>Style Notes</h3>
<textarea id="userNotes" placeholder="Write your style thoughts..."></textarea>
<button onclick="saveNotes()" style="margin-top:1rem;width:100%;background:#00ff66;border:none;padding:.8rem;border-radius:25px;">Save Notes</button>
</div>

</section>

<footer>© 2026 Rentique Style Planner</footer>

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
${p.date} • ${p.days}d • £${p.price}
</div>

<div>
<button class="delete-btn" onclick="deletePlan(${index})">Delete</button>
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

let goal=Math.min((totalDays/100)*100,100);
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

if(!d||!item||!days||!price) return;

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
alert("Notes saved!");
}

renderPlans();

</script>

</body>

</html>
