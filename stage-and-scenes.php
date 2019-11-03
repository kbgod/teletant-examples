<?php

use Askoldex\Teletant\Addons\LocalStorage;
use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;

include 'vendor/autoload.php';

/*
 * settings.php - возвращает апи токен
 */
$settings = new Settings(include 'settings.php');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);

/*
 * В этом примернике мы поработаем со сценами, и чтобы сцены работали нам нужно в контекст
 * передать объект имплементирующий класс StorageInterface, в Teletant Framework
 * есть стандартное хранилище LocalStorage, но оно подходит только для тестов во время
 * разработки без использования базы данных.
 */
$storage = new LocalStorage();
/*
 * Сразу же определим главную сцену (По факту это как бот внутри бота, на продакшене
 * вы вряд ли будете использовать больше одной главной сцены (Stage)
 */
$stage = new Stage();
/*
 * Теперь создадим две простые сценки, чтобы показать весь их функционал.
 */
$stickerScene = new Scene('sticker');
$messageScene = new Scene('message');
/*
 * Сценки (Scene) имеют конструктор событий как Bot и дополнительно два события:
 * onEnter - срабатывает, когда входят в сцену ($ctx->enter('scene'))
 * onLeave - срабатывает, когда выходят с сцены ($ctx->leave())
 * Внимание! Эти два события нельзя защитить мидлварами событий,
 * так как они вызываются не пользователем, а разработчиком бота.
 * Перейдем к простенькому заполнению:
 */
$stickerScene->onEnter(function (Context $ctx) {
    $ctx->reply('Отправьте мне стикер');
});
$stickerScene->onMessage('sticker', function (Context $ctx) {
    $ctx->reply('Классный стикер:)');
});
$stickerScene->onCommand('exit', function (Context $ctx) {
    $ctx->leave();
});
$stickerScene->onLeave(function (Context $ctx) {
    $ctx->reply('Возвращайся!');
});
/*
 * Первая сцена готова, при входе в сцену, бот попросит нас прислать стикер,
 * если мы отправим стикер, бот на это ответит. Если мы отправим команду /exit,
 * то бот выйдет с сцены и попрощается. Выход с сцены подразумевает переход к обработке
 * событий заданных в Bot
 *
 * Теперь давайте сделаем так, что в первой сцене если мы отправим любое сообщение,
 * то бот перейдет в вторую сцену.
 */
$messageScene->onEnter(function (Context $ctx) {
    $ctx->reply('Отправьте любое сообщение');
});
$messageScene->onUpdate('message', function (Context $ctx) {
    $ctx->enter('sticker');
});

$stage->addScene($stickerScene);
$stage->addScene($messageScene);
/*
 * Сцены готовы, теперь давайте зарегистрируем нужные мидлвары для работы сцен.
 */
$bot->middlewares([
    function(Context $ctx, callable $next) use ($storage) {
        $storage->boot($ctx);
        $ctx->setStorage($storage);
        $next($ctx);
    },
    $stage->middleware()
]);
/*
 * Можете заметить, что я сначала определил Storage, для того чтобы мидлвар сцены
 * смог отработать, если до мидлвара не будет определено хранилище,
 * будет выброшено исключение StageException.
 *
 * Почти все готово, осталось войти в первую сцену
 */
$bot->onCommand('go', function (Context $ctx) {
    $ctx->enter('message');
    /*
     * В метод enter так же можно передать объект сцены
     * $ctx->enter($messageScene);
     */
});

$bot->polling();