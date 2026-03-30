-- Online Donation / Charity Management System
-- Database: charity_db
-- Created for BCSE302L DA-2

CREATE DATABASE IF NOT EXISTS charity_db;
USE charity_db;

-- ADMIN Table
CREATE TABLE IF NOT EXISTS Admin (
    Admin_ID VARCHAR(10) PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL
);

-- CATEGORY Table
CREATE TABLE IF NOT EXISTS Category (
    Category_ID VARCHAR(10) PRIMARY KEY,
    Category_Name VARCHAR(100) NOT NULL
);

-- DONOR Table
CREATE TABLE IF NOT EXISTS Donor (
    Donor_ID VARCHAR(10) PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Phone VARCHAR(15) NOT NULL,
    Address TEXT,
    Password VARCHAR(255) NOT NULL
);

-- CHARITY Table
CREATE TABLE IF NOT EXISTS Charity (
    Charity_ID VARCHAR(10) PRIMARY KEY,
    Charity_Name VARCHAR(150) NOT NULL,
    Registration_No VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Address TEXT,
    Description TEXT,
    Category_ID VARCHAR(10),
    FOREIGN KEY (Category_ID) REFERENCES Category(Category_ID) ON DELETE SET NULL
);

-- CAMPAIGN Table
CREATE TABLE IF NOT EXISTS Campaign (
    Campaign_ID VARCHAR(10) PRIMARY KEY,
    Title VARCHAR(200) NOT NULL,
    Description TEXT,
    Target_Amount DECIMAL(12,2) NOT NULL,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Status ENUM('Active','Completed','Paused','Cancelled') DEFAULT 'Active',
    Charity_ID VARCHAR(10),
    FOREIGN KEY (Charity_ID) REFERENCES Charity(Charity_ID) ON DELETE SET NULL
);

-- PAYMENT Table
CREATE TABLE IF NOT EXISTS Payment (
    Payment_ID VARCHAR(10) PRIMARY KEY,
    Payment_Mode ENUM('UPI','Credit Card','Debit Card','Net Banking','Cash') NOT NULL,
    Transaction_Status ENUM('Success','Failed','Pending') DEFAULT 'Pending',
    Transaction_Date DATE NOT NULL
);

-- DONATION Table
CREATE TABLE IF NOT EXISTS Donation (
    Donation_ID VARCHAR(10) PRIMARY KEY,
    Amount DECIMAL(10,2) NOT NULL,
    Donation_Date DATE NOT NULL,
    Donor_ID VARCHAR(10),
    Campaign_ID VARCHAR(10),
    Payment_ID VARCHAR(10),
    FOREIGN KEY (Donor_ID) REFERENCES Donor(Donor_ID) ON DELETE SET NULL,
    FOREIGN KEY (Campaign_ID) REFERENCES Campaign(Campaign_ID) ON DELETE SET NULL,
    FOREIGN KEY (Payment_ID) REFERENCES Payment(Payment_ID) ON DELETE SET NULL
);

-- =============================================
-- SAMPLE DATA
-- =============================================

INSERT INTO Admin VALUES
('A01', 'System Admin', 'admin@charity.com', MD5('admin123'));

INSERT INTO Category VALUES
('C01', 'Education'),
('C02', 'Healthcare'),
('C03', 'Disaster Aid'),
('C04', 'Animal Welfare'),
('C05', 'Environment');

INSERT INTO Donor VALUES
('D101', 'Rahul Kumar',  'rahul@gmail.com',  '9876543210', 'Chennai',   MD5('r@123')),
('D102', 'Anita Singh',  'anita@gmail.com',  '9123456780', 'Bangalore', MD5('a@456')),
('D103', 'Vikram Mehta', 'vikram@gmail.com', '9988776655', 'Mumbai',    MD5('v@789')),
('D104', 'Priya Das',    'priya@gmail.com',  '8877665544', 'Delhi',     MD5('p@321'));

INSERT INTO Charity VALUES
('CH01', 'Help India Trust',    'REG123', 'help@india.org',        '9988776655', 'Delhi',   'Education Support',   'C01'),
('CH02', 'Care Foundation',     'REG456', 'care@foundation.org',   '8899776655', 'Mumbai',  'Medical Assistance',  'C02'),
('CH03', 'Green Earth Society', 'REG789', 'green@earth.org',       '7788996655', 'Pune',    'Environment Care',    'C05'),
('CH04', 'Disaster Relief India','REG012','relief@india.org',      '6677885544', 'Chennai', 'Disaster Aid',        'C03');

INSERT INTO Campaign VALUES
('CP01', 'Educate Children',   'School Supplies for underprivileged children', 500000,  '2025-01-01', '2025-03-31', 'Active',    'CH01'),
('CP02', 'Medical Relief',     'Surgery Support for low-income patients',      800000,  '2025-02-01', '2025-04-30', 'Active',    'CH02'),
('CP03', 'Plant 10000 Trees',  'Reforestation campaign across Karnataka',      300000,  '2025-03-01', '2025-06-30', 'Active',    'CH03'),
('CP04', 'Flood Relief Fund',  'Aid for flood-affected families in Assam',    1000000, '2025-07-01', '2025-09-30', 'Paused',    'CH04');

INSERT INTO Payment VALUES
('P001', 'UPI',         'Success', '2025-02-10'),
('P002', 'Credit Card', 'Success', '2025-02-12'),
('P003', 'Net Banking', 'Success', '2025-03-01'),
('P004', 'Debit Card',  'Failed',  '2025-03-05'),
('P005', 'UPI',         'Success', '2025-03-10');

INSERT INTO Donation VALUES
('DN01', 10000, '2025-02-10', 'D101', 'CP01', 'P001'),
('DN02', 15000, '2025-02-12', 'D102', 'CP02', 'P002'),
('DN03', 25000, '2025-03-01', 'D103', 'CP03', 'P003'),
('DN04',  5000, '2025-03-10', 'D101', 'CP04', 'P005');
