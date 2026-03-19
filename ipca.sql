CREATE DATABASE ipca;
USE ipca;

CREATE TABLE users (
    login VARCHAR(50) PRIMARY KEY,
    pwd VARCHAR(255) NOT NULL,
    grupo INT,
    email VARCHAR(100)
);

CREATE TABLE grupos (
    ID INT PRIMARY KEY,
    GRUPO VARCHAR(50)
);

