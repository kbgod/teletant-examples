<?php

use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;

include 'vendor/autoload.php';

/*
 * В этом примернике мы рассмотрим настройки (Settings)
 * В конструктор Settings ля короткой записи мы можем передать токен
 */
$settings = new Settings('token');
$settings->setApiToken('token');

/*
 * Следующий по важности параметр, это ответ на вебхук при первой отправке любого запроса
 * (sendMessage и тд)
 *
 * Значение по умолчанию: true
 */
$settings->setHookOnFirstRequest(false);

/*
 * Для установки proxy используется следующий метод
 * Синтаксис: protocol://user:password@server:port
 */
$settings->setProxy('http://127.0.0.1:7060');

/*
 * Для замены сервера Telegram следующий метод
 */
$settings->setBaseUri('https://youtube.com/');