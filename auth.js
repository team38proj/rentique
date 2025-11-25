
const USERS_KEY = 'rentique_users';
const SESSION_ROLE = 'rentique_role';
const SESSION_USER = 'rentique_user';


function loadUsers() {
    const raw = localStorage.getItem(USERS_KEY);
    return raw ? JSON.parse(raw) : [];
}
function saveUsers(list) {
    localStorage.setItem(USERS_KEY, JSON.stringify(list));
}
function findUserByEmail(email) {
    return loadUsers().find(u => u.email.toLowerCase() === email.toLowerCase());
}


function hashPassword(plain) {
    return btoa(plain);
}


function handleRegister(e) {
    e.preventDefault();

    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const password = document.getElementById('signupPassword').value;
    const role = document.getElementById('signupRole').value;

    if (!name || !email || !password) {
        alert('Please complete all required fields.');
        return;
    }

    if (findUserByEmail(email)) {
        alert('An account with that email already exists. Please login or use another email.');
        return;
    }


    const displayName = document.getElementById('sellerDisplayName') ? document.getElementById('sellerDisplayName').value.trim() : '';
    const sort = document.getElementById('sellerSort') ? document.getElementById('sellerSort').value.trim() : '';
    const account = document.getElementById('sellerAccount') ? document.getElementById('sellerAccount').value.trim() : '';

    const newUser = {
        name,
        email,
        password: hashPassword(password),
        role,
        createdAt: new Date().toISOString(),
        sellerProfile: role === 'seller' ? { displayName, sort, account, verified: false } : null
    };

    const users = loadUsers();
    users.push(newUser);
    saveUsers(users);

 
    localStorage.setItem(SESSION_ROLE, role);
    localStorage.setItem(SESSION_USER, email);
    redirectByRole(role);
}


function handleLogin(e) {
    e.preventDefault();

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const role = document.getElementById('loginRole').value;

    const user = findUserByEmail(email);
    if (!user) {
        alert('No account found with that email.');
        return;
    }

    if (user.password !== hashPassword(password)) {
        alert('Incorrect password.');
        return;
    }

    
    if (user.role !== role) {
        
        alert(`This account is registered as "${user.role}". Please select the matching role.`);
        return;
    }

  
    localStorage.setItem(SESSION_ROLE, role);
    localStorage.setItem(SESSION_USER, email);
    redirectByRole(role);
}

function redirectByRole(role) {
    if (role === 'user') {
        window.location.href = 'user_dashboard.html';
    } else if (role === 'seller') {
        window.location.href = 'seller_dashboard.html';
    } else if (role === 'admin') {
        window.location.href = 'admin_dashboard.html';
    } else {
        window.location.href = 'Homepage.html';
    }
}

function logout() {
    localStorage.removeItem(SESSION_ROLE);
    localStorage.removeItem(SESSION_USER);
    window.location.href = 'login.html';
}


function toggleSellerFields() {
    const role = document.getElementById('signupRole').value;
    const div = document.getElementById('sellerFields');
    if (!div) return;
    div.style.display = role === 'seller' ? 'block' : 'none';
}


window.handleRegister = handleRegister;
window.handleLogin = handleLogin;
window.logout = logout;
window.toggleSellerFields = toggleSellerFields;

localStorage.setItem('rentique_users', JSON.stringify([
    {
        name: "John Smith",
        email: "john.user@rentique.com",
        password: btoa("user123"),  
        role: "user",
        createdAt: new Date().toISOString(),
        sellerProfile: null
    },
    {
        name: "Sophie Boutique",
        email: "sophie.seller@rentique.com",
        password: btoa("seller123"), 
        role: "seller",
        createdAt: new Date().toISOString(),
        sellerProfile: {
            displayName: "Sophie’s Closet",
            sort: "11-22-33",
            account: "12345678",
            verified: true
        }
    },
    {
        name: "Admin Master",
        email: "admin@rentique.com",
        password: btoa("admin123"), 
        role: "admin",
        createdAt: new Date().toISOString(),
        sellerProfile: null
    }
]));
