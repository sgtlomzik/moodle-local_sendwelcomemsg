<?php
// local/sendwelcomemsg/run.php

require_once(__DIR__ . '/../../config.php');
require_login();

if (!is_siteadmin()) {
    throw new \moodle_exception('accessdenied', 'admin');
}

// Указываем класс вашей задачи
$taskclassname = '\\local_sendwelcomemsg\\task\\send_welcome_emails';

if (!class_exists($taskclassname)) {
    throw new \moodle_exception('Класс задачи не найден: ' . $taskclassname);
}

// Выполняем задачу
$task = new $taskclassname();
$task->execute();

echo "Задача выполнена.";
