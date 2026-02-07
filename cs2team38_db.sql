CREATE TABLE admin_conversations (
  id int NOT NULL,
  user_uid int NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE admin_messages (
  id int NOT NULL,
  admin_conversation_id int NOT NULL,
  sender_role varchar(20) NOT NULL,
  sender_uid int DEFAULT NULL,
  body text NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE basket (
  id int(11) NOT NULL,
  uid int(11) NOT NULL,
  pid int(11) NOT NULL,
  title varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  image varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  product_type varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  price decimal(10,2) NOT NULL,
  quantity int(11) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  rental_days int(11) NOT NULL DEFAULT 1,
  platform_fee decimal(10,2) NOT NULL DEFAULT 4.99,
  seller_uid int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE conversations (
  id int NOT NULL,
  order_id_fk int NOT NULL,
  buyer_uid int NOT NULL,
  seller_uid int NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE messages (
  id int NOT NULL,
  conversation_id int NOT NULL,
  sender_role varchar(20) NOT NULL,
  sender_uid int DEFAULT NULL,
  body text NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_items (
  id int NOT NULL,
  order_id_fk int NOT NULL,
  pid int NOT NULL,
  seller_uid int NOT NULL,
  title varchar(255) NOT NULL,
  image varchar(255) DEFAULT NULL,
  product_type varchar(120) DEFAULT NULL,
  per_day_price decimal(10,2) NOT NULL,
  rental_days int NOT NULL DEFAULT 1,
  quantity int NOT NULL DEFAULT 1,
  line_total decimal(10,2) NOT NULL,
  platform_fee decimal(10,2) NOT NULL DEFAULT 4.99,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE order_shipments (
  id int NOT NULL,
  order_item_id int NOT NULL,
  shipped_at datetime DEFAULT NULL,
  courier varchar(80) DEFAULT NULL,
  tracking_number varchar(120) DEFAULT NULL,
  buyer_return_courier varchar(80) DEFAULT NULL,
  buyer_return_tracking varchar(120) DEFAULT NULL,
  buyer_marked_returned_at datetime DEFAULT NULL,
  seller_marked_received_at datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE orders (
  id int NOT NULL,
  order_id varchar(32) NOT NULL,
  buyer_uid int NOT NULL,
  status varchar(30) NOT NULL DEFAULT 'paid',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ship_by datetime DEFAULT NULL,
  rental_start date DEFAULT NULL,
  rental_end date DEFAULT NULL,
  return_deadline datetime DEFAULT NULL,
  buyer_address_line1 varchar(255) DEFAULT NULL,
  buyer_address_line2 varchar(255) DEFAULT NULL,
  buyer_city varchar(120) DEFAULT NULL,
  buyer_postcode varchar(30) DEFAULT NULL,
  buyer_country varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE products (
  pid int(11) NOT NULL,
  uid int(11) NOT NULL,
  title varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  image varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  product_type varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  price decimal(10,2) NOT NULL,
  description text COLLATE utf8mb4_general_ci DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_available tinyint(1) NOT NULL DEFAULT 1,
  available_confirmed tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE saved_cards (
  id int(11) NOT NULL,
  uid int(11) NOT NULL,
  cardholder_name varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  card_type varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  masked_card_number varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE users (
  uid int(11) NOT NULL,
  username varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  email varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  password varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  billing_fullname varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  address varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  pay_sortcode varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  pay_banknumber varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  role enum('customer','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'customer',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



ALTER TABLE admin_conversations
  ADD PRIMARY KEY (id),
  ADD KEY user_uid (user_uid);

ALTER TABLE admin_messages
  ADD PRIMARY KEY (id),
  ADD KEY admin_conversation_id (admin_conversation_id);

ALTER TABLE basket
  ADD PRIMARY KEY (id),
  ADD KEY uid (uid),
  ADD KEY pid (pid);

ALTER TABLE conversations
  ADD PRIMARY KEY (id),
  ADD KEY order_id_fk (order_id_fk),
  ADD KEY buyer_uid (buyer_uid),
  ADD KEY seller_uid (seller_uid);

ALTER TABLE messages
  ADD PRIMARY KEY (id),
  ADD KEY conversation_id (conversation_id);

ALTER TABLE order_items
  ADD PRIMARY KEY (id),
  ADD KEY order_id_fk (order_id_fk),
  ADD KEY seller_uid (seller_uid);

ALTER TABLE order_shipments
  ADD PRIMARY KEY (id),
  ADD KEY order_item_id (order_item_id);

ALTER TABLE orders
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY order_id (order_id);

ALTER TABLE products
  ADD PRIMARY KEY (pid),
  ADD KEY uid (uid);

ALTER TABLE saved_cards
  ADD PRIMARY KEY (id),
  ADD KEY uid (uid);

ALTER TABLE users
  ADD PRIMARY KEY (uid),
  ADD UNIQUE KEY email (email);



ALTER TABLE admin_conversations
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE admin_messages
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE basket
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

ALTER TABLE conversations
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE messages
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE order_items
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE order_shipments
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE orders
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE products
  MODIFY pid int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE saved_cards
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE users
  MODIFY uid int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


ALTER TABLE admin_messages
  ADD CONSTRAINT fk_admin_messages_conv
  FOREIGN KEY (admin_conversation_id) REFERENCES admin_conversations (id)
  ON DELETE CASCADE;

ALTER TABLE basket
  ADD CONSTRAINT basket_ibfk_1
  FOREIGN KEY (uid) REFERENCES users (uid)
  ON DELETE CASCADE,
  ADD CONSTRAINT basket_ibfk_2
  FOREIGN KEY (pid) REFERENCES products (pid)
  ON DELETE CASCADE;

ALTER TABLE conversations
  ADD CONSTRAINT fk_conversation_order
  FOREIGN KEY (order_id_fk) REFERENCES orders (id)
  ON DELETE CASCADE;

ALTER TABLE messages
  ADD CONSTRAINT fk_messages_conversation
  FOREIGN KEY (conversation_id) REFERENCES conversations (id)
  ON DELETE CASCADE;

ALTER TABLE order_items
  ADD CONSTRAINT fk_order_items_order
  FOREIGN KEY (order_id_fk) REFERENCES orders (id)
  ON DELETE CASCADE;

ALTER TABLE order_shipments
  ADD CONSTRAINT fk_shipments_order_item
  FOREIGN KEY (order_item_id) REFERENCES order_items (id)
  ON DELETE CASCADE;

ALTER TABLE products
  ADD CONSTRAINT products_ibfk_1
  FOREIGN KEY (uid) REFERENCES users (uid)
  ON DELETE CASCADE;

ALTER TABLE saved_cards
  ADD CONSTRAINT saved_cards_ibfk_1
  FOREIGN KEY (uid) REFERENCES users (uid)
  ON DELETE CASCADE;


