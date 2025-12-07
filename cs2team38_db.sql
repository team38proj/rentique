CREATE TABLE basket (
id int(11) NOT NULL,
uid int(11) NOT NULL,
pid int(11) NOT NULL,
title varchar(255) NOT NULL,
image varchar(255) NOT NULL,
product_type varchar(100) NOT NULL,
price decimal(10,2) NOT NULL,
quantity int(11) NOT NULL DEFAULT 1,
created_at timestamp NOT NULL DEFAULT current_timestamp(),
updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE products (
pid int(11) NOT NULL,
uid int(11) NOT NULL,
title varchar(255) NOT NULL,
image varchar(255) NOT NULL,
product_type varchar(100) NOT NULL,
price decimal(10,2) NOT NULL,
description text DEFAULT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE saved_cards (
id int(11) NOT NULL,
uid int(11) NOT NULL,
cardholder_name varchar(255) NOT NULL,
card_type varchar(50) NOT NULL,
masked_card_number varchar(30) NOT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE transactions (
id int(11) NOT NULL,
pid int(11) NOT NULL,
paying_uid int(11) NOT NULL,
receiving_uid int(11) NOT NULL,
price decimal(10,2) NOT NULL,
order_id varchar(50) NOT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE users (
uid int(11) NOT NULL,
username varchar(100) NOT NULL,
email varchar(255) NOT NULL,
password varchar(255) NOT NULL,
billing_fullname varchar(255) DEFAULT NULL,
address varchar(255) DEFAULT NULL,
pay_sortcode varchar(20) DEFAULT NULL,
pay_banknumber varchar(50) DEFAULT NULL,
role enum('customer','admin') NOT NULL DEFAULT 'customer',
created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



ALTER TABLE basket
ADD PRIMARY KEY (id),
ADD KEY uid (uid),
ADD KEY pid (pid);

ALTER TABLE products
ADD PRIMARY KEY (pid),
ADD KEY uid (uid);

ALTER TABLE saved_cards
ADD PRIMARY KEY (id),
ADD KEY uid (uid);

ALTER TABLE transactions
ADD PRIMARY KEY (id),
ADD KEY pid (pid),
ADD KEY paying_uid (paying_uid),
ADD KEY receiving_uid (receiving_uid);

ALTER TABLE users
ADD PRIMARY KEY (uid),
ADD UNIQUE KEY email (email);


ALTER TABLE basket
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE products
MODIFY pid int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE saved_cards
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE transactions
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE users
MODIFY uid int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;



INSERT INTO products (pid, uid, title, image, product_type, price, description, created_at) VALUES
(1, 3, 'dress', '1764973088_blackdress.webp', 'Dresses', 245.00, 'dress', '2025-12-05 22:18:08'),
(2, 3, 'nike shoes', '1764973109_nike.webp', 'Shoes', 300.00, 'used nike shoes', '2025-12-05 22:18:29'),
(3, 4, 'suit', '1764973177_suit.webp', 'Suits', 265.00, 'suit', '2025-12-05 22:19:37');

INSERT INTO saved_cards (id, uid, cardholder_name, card_type, masked_card_number, created_at) VALUES
(1, 4, 'testuser', 'Visa', '************4444', '2025-12-05 22:22:03'),
(2, 3, 'bob', 'MasterCard', '************5432', '2025-12-05 22:40:56');

INSERT INTO transactions (id, pid, paying_uid, receiving_uid, price, order_id, created_at) VALUES
(1, 1, 4, 4, 245.00, '17649733231587', '2025-12-05 22:22:03'),
(2, 3, 3, 3, 265.00, '17649744568551', '2025-12-05 22:40:56');

INSERT INTO users (uid, username, email, password, billing_fullname, address, pay_sortcode, pay_banknumber, role, created_at) VALUES
(2, 'admin11', 'admin@gmail.com
', '$2y$10$5wjxgr8EJrkXC8Jo4hC8q.pG.O51KOQ7Fu.6YAtIOp4.P0Oc0TmHW', NULL, NULL, NULL, NULL, 'admin', '2025-12-05 21:48:18'),
(3, 'testuser', 'testuser@aston.ac.uk
', '$2y$10$UVCBj45JXD8trytcne9B/uowpejgPq5IZNwMgZCDXX1Z2nTICnwVW', NULL, NULL, NULL, NULL, 'customer', '2025-12-05 22:10:26'),
(4, 'testuser2', 'testuser2@aston.ac.uk
', '$2y$10$KZ4Nn0fvVZu9JqmwTksWU..F09BTZzxFk.7DUGi9Qk6hbS8I79c9q', NULL, NULL, NULL, NULL, 'customer', '2025-12-05 22:19:08');


ALTER TABLE basket
ADD CONSTRAINT basket_ibfk_1 FOREIGN KEY (uid) REFERENCES users (uid) ON DELETE CASCADE,
ADD CONSTRAINT basket_ibfk_2 FOREIGN KEY (pid) REFERENCES products (pid) ON DELETE CASCADE;

ALTER TABLE products
ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (uid) REFERENCES users (uid) ON DELETE CASCADE;

ALTER TABLE saved_cards
ADD CONSTRAINT saved_cards_ibfk_1 FOREIGN KEY (uid) REFERENCES users (uid) ON DELETE CASCADE;

ALTER TABLE transactions
ADD CONSTRAINT transactions_ibfk_1 FOREIGN KEY (pid) REFERENCES products (pid),
ADD CONSTRAINT transactions_ibfk_2 FOREIGN KEY (paying_uid) REFERENCES users (uid),
ADD CONSTRAINT transactions_ibfk_3 FOREIGN KEY (receiving_uid) REFERENCES users (uid);