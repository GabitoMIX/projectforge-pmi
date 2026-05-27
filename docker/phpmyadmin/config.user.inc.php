<?php
// Configuración adicional de phpMyAdmin para ProjectForge PMI.

// La base de datos MariaDB se comunica por la red interna de Docker.
// No está expuesta públicamente, por eso se marca el host "db" como seguro
// para evitar el aviso de SSL dentro de phpMyAdmin.
$cfg['MysqlSslWarningSafeHosts'] = ['db'];