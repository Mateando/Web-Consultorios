<?php
$db=new PDO('sqlite:'.__DIR__.'/../database/database.sqlite');
$hash=password_hash('secret123', PASSWORD_BCRYPT);
$st=$db->prepare('UPDATE users SET password=? WHERE email=?');
$st->execute([$hash,'admin@consultorio.com']);
echo "updated admin password\n";
