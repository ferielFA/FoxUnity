# 🎮 FoxUnity Gaming Platform

## 📌 Description

**FoxUnity** is a complete **web gaming platform** developed as part of a **Web Development course**.
It is built using **PHP (OOP)**, **MVC architecture**, and **PDO**, and provides multiple interconnected modules such as **event management, trading system, shop, articles, news, user management, and reclamations**.

The platform aims to bring gamers together by offering a unified ecosystem where users can **trade items, participate in events, read news and articles, manage profiles, and submit reclamations**—all in one place.

---

## 📑 Table of Contents

* [Features](#-features)
* [Architecture](#-architecture)
* [Database Structure](#-database-structure)
* [Prerequisites](#-prerequisites)
* [Installation](#-installation)
* [Usage](#-usage)
* [Security](#-security)
* [Technologies Used](#-technologies-used)
* [Contribution](#-contribution)
* [License](#-license)
* [Author](#-author)

---

## 🚀 Features

### 🎉 Event Management (CRUD)

* Create, read, update, and delete gaming events
* Interactive calendar view for upcoming events
* Event status management (upcoming, ongoing, completed, cancelled)
* Digital ticketing system:

  * QR code generation for each ticket
  * QR code scanning to validate access
* User registration and unregistration

### 🔄 Trading System

* Create trade offers between users
* View trade history
* Accept or reject trades
* Secure item exchange logic
* AI-assisted trading:

  * Suggest fair trades based on item value
  * Analyze past trades
  * Recommend skins based on user history
  * Display personal trade statistics

### 🛒 Shop Module

* Display gaming items
* Purchase items using platform currency
* Coupon system:

  * Percentage or fixed-amount discounts
  * Expiration date and usage limit
* Admin management of items and coupons

### 📰 News Module

* Publish official announcements and gaming news
* Homepage display of latest news
* Categorization (Updates, Events, Platform News)
* Admin content moderation

### 👤 User Management

* User profiles
* Activity history (events, trades, purchases)
* FrontOffice / BackOffice access

**Advanced User Features (Métier avancé)**:

* Secure authentication system
* Email verification after registration
* Forgot password workflow (email reset link)
* Google OAuth login
* CAPTCHA protection on forms
* Facial recognition authentication (experimental)

*Integrated AI Chatbot*:

* Navigation assistance
* Answers about events, trades, and shop
* Dedicated chatbot page:

```
http://localhost/projet_web/view/front/chatbot.php
```

---

## 🏗️ Architecture

The project follows a **Model-View-Controller (MVC)** architecture:

```
projet_web/
├── model/              # Business entities
├── view/               # User interfaces
│   ├── back/           # Administration views
│   └── front/          # User views
├── controller/         # Business logic
├── config/             # Database configuration
└── database.sql        # Database schema and test data
```

### ✔ MVC Benefits

* Clear separation of concerns
* Maintainable and scalable code
* Easy debugging and extension

---

## 🗂️ Database Structure

### Main Tables

* **users** – platform users
* **evenement** – gaming events
* **participation** – event registrations
* **trade** – user trades
* **skins** – user trades
* **purchase** – user trades
* **produit** – store items
* **article** – gaming articles
* **Categorie** – platform news
* **reclamation** – user complaints
  **reponse** – user complaints

Foreign keys ensure relational integrity.

---

## 🧰 Prerequisites

Before installing the project, make sure you have:

* **XAMPP** (Apache + MySQL)
* **PHP 8.0 or higher**
* **Composer** (PHP dependency manager)
* **Git** installed on your machine
* A modern web browser (Chrome, Firefox, Edge)

---

## ⚙️ Installation

1. Clone the repository:

```bash
git clone https://github.com/ferielFA/FoxUnity.git
cd FoxUnity
```

2. Move the project to XAMPP `htdocs`:

```
C:\xampp\htdocs\projet_web\
```

3. Start **Apache** and **MySQL** from XAMPP

4. Create the database:

* Open `http://localhost/phpmyadmin`
* Create a database named:

```
foxunity0
```

5. Import the database:

* Import the file `database.sql`

6. Configure database connection in:

```
config/database.php
```

```php
private static $host = 'localhost';
private static $dbname = 'foxunity0';
private static $username = 'root';
private static $password = '';
```

---

## ▶️ Usage

### BackOffice (Admin)

Manage:

* Events
* Users
* Trades
* Shop items
* News & articles
* Reclamations

URL example:

```
http://localhost/projet_web/view/back/
```

### FrontOffice (Users)

Users can:

* Browse and join events
* Trade items
* Purchase from shop
* Read news and articles
* Submit reclamations

URL example:

```
http://localhost/projet_web/view/front/
```

---

## 🔐 Security

* PDO prepared statements
* Protection against SQL Injection
* Input validation
* Output sanitization with `htmlspecialchars()`
* Exception-based error handling

---

## 🧰 Technologies Used

* **Backend**: PHP 8 (OOP)
* **Database**: MySQL
* **Architecture**: MVC
* **Database Access**: PDO
* **Frontend**: HTML5, CSS3
* **Icons**: Font Awesome
* **Server**: Apache (XAMPP)

---

## 🤝 Contribution

To contribute:

1. Fork the repository
2. Create a new branch:

```bash
git checkout -b feature-name
```

3. Commit your changes:

```bash
git add .
git commit -m "Add new feature"
```

4. Push to your branch:

```bash
git push origin feature-name
```

5. Open a Pull Request

---

## 📄 License

This project is licensed under the **MIT License**.

---

## ✨ Author

Developed as part of the **Web Development Course (PW)**

* Project: **FoxUnity Gaming Platform**
* Academic Year: **2025–2026**

---

🎮 **FoxUnity – Made by gamers, for gamers**
