-- otaku_haven_schema.sql
-- This SQL file sets up the MySQL database and tables for the Otaku Haven website.
-- It includes Users, Authors, Books, Stationery, Orders, Items, Payments, Reviews.

-- Create the database
CREATE DATABASE IF NOT EXISTS otakuhaven;
USE otakuhaven;

-- Users table: stores customers and admins
CREATE TABLE Users (
  UserID INT AUTO_INCREMENT PRIMARY KEY,
  Username VARCHAR(255),
  Password VARCHAR(255),
  Email VARCHAR(255) UNIQUE,
  Role VARCHAR(50),
  RegistrationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  ShippingAddress TEXT
);

-- Authors table: used for books
CREATE TABLE Authors (
  AuthorID INT AUTO_INCREMENT PRIMARY KEY,
  Name VARCHAR(255),
  Bio TEXT
);

-- Books table
CREATE TABLE Books (
  BookID INT AUTO_INCREMENT PRIMARY KEY,
  Title VARCHAR(255),
  AuthorID INT,
  Genre VARCHAR(100),
  Price DECIMAL(10,2),
  Stock INT,
  FOREIGN KEY (AuthorID) REFERENCES Authors(AuthorID)
);

-- Stationery table
CREATE TABLE Stationery (
  StationeryID INT AUTO_INCREMENT PRIMARY KEY,
  Name VARCHAR(255),
  Category VARCHAR(100),
  Price DECIMAL(10,2),
  Stock INT
);

-- Orders table
CREATE TABLE Orders (
  OrderID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT,
  OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  Status VARCHAR(50),
  FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- Book order items
CREATE TABLE BookOrderItems (
  OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
  OrderID INT,
  BookID INT,
  Quantity INT,
  PriceAtOrder DECIMAL(10,2),
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
);

-- Stationery order items
CREATE TABLE StationeryOrderItems (
  OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
  OrderID INT,
  StationeryID INT,
  Quantity INT,
  PriceAtOrder DECIMAL(10,2),
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
  FOREIGN KEY (StationeryID) REFERENCES Stationery(StationeryID)
);

-- Payments table
CREATE TABLE Payments (
  PaymentID INT AUTO_INCREMENT PRIMARY KEY,
  OrderID INT,
  PaymentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  Amount DECIMAL(10,2),
  PaymentMethod VARCHAR(50),
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID)
);

-- Reviews table
CREATE TABLE Reviews (
  ReviewID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT,
  BookID INT,
  ReviewText TEXT,
  Rating INT,
  FOREIGN KEY (UserID) REFERENCES Users(UserID),
  FOREIGN KEY (BookID) REFERENCES Books(BookID)
);

CREATE TABLE Wishlist (
  WishlistID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT,
  BookID INT NULL,
  StationeryID INT NULL,
  FOREIGN KEY (UserID) REFERENCES Users(UserID),
  FOREIGN KEY (BookID) REFERENCES Books(BookID),
  FOREIGN KEY (StationeryID) REFERENCES Stationery(StationeryID)
);
