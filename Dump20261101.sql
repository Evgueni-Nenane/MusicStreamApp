DROP DATABASE IF EXISTS `discocompacto`;
CREATE DATABASE `discocompacto` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `discocompacto`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: discocompacto
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Cantor`
--

DROP TABLE IF EXISTS `Cantor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Cantor` (
  `Codigo_Cantor` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Cantor` varchar(50) NOT NULL,
  `Apelido_Cantor` varchar(50) DEFAULT NULL,
  `Contacto_Cantor` varchar(20) DEFAULT NULL,
  `Email_Cantor` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Cantor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Cantor`
--

LOCK TABLES `Cantor` WRITE;
/*!40000 ALTER TABLE `Cantor` DISABLE KEYS */;
INSERT INTO `Cantor` VALUES (1,'Drake','Lincolm','860402975','drake@gmail.com'),(2,'Alana','Pietro','840000000','alana@gmail.com'),(3,'Kendrick','Lamar','840000000','kendrick@gmail.com');
/*!40000 ALTER TABLE `Cantor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Cantor_DC`
--

DROP TABLE IF EXISTS `Cantor_DC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Cantor_DC` (
  `Codigo_Cantor` int(11) NOT NULL,
  `Codigo_DC` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Cantor`,`Codigo_DC`),
  KEY `Codigo_DC` (`Codigo_DC`),
  CONSTRAINT `cantor_dc_ibfk_1` FOREIGN KEY (`Codigo_Cantor`) REFERENCES `Cantor` (`Codigo_Cantor`) ON DELETE CASCADE,
  CONSTRAINT `cantor_dc_ibfk_2` FOREIGN KEY (`Codigo_DC`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Cantor_DC`
--

LOCK TABLES `Cantor_DC` WRITE;
/*!40000 ALTER TABLE `Cantor_DC` DISABLE KEYS */;
INSERT INTO `Cantor_DC` VALUES (1,1),(1,6),(2,5),(2,7);
/*!40000 ALTER TABLE `Cantor_DC` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Compositor`
--

DROP TABLE IF EXISTS `Compositor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Compositor` (
  `Codigo_Compositor` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Compositor` varchar(50) NOT NULL,
  `Apelido_Compositor` varchar(50) DEFAULT NULL,
  `Contacto_Compositor` varchar(20) DEFAULT NULL,
  `Email_Compositor` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Compositor`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Compositor`
--

LOCK TABLES `Compositor` WRITE;
/*!40000 ALTER TABLE `Compositor` DISABLE KEYS */;
INSERT INTO `Compositor` VALUES (1,'Daren','Langa','85493849034','daren@gmail.com'),(2,'Clemente','Pietro','8455555554','cleen@gmail.com');
/*!40000 ALTER TABLE `Compositor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Compositor_DC`
--

DROP TABLE IF EXISTS `Compositor_DC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Compositor_DC` (
  `Codigo_Compositor` int(11) NOT NULL,
  `Codigo_DC` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Compositor`,`Codigo_DC`),
  KEY `Codigo_DC` (`Codigo_DC`),
  CONSTRAINT `compositor_dc_ibfk_1` FOREIGN KEY (`Codigo_Compositor`) REFERENCES `Compositor` (`Codigo_Compositor`) ON DELETE CASCADE,
  CONSTRAINT `compositor_dc_ibfk_2` FOREIGN KEY (`Codigo_DC`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Compositor_DC`
--

LOCK TABLES `Compositor_DC` WRITE;
/*!40000 ALTER TABLE `Compositor_DC` DISABLE KEYS */;
INSERT INTO `Compositor_DC` VALUES (1,1),(1,6),(1,7);
/*!40000 ALTER TABLE `Compositor_DC` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Disco_Compacto`
--

DROP TABLE IF EXISTS `Disco_Compacto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Disco_Compacto` (
  `Codigo_Disco` int(11) NOT NULL AUTO_INCREMENT,
  `Titulo` varchar(150) NOT NULL,
  `Genero` varchar(50) DEFAULT NULL,
  `Preco` decimal(10,2) DEFAULT NULL,
  `Ano_Edicao` int(11) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Disco`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Disco_Compacto`
--

LOCK TABLES `Disco_Compacto` WRITE;
/*!40000 ALTER TABLE `Disco_Compacto` DISABLE KEYS */;
INSERT INTO `Disco_Compacto` VALUES (1,'MORE LIFE',NULL,16998.00,2017),(5,'A Peca',NULL,1235.00,2026),(6,'Take Care (DELUXE)',NULL,2160.00,2011),(7,'Otrs VCs',NULL,1250.00,2026);
/*!40000 ALTER TABLE `Disco_Compacto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Disco_Genero`
--

DROP TABLE IF EXISTS `Disco_Genero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Disco_Genero` (
  `Codigo_DC` int(11) NOT NULL,
  `Codigo_Genero` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_DC`,`Codigo_Genero`),
  KEY `Codigo_Genero` (`Codigo_Genero`),
  CONSTRAINT `disco_genero_ibfk_1` FOREIGN KEY (`Codigo_DC`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE,
  CONSTRAINT `disco_genero_ibfk_2` FOREIGN KEY (`Codigo_Genero`) REFERENCES `Genero` (`Codigo_Genero`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Disco_Genero`
--

LOCK TABLES `Disco_Genero` WRITE;
/*!40000 ALTER TABLE `Disco_Genero` DISABLE KEYS */;
INSERT INTO `Disco_Genero` VALUES (1,2),(1,3),(5,1),(6,2),(6,3),(7,1),(7,5),(7,8);
/*!40000 ALTER TABLE `Disco_Genero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Edicao`
--

DROP TABLE IF EXISTS `Edicao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Edicao` (
  `Codigo_Disco` int(11) NOT NULL,
  `Data_Edicao` date DEFAULT NULL,
  `Codigo_Editora` int(11) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Disco`),
  KEY `Codigo_Editora` (`Codigo_Editora`),
  CONSTRAINT `edicao_ibfk_1` FOREIGN KEY (`Codigo_Disco`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE,
  CONSTRAINT `edicao_ibfk_2` FOREIGN KEY (`Codigo_Editora`) REFERENCES `Editora` (`Codigo_Editora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Edicao`
--

LOCK TABLES `Edicao` WRITE;
/*!40000 ALTER TABLE `Edicao` DISABLE KEYS */;
INSERT INTO `Edicao` VALUES (1,'2018-01-15',1),(5,'2026-10-08',1),(6,'2011-11-15',1),(7,'2026-10-16',3);
/*!40000 ALTER TABLE `Edicao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Editora`
--

DROP TABLE IF EXISTS `Editora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Editora` (
  `Codigo_Editora` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Editora` varchar(100) NOT NULL,
  `Email_Editora` varchar(100) DEFAULT NULL,
  `Contacto_Editora` varchar(20) DEFAULT NULL,
  `Endereco` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Editora`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Editora`
--

LOCK TABLES `Editora` WRITE;
/*!40000 ALTER TABLE `Editora` DISABLE KEYS */;
INSERT INTO `Editora` VALUES (1,'OSO','oso@gmail.com','85493849034','Moçambique'),(2,'Obra','mili@gmail.com','85493849034','Portugal'),(3,'main','main@2gmail.com','85493849034','Portugal');
/*!40000 ALTER TABLE `Editora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Faixa`
--

DROP TABLE IF EXISTS `Faixa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Faixa` (
  `idFaixa` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Faixa` varchar(150) NOT NULL,
  `Artista_Principal` varchar(100) DEFAULT NULL,
  `Duracao` time DEFAULT NULL,
  `Numero_Faixa` int(11) DEFAULT NULL,
  `idDisco` int(11) DEFAULT NULL,
  PRIMARY KEY (`idFaixa`),
  KEY `idDisco` (`idDisco`),
  CONSTRAINT `faixa_ibfk_1` FOREIGN KEY (`idDisco`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Faixa`
--

LOCK TABLES `Faixa` WRITE;
/*!40000 ALTER TABLE `Faixa` DISABLE KEYS */;
INSERT INTO `Faixa` VALUES (2,'Teenage Fever','DRAKE','03:40:00',2,1),(3,'Medo','Anna joyce','03:06:00',1,5),(4,'Over my dead body','DRAKE','03:00:00',3,1),(5,'Nothing into Somethings','DRAKE','02:33:00',4,1),(6,'Do not Disturb','DRAKE','03:16:00',5,1),(7,'Headlines','DRAKE','03:55:00',1,6),(8,'Marvis Room','DRAKE, Marvin','05:47:00',2,6),(10,'Lord Knows','DRAKE, Rick Ross','05:08:00',4,6),(12,'Take care','DRAKE, Rihanna','03:05:00',6,6),(14,'Paz','Daren Langa','06:14:00',2,7);
/*!40000 ALTER TABLE `Faixa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Faixa_Cantor`
--

DROP TABLE IF EXISTS `Faixa_Cantor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Faixa_Cantor` (
  `Codigo_Faixa` int(11) NOT NULL,
  `Codigo_Cantor` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Faixa`,`Codigo_Cantor`),
  KEY `Codigo_Cantor` (`Codigo_Cantor`),
  CONSTRAINT `faixa_cantor_ibfk_1` FOREIGN KEY (`Codigo_Faixa`) REFERENCES `Faixa` (`idFaixa`) ON DELETE CASCADE,
  CONSTRAINT `faixa_cantor_ibfk_2` FOREIGN KEY (`Codigo_Cantor`) REFERENCES `Cantor` (`Codigo_Cantor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Faixa_Cantor`
--

LOCK TABLES `Faixa_Cantor` WRITE;
/*!40000 ALTER TABLE `Faixa_Cantor` DISABLE KEYS */;
INSERT INTO `Faixa_Cantor` VALUES (2,1),(4,1),(5,1),(6,1),(7,1),(8,1),(10,1),(12,1);
/*!40000 ALTER TABLE `Faixa_Cantor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Faixa_Compositor`
--

DROP TABLE IF EXISTS `Faixa_Compositor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Faixa_Compositor` (
  `Codigo_Faixa` int(11) NOT NULL,
  `Codigo_Compositor` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Faixa`,`Codigo_Compositor`),
  KEY `Codigo_Compositor` (`Codigo_Compositor`),
  CONSTRAINT `faixa_compositor_ibfk_1` FOREIGN KEY (`Codigo_Faixa`) REFERENCES `Faixa` (`idFaixa`) ON DELETE CASCADE,
  CONSTRAINT `faixa_compositor_ibfk_2` FOREIGN KEY (`Codigo_Compositor`) REFERENCES `Compositor` (`Codigo_Compositor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Faixa_Compositor`
--

LOCK TABLES `Faixa_Compositor` WRITE;
/*!40000 ALTER TABLE `Faixa_Compositor` DISABLE KEYS */;
INSERT INTO `Faixa_Compositor` VALUES (2,1),(4,1),(5,1),(6,1),(7,1),(8,1),(10,1),(12,1),(14,1);
/*!40000 ALTER TABLE `Faixa_Compositor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Faixa_Musico`
--

DROP TABLE IF EXISTS `Faixa_Musico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Faixa_Musico` (
  `Codigo_Faixa` int(11) NOT NULL,
  `Codigo_Musico` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Faixa`,`Codigo_Musico`),
  KEY `Codigo_Musico` (`Codigo_Musico`),
  CONSTRAINT `faixa_musico_ibfk_1` FOREIGN KEY (`Codigo_Faixa`) REFERENCES `Faixa` (`idFaixa`) ON DELETE CASCADE,
  CONSTRAINT `faixa_musico_ibfk_2` FOREIGN KEY (`Codigo_Musico`) REFERENCES `Musico` (`Codigo_Musico`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Faixa_Musico`
--

LOCK TABLES `Faixa_Musico` WRITE;
/*!40000 ALTER TABLE `Faixa_Musico` DISABLE KEYS */;
INSERT INTO `Faixa_Musico` VALUES (2,1),(5,1),(6,1),(7,1),(10,2),(12,2);
/*!40000 ALTER TABLE `Faixa_Musico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Faixa_Produtor`
--

DROP TABLE IF EXISTS `Faixa_Produtor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Faixa_Produtor` (
  `Codigo_Faixa` int(11) NOT NULL,
  `Codigo_Produtor` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Faixa`,`Codigo_Produtor`),
  KEY `Codigo_Produtor` (`Codigo_Produtor`),
  CONSTRAINT `faixa_produtor_ibfk_1` FOREIGN KEY (`Codigo_Faixa`) REFERENCES `Faixa` (`idFaixa`) ON DELETE CASCADE,
  CONSTRAINT `faixa_produtor_ibfk_2` FOREIGN KEY (`Codigo_Produtor`) REFERENCES `Produtor` (`Codigo_Prod`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Faixa_Produtor`
--

LOCK TABLES `Faixa_Produtor` WRITE;
/*!40000 ALTER TABLE `Faixa_Produtor` DISABLE KEYS */;
/*!40000 ALTER TABLE `Faixa_Produtor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Genero`
--

DROP TABLE IF EXISTS `Genero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Genero` (
  `Codigo_Genero` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Genero` varchar(50) NOT NULL,
  PRIMARY KEY (`Codigo_Genero`),
  UNIQUE KEY `Nome_Genero` (`Nome_Genero`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Genero`
--

LOCK TABLES `Genero` WRITE;
/*!40000 ALTER TABLE `Genero` DISABLE KEYS */;
INSERT INTO `Genero` VALUES (5,'Amapiano'),(4,'KICKS'),(1,'Kizomba'),(7,'KUDURO'),(6,'marabenta'),(3,'R&B'),(2,'RAP'),(8,'semba');
/*!40000 ALTER TABLE `Genero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Gravadora`
--

DROP TABLE IF EXISTS `Gravadora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Gravadora` (
  `Codigo_Gravadora` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Gravadora` varchar(100) NOT NULL,
  `Email_Gravadora` varchar(100) DEFAULT NULL,
  `Endereco_Gravadora` varchar(150) DEFAULT NULL,
  `Contacto_Gravadora` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Gravadora`),
  UNIQUE KEY `Nome_Gravadora` (`Nome_Gravadora`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Gravadora`
--

LOCK TABLES `Gravadora` WRITE;
/*!40000 ALTER TABLE `Gravadora` DISABLE KEYS */;
INSERT INTO `Gravadora` VALUES (1,'OVO','ovo@gmail.com','Portugal','85493849034'),(2,'FreeBanz','banz@gmail.com','EUA','85493849034'),(3,'De','cleen@gmail.com','Moçambique','entretenimento'),(5,'plural','pablo@gmail.com','Portugal','840000009');
/*!40000 ALTER TABLE `Gravadora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GravadoraDisco`
--

DROP TABLE IF EXISTS `GravadoraDisco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GravadoraDisco` (
  `Codigo_Disco` int(11) NOT NULL,
  `Codigo_Gravadora` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Disco`,`Codigo_Gravadora`),
  KEY `Codigo_Gravadora` (`Codigo_Gravadora`),
  CONSTRAINT `gravadoradisco_ibfk_1` FOREIGN KEY (`Codigo_Disco`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE,
  CONSTRAINT `gravadoradisco_ibfk_2` FOREIGN KEY (`Codigo_Gravadora`) REFERENCES `Gravadora` (`Codigo_Gravadora`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GravadoraDisco`
--

LOCK TABLES `GravadoraDisco` WRITE;
/*!40000 ALTER TABLE `GravadoraDisco` DISABLE KEYS */;
INSERT INTO `GravadoraDisco` VALUES (1,1),(6,1),(7,2);
/*!40000 ALTER TABLE `GravadoraDisco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Instrumento`
--

DROP TABLE IF EXISTS `Instrumento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Instrumento` (
  `Codigo` int(11) NOT NULL AUTO_INCREMENT,
  `NomeInstrumento` varchar(50) NOT NULL,
  PRIMARY KEY (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Instrumento`
--

LOCK TABLES `Instrumento` WRITE;
/*!40000 ALTER TABLE `Instrumento` DISABLE KEYS */;
INSERT INTO `Instrumento` VALUES (1,'Guitarra'),(2,'Violino'),(3,'Violão'),(4,'Bateria'),(5,'Baixo'),(6,'Piano'),(7,'Flauta'),(8,'flauta'),(9,'viols');
/*!40000 ALTER TABLE `Instrumento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Logs`
--

DROP TABLE IF EXISTS `Logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Logs` (
  `Codigo` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(50) DEFAULT NULL,
  `Apelido` varchar(50) DEFAULT NULL,
  `Perfil` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Accao` varchar(255) DEFAULT NULL,
  `Hora` datetime DEFAULT NULL,
  PRIMARY KEY (`Codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `Musico`
--

DROP TABLE IF EXISTS `Musico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Musico` (
  `Codigo_Musico` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Art_Musico` varchar(50) DEFAULT NULL,
  `Nome_Musico` varchar(50) NOT NULL,
  `Apelido_Musico` varchar(50) DEFAULT NULL,
  `Contacto_Musico` varchar(20) DEFAULT NULL,
  `Email_Musico` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Musico`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Musico`
--

LOCK TABLES `Musico` WRITE;
/*!40000 ALTER TABLE `Musico` DISABLE KEYS */;
INSERT INTO `Musico` VALUES (1,NULL,'Kennedy','pietro','840000000','kennedyp479@gmail.com'),(2,NULL,'Marley','Mahel','849694252','marley123@gmail.com'),(3,'Anna','Anna','joyce','8455555555','anna@gmail.com'),(5,NULL,'Edmazia','Mayembe','8496942525','edmazia@gmail.com');
/*!40000 ALTER TABLE `Musico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Musico_DC`
--

DROP TABLE IF EXISTS `Musico_DC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Musico_DC` (
  `Codigo_Musico` int(11) NOT NULL,
  `Codigo_DC` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Musico`,`Codigo_DC`),
  KEY `Codigo_DC` (`Codigo_DC`),
  CONSTRAINT `musico_dc_ibfk_1` FOREIGN KEY (`Codigo_Musico`) REFERENCES `Musico` (`Codigo_Musico`) ON DELETE CASCADE,
  CONSTRAINT `musico_dc_ibfk_2` FOREIGN KEY (`Codigo_DC`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Musico_DC`
--

LOCK TABLES `Musico_DC` WRITE;
/*!40000 ALTER TABLE `Musico_DC` DISABLE KEYS */;
INSERT INTO `Musico_DC` VALUES (1,1),(1,6),(2,6),(3,5),(5,7);
/*!40000 ALTER TABLE `Musico_DC` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Musico_Instrumento`
--

DROP TABLE IF EXISTS `Musico_Instrumento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Musico_Instrumento` (
  `Codigo_Musico` int(11) NOT NULL,
  `Codigo_Instr` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Musico`,`Codigo_Instr`),
  KEY `Codigo_Instr` (`Codigo_Instr`),
  CONSTRAINT `musico_instrumento_ibfk_1` FOREIGN KEY (`Codigo_Musico`) REFERENCES `Musico` (`Codigo_Musico`) ON DELETE CASCADE,
  CONSTRAINT `musico_instrumento_ibfk_2` FOREIGN KEY (`Codigo_Instr`) REFERENCES `Instrumento` (`Codigo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Musico_Instrumento`
--

LOCK TABLES `Musico_Instrumento` WRITE;
/*!40000 ALTER TABLE `Musico_Instrumento` DISABLE KEYS */;
INSERT INTO `Musico_Instrumento` VALUES (1,1),(1,4),(2,1),(2,3),(3,3),(5,4),(5,5);
/*!40000 ALTER TABLE `Musico_Instrumento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NivelAcesso`
--

DROP TABLE IF EXISTS `NivelAcesso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NivelAcesso` (
  `CodigoNivel` int(11) NOT NULL AUTO_INCREMENT,
  `NomeNivel` varchar(50) NOT NULL,
  `PodeLer` tinyint(1) NOT NULL DEFAULT 1,
  `PodeAdicionar` tinyint(1) NOT NULL DEFAULT 0,
  `PodeEditar` tinyint(1) NOT NULL DEFAULT 0,
  `PodeRemover` tinyint(1) NOT NULL DEFAULT 0,
  `PodeUtilizadores` tinyint(1) NOT NULL DEFAULT 0,
  `PodeLogs` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CodigoNivel`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NivelAcesso`
--

LOCK TABLES `NivelAcesso` WRITE;
/*!40000 ALTER TABLE `NivelAcesso` DISABLE KEYS */;
INSERT INTO `NivelAcesso` VALUES (1,'Operador',1,1,0,0,0,0),(2,'SuperOperador',1,1,1,1,0,0),(3,'Administrador',1,1,1,1,1,0),(4,'Auditor',1,1,1,1,0,1),(7,'perfil de Stanley',1,0,0,0,1,1),(10,'Estagiario',1,1,1,1,1,1);
/*!40000 ALTER TABLE `NivelAcesso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ProDC`
--

DROP TABLE IF EXISTS `ProDC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProDC` (
  `Codigo_Disco` int(11) NOT NULL,
  `Codigo_Produtor` int(11) NOT NULL,
  PRIMARY KEY (`Codigo_Disco`,`Codigo_Produtor`),
  KEY `Codigo_Produtor` (`Codigo_Produtor`),
  CONSTRAINT `prodc_ibfk_1` FOREIGN KEY (`Codigo_Disco`) REFERENCES `Disco_Compacto` (`Codigo_Disco`) ON DELETE CASCADE,
  CONSTRAINT `prodc_ibfk_2` FOREIGN KEY (`Codigo_Produtor`) REFERENCES `Produtor` (`Codigo_Prod`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProDC`
--

LOCK TABLES `ProDC` WRITE;
/*!40000 ALTER TABLE `ProDC` DISABLE KEYS */;
INSERT INTO `ProDC` VALUES (1,1),(6,1),(7,1);
/*!40000 ALTER TABLE `ProDC` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Produtor`
--

DROP TABLE IF EXISTS `Produtor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Produtor` (
  `Codigo_Prod` int(11) NOT NULL AUTO_INCREMENT,
  `Nome_Prod` varchar(50) NOT NULL,
  `Apelido_Produtor` varchar(50) DEFAULT NULL,
  `Nome_Art_Prod` varchar(50) DEFAULT NULL,
  `Contacto_Prod` varchar(20) DEFAULT NULL,
  `Email_Prod` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Codigo_Prod`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Produtor`
--

LOCK TABLES `Produtor` WRITE;
/*!40000 ALTER TABLE `Produtor` DISABLE KEYS */;
INSERT INTO `Produtor` VALUES (1,'Drizzy','Mahel',NULL,'8455555555','drizzy@gmail.com'),(3,'kenedy','pietro',NULL,'840000000','kennedyp47@gmail.com');
/*!40000 ALTER TABLE `Produtor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Utilizador`
--

DROP TABLE IF EXISTS `Utilizador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Utilizador` (
  `Codigo_User` int(11) NOT NULL AUTO_INCREMENT,
  `Nome` varchar(50) NOT NULL,
  `Apelido` varchar(50) DEFAULT NULL,
  `UserName` varchar(50) NOT NULL,
  `Genero` varchar(20) DEFAULT NULL,
  `Perfil` int(11) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Contacto` varchar(20) DEFAULT NULL,
  `Senha` varchar(255) DEFAULT NULL,
  `Primeiro_Acesso` tinyint(1) DEFAULT 1,
  `foto` longblob DEFAULT NULL,
  `foto_tipo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Codigo_User`),
  UNIQUE KEY `UserName` (`UserName`),
  KEY `Perfil` (`Perfil`),
  CONSTRAINT `utilizador_ibfk_1` FOREIGN KEY (`Perfil`) REFERENCES `NivelAcesso` (`CodigoNivel`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-01 13:00:45
