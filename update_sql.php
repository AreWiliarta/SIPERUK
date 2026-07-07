<?php
$content = file_get_contents('database.sql');
$content = str_replace("'ACTIVE')", "'ACTIVE', 'uploads/rooms/kelas_default.jpg')", $content);
$content = str_replace("(`name`, `capacity`, `facilities`, `location`, `status`)", "(`name`, `capacity`, `facilities`, `location`, `status`, `image`)", $content);
file_put_contents('database.sql', $content);
echo 'Success';
