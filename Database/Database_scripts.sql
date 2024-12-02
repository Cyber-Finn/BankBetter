CREATE DATABASE BankBetter;
USE BankBetter;

-- 1. Create the Users 
CREATE TABLE Users (
    user_id INT UNIQUE NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_active BOOLEAN NOT NULL DEFAULT TRUE
);

-- 2. Create the different account types (Savings, credit, immediate payments, etc.) and fees table
CREATE TABLE ACCOUNT_TYPES (
    at_id INT NOT NULL UNIQUE PRIMARY KEY,
    at_description VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO account_types (at_id, at_description) VALUES (1, 'Standard');
INSERT INTO account_types (at_id, at_description) VALUES (2, 'Realtime');

CREATE TABLE FEES (
    fee_id INT NOT NULL AUTO_INCREMENT UNIQUE PRIMARY KEY,
    fee DECIMAL(18, 2) NOT NULL,
    at_id INT NOT NULL,
    FOREIGN KEY (at_id) REFERENCES ACCOUNT_TYPES(at_id)
);

INSERT INTO FEES (fee, at_id) VALUES (0.50, 1);
INSERT INTO FEES (fee, at_id) VALUES (1.50, 2);

-- 3. Create the Accounts
CREATE TABLE Accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_type INT NOT NULL,
    balance DECIMAL(18, 2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (account_type) REFERENCES ACCOUNT_TYPES(at_id)
);
