<?php

use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;

include 'vendor/autoload.php';

/*
 * settings.php - возвращает апи токен
 */
$settings = new Settings(include 'settings.php');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);

/*
 * У объекта Bot и Stage есть методы middlewares и eventMiddlewares, которые
 * реализуют работу промежуточных функций, проще говоря, мидлвары это слои,
 * которые получают контекст, что-то с ним делают (всяческие проверки) и решают
 * пропускать далее или нет. У Teletant Framework есть два типа мидлваров, ниже приведен
 * пример глобальных мидлваров.
 */
$privateChatMiddleware = function (Context $ctx, callable $next) {
    if($ctx->getChatType() == 'private') {
        $next($ctx);
    }
};
/*
 * Выше мы создали функцию мидлвар, которая принимает контекст и следующий слой,
 * если условие выполнится, то функция запустит следующий мидлвар.
 * Теперь чтобы зарегистрировать мидлвар, воспользуемся методом middleware(array)
 */
$bot->middlewares([
    $privateChatMiddleware
]);
/*
 * Порядок мидлваров в массиве, является порядком обработки этих мидлваров, так что стоит
 * выбирать порядок с умом.
 *
 * С текущим набором мидлваров, все наши события будут срабатывать только в личном чате,
 * Давайте добавим еще один мидлвар, который будет просто выводить id пользователя
 * в терминал.
 */

$bot->middlewares([
    $privateChatMiddleware,
    function (Context $ctx, callable $next) {
        echo $ctx->getUserID().PHP_EOL;
        $next($ctx);
    }
]);
/*
 * Кодом выше мы просто перезаписали список мидлваров, в рабочем боте глобальные
 * миддлвары стоит прописывать сразу правильные.
 *
 * Теперь когда мы знаем id пользователя, давайте создадим админ команду и для этого
 * создадим мидлвар события.
 * Метод eventMiddlewares ассоциативный массив групп мидлваров. Возможные варианты регистрации
 * мидлваров:
 * eventMiddlewares(['admin' => function(Context, next)]) - Так мы регистрируем мидлвар
 * admin, у которого всего одна функция, если для проверки нужно несколько функций, то
 * вариант записи следующий:
 * eventMiddlewares(['admin' => [function, function]]);
 * Теперь перейдем к практике и создадим мидлвар на проверку админа
 */
$bot->eventMiddlewares([
    'admin' => function (Context $ctx, callable $next) {
        if($ctx->getUserID() == 115533432) {
            $next($ctx);
        }
    }
]);
/*
 * мидлвар admin зарегистрирован, теперь чтобы защитить им события нужно воспользоваться
 * методом em, который принимает строку мидлваров разделенных запятой и
 * функцию с объектом Bot или Stage, и все созданные события
 * внутри этой функции, будут защищены указанными мидлварами.
 * Перейдем в практике и защитим команду balance мидлваром admin
 */

$bot->em('admin', function(Bot $bot) {
    $bot->onCommand('balance', function (Context $ctx) {
        $ctx->reply('Поздравляю, вы админ');
    });
});
/*
 * в методе em, передавая через запятую имена групп мидлваров, вы задаете им аналогичный
 * порядок проверки
 */

$bot->onCommand('start', function (Context $ctx) {
    $ctx->reply('Добро пожаловать');
});

$bot->polling();