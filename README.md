# 💻 Laptop Price Comparison Platform (Web So Sánh Giá Laptop)

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Puppeteer](https://img.shields.io/badge/Puppeteer-Scraping-40B5A4?style=for-the-badge&logo=puppeteer&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC-FF9900?style=for-the-badge)

A comprehensive and scalable web application built to aggregate, compare, and track laptop prices from multiple e-commerce platforms. This project demonstrates full-stack development skills, data extraction (web scraping), database design, and modern DevOps practices using Docker.

## 🌟 Key Features

- **Price Comparison Engine:** Aggregates and compares laptop prices from different sources to help users find the best deals.
- **Price History Tracking:** Records and visualizes the historical pricing of products to identify sale trends.
- **Automated Web Scraping:** Uses **Puppeteer** to extract real-time product data and pricing from e-commerce websites.
- **Advanced User Authentication:** Secure registration, login, and token-based password reset system.
- **Personalized Experience:** Users can bookmark favorite products and view their personal search history.
- **AI Chatbot Integration:** Built-in AI chat system (`chat_history` structured) to assist users in finding the right laptop.

## 🛠 Tech Stack & Architecture

### Backend
- **Core:** Raw PHP 8.2 with a custom **MVC (Model-View-Controller)** architecture.
- **Database Interaction:** Secure queries using **PDO (PHP Data Objects)** to prevent SQL Injection.
- **Scraping Engine:** Node.js & Puppeteer for dynamic data extraction.

### Database
- **MySQL 8.0**: Relational database optimized with proper indexing, cascading foreign keys, and unique constraints.

### DevOps & Deployment
- **Docker & Docker Compose:** Containerized environment ensuring consistency across development and production.
- **Apache Web Server:** Configured with URL rewriting and output buffering.

## 🚀 How to Run Locally

With Docker, setting up the project takes less than a minute.

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd "web so sánh giá laptop"
   ```

2. **Start the containers using Docker Compose:**
   ```bash
   docker-compose up -d --build
   ```

3. **Access the application:**
   Open your browser and navigate to: `http://localhost:8080/`

*(Note: The database is automatically initialized and seeded with sample data via `data1.sql` upon the first build).*

## 💡 Why This Project Stands Out (For Recruiters)

This project was built from scratch without relying on heavy backend frameworks like Laravel or Symfony. It demonstrates a deep understanding of core programming principles:

1. **Software Architecture:** Implemented a clean, custom MVC pattern, showing strong foundational knowledge of how modern frameworks operate under the hood.
2. **Database Design:** The schema is highly normalized, utilizing foreign keys (`ON DELETE CASCADE`), boolean flags, and datetime tracking for robust data integrity.
3. **Data Engineering:** Integrated a web scraping pipeline (Puppeteer) to solve a real-world problem—data aggregation.
4. **Infrastructure as Code:** Fully containerized the application, eliminating the "it works on my machine" problem, a crucial skill in modern Agile teams.
5. **Security Best Practices:** Applied PDO for database security, password hashing for user data, and secure token generation for password resets.

---
*Created as a showcase of Full-stack Engineering, Database Design, and DevOps practices.*