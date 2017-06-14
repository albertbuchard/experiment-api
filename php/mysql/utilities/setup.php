<?php

// Load the utilities
// Check if request for config file generation
// -- There was a post sent with db connection variabes -- check db connection and user exists
// -- Check if there was a user info, if yes generate the user with password - if not generate a root user with random password
// -- Generate the config file
// If there was no variable set check if
// Config file exists
// Db connection is possible
// Users table exist

/*

SQL FOR USERS TABLE :
CREATE TABLE `users` (
  `userId` text,
  `password` text,
  `logKey` text,
  `logKeyTime` bigint(20) DEFAULT NULL,
  `logIp` text,
  `type` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
);



 */
