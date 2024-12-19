# Two-Factor Authentication System

## Description
This project implements a simple two-factor authentication (2FA) system using QR codes compatible with Google Authenticator and JWT (JavaScript Web Token) for secure user authentication.

![image](https://github.com/user-attachments/assets/967ad96f-d12b-4072-9755-97cf91ecb76a)

## Objective
- Develop a 2FA login system with encrypted passwords stored in the database.
- Demonstrate the use of JWT for managing user authentication data.

![image](https://github.com/user-attachments/assets/316ad96c-ee05-4332-a00f-ed2d99e66af7)

## Tools and Technologies
- **PHP** for backend logic.
- **MySQL** for database management.
- **endroid/qr-code** package for QR code generation.
- **firebase/jwt** for JWT generation and validation.
- **Tailwind CSS** and **daisyUI** for a user-friendly interface.

![image](https://github.com/user-attachments/assets/3322e56b-887c-4eb7-9309-09f0d7f8e780)

## Key Features
- Secure 2FA with QR codes.
- Encrypted passwords and salting for data security.
- JWT for secure session handling.
- User-friendly UI design.

## Key Takeaways
- **Data Security:** Implementing salted hashed passwords ensures user data safety.
- **Identity Management:** TOTP codes add an extra layer of security with 2FA.
- **Risk Minimization:** TOTP codes prevent phishing and brute-force attacks.
- **Scalability:** The system is designed to be flexible and scalable for future needs.
