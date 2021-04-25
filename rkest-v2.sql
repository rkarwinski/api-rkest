CREATE DATABASE  IF NOT EXISTS `rkest` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `rkest`;
-- MySQL dump 10.13  Distrib 5.7.33, for Linux (x86_64)
--
-- Host: localhost    Database: rkest
-- ------------------------------------------------------
-- Server version	5.7.33-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `parametros`
--

DROP TABLE IF EXISTS `parametros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parametros` (
  `nome` varchar(220) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spotify_playlist_tracks`
--

DROP TABLE IF EXISTS `spotify_playlist_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spotify_playlist_tracks` (
  `id` varchar(250) NOT NULL,
  `playlist_id` varchar(250) NOT NULL,
  `titulo` varchar(220) DEFAULT NULL,
  `artista` varchar(220) DEFAULT NULL,
  `url_externa` varchar(250) DEFAULT NULL,
  `url_api` varchar(250) DEFAULT NULL,
  `url_imagem` varchar(250) DEFAULT NULL,
  `id_artista` varchar(220) NOT NULL,
  `url_artista` varchar(250) DEFAULT NULL,
  `id_album` varchar(220) NOT NULL,
  `titulo_album` varchar(220) DEFAULT NULL,
  `url_album` varchar(250) DEFAULT NULL,
  `url_external_album` varchar(250) DEFAULT NULL,
  `data_criado` datetime DEFAULT NULL,
  `data_atualizado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`playlist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spotify_playlists`
--

DROP TABLE IF EXISTS `spotify_playlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spotify_playlists` (
  `id` varchar(220) NOT NULL,
  `user_id` varchar(220) NOT NULL,
  `titulo` varchar(220) DEFAULT NULL,
  `url_externa` varchar(250) DEFAULT NULL,
  `url_api` varchar(250) DEFAULT NULL,
  `url_musicas` varchar(250) DEFAULT NULL,
  `url_imagem` text,
  `total_musicas` int(11) DEFAULT NULL,
  `data_criado` datetime DEFAULT NULL,
  `data_atualizado` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spotify_tokens_usuarios`
--

DROP TABLE IF EXISTS `spotify_tokens_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spotify_tokens_usuarios` (
  `id_user` varchar(100) NOT NULL,
  `token` text,
  `reflesh_token` text,
  `data_gerado` datetime DEFAULT NULL,
  `data_expirado` datetime DEFAULT NULL,
  UNIQUE KEY `usuarios_tokens_UN` (`id_user`),
  CONSTRAINT `usuarios_tokens_FK_copy` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `senha` varchar(220) NOT NULL,
  `data_criado` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios_plataformas`
--

DROP TABLE IF EXISTS `usuarios_plataformas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_plataformas` (
  `id_user` varchar(100) NOT NULL,
  `plataform` varchar(100) NOT NULL,
  `id_plataform` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_user`,`plataform`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios_tokens`
--

DROP TABLE IF EXISTS `usuarios_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_tokens` (
  `id_user` varchar(100) NOT NULL,
  `token` text,
  `data_gerado` datetime DEFAULT NULL,
  `data_expirado` datetime DEFAULT NULL,
  UNIQUE KEY `usuarios_tokens_UN` (`id_user`),
  CONSTRAINT `usuarios_tokens_FK` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `youtube_playlist_tracks`
--

DROP TABLE IF EXISTS `youtube_playlist_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `youtube_playlist_tracks` (
  `id` varchar(250) NOT NULL,
  `playlist_id` varchar(250) NOT NULL,
  `video_id` varchar(250) NOT NULL,
  `titulo` varchar(220) DEFAULT NULL,
  `canal` varchar(220) DEFAULT NULL,
  `canal_id` varchar(250) DEFAULT NULL,
  `url_imagem` varchar(250) DEFAULT NULL,
  `data_criado` datetime DEFAULT NULL,
  `data_atualizado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`playlist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `youtube_playlists`
--

DROP TABLE IF EXISTS `youtube_playlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `youtube_playlists` (
  `id` varchar(220) NOT NULL,
  `user_id` varchar(220) NOT NULL,
  `titulo` varchar(220) DEFAULT NULL,
  `canal_id` varchar(220) DEFAULT NULL,
  `url_imagem` text,
  `data_criado` datetime DEFAULT NULL,
  `data_atualizado` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `youtube_tokens_usuarios`
--

DROP TABLE IF EXISTS `youtube_tokens_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `youtube_tokens_usuarios` (
  `id_user` varchar(100) NOT NULL,
  `token` text,
  `reflesh_token` text,
  `data_gerado` datetime DEFAULT NULL,
  `data_expirado` datetime DEFAULT NULL,
  UNIQUE KEY `usuarios_ut_tokens_UN` (`id_user`),
  CONSTRAINT `usuarios_ut_tokens_FK_copy` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-04-25 16:54:52
