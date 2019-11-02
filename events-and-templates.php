<?php

include 'vendor/autoload.php';

use Askoldex\Teletant\Addons\Menux;
use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Entities\Inline\Article;
use Askoldex\Teletant\Entities\Inline\Base\InputMessageContent;
use Askoldex\Teletant\Entities\Inline\InputTextMessageContent;
use Askoldex\Teletant\Entities\Inline\Result;
use Askoldex\Teletant\Settings;

/*
 * settings.php - возвращает апи токен
 */
$settings = new Settings(include 'settings.php');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);

/*
 * Событие onCommand
 * Приоритет: 3
 * Проверяет прислали ли боту команду (https://core.telegram.org/bots/api#messageentity)
 * MessageEntity -> type = bot_command
 * Пример: отвечаем приветствием на команду /start
 */
$bot->onCommand('start', function (Context $ctx) {
    $ctx->reply('Добро пожаловать!');
});

/*
 * Событие onText
 * Приоритет: 3
 * Проверяет значение поля text у Message (https://core.telegram.org/bots/api#message)
 * В этом событии достуны инструменты шаблонизации
 */
$bot->onText('привет', function (Context $ctx) {
    $ctx->reply('http://neprivet.ru');
});

/*
 * Инструменты шаблонизации
 * Шаблон в поле определяется в следующем формате: {шаблон}
 * Синтаксис шаблона без типа данных: {name}
 * Синтаксис шаблона с типом данных: {name:type}
 * Типы данных шаблонизации:
 * string - соответствует regexp паттерну: [\w\s]+, использует пробелы
 * integer - соответствует regexp паттерну: [\d]+, не использует пробелы
 * float - соответствует regexp паттерну: -?\d+(\.\d+)?, не использует пробелы
 * word - соотвествует regexp паттерну: [\w]+, не использует пробелы
 * any - соотвествует regexp паттерну: (.*?), не использует пробелы и является типом по умолчанию
 * char - соотвествует regexp паттерну: [\w], не использует пробелы
 *
 * Использование пробелов в типах данных создает трудности при парсинге, если подряд идут два
 * типа данных использующие пробелы и при этом разделенные пробелом, пример:
 * смс {title:string} {message:string} - в этом случае, Teletant в переменную title постарается
 * записать всю строку, оставив в message последнее слово
 */
$bot->onText('смс {title:string} {message:string}', function (Context $ctx) {
    $ctx->reply('title: ' . $ctx->var('title') . PHP_EOL .
        'message: ' . $ctx->var('message'));
});
/*
 * и в таком случае нам нужно явно разделить две переменные любым другим символом, либо
 * воспользоваться конструкцией box в шаблонизаторе Teletant.
 * Синтаксис переменной с использованием box: {name:type:box}
 * Где box может быть любым символом, с которого должна начаться переменная и закончиться,
 * пример: sms {title:string:"} {message:string:"}, Данное выражение будет работать так:
 * sms "покупка продуктов" "купи 2кг яблок"
 */
$bot->onText('sms {title:string:"} {message:string:"}', function (Context $ctx) {
    $ctx->reply('title: ' . $ctx->var('title') . PHP_EOL .
        'message: ' . $ctx->var('message'));
});
/*
 * Уже куда удобнее и понятнее, но что если нам нужно указать необязательную переменную?
 * Синтаксис необязательной переменной: {шаблон?}, т.е. вы можете указать и тип и бокс,
 * а в конце поставить знак вопроса и эта переменная станет необязательной.
 * Если вы укажите balance {id?} {balance}, то переменная id автоматически
 * станет обязательной. Этим я обращаю внимание, что необязательные переменные
 * стоит определять в конце всего шаблона
 */
$bot->onText('balance {id?}', function (Context $ctx) {
    $id = $ctx->var('id');
    if ($id != '')
        $ctx->with('id', $id)->reply('Вы посмотрели баланс пользователя: {id}');
    else
        $ctx->reply('Вы посмотрели свой баланс');
});
/*
 * Бывает такое, что пользователи неправильно выполняют команды, нарушают тип данных или вовсе
 * забывают указать переменную, для этого у событий с шаблонизатором есть система валидации
 * функция обработки ошибок передается после функци события и принимает в себя контекст
 * и массив ошибок формата: variable => error
 * Типы ошибок:
 * invalid_type  Неверно указан тип данных
 * not_specified - обязательная переменная не была передана
 */
$bot->onText('profile {id:integer}', function (Context $ctx) {
    $id = $ctx->var('id');
    $ctx->with('id', $id)->reply('Профиль пользователя: {id}');
}, function (Context $ctx, $errors) {
    switch ($errors['id']) {
        case 'invalid_type':
            $ctx->reply('ID должен быть числом');
            break;
        case 'not_specified':
            $ctx->reply('Поле ID обязательно для заполнения');
            break;
    }
});

/*
 * Событие onHears
 * Приоритет: 3
 * ищет подстроку в поле text у Message (https://core.telegram.org/bots/api#message)
 * В этом событии достуны инструменты шаблонизации
 * Событие поддерживает передачу нескольких строк массивом
 */
$bot->onHears(['дурак', 'тупица', 'джсник'], function (Context $ctx) {
    $ctx->reply('Оскорбления запрещены!');
});
$bot->onHears('teletant', function (Context $ctx) {
    $menu = Menux::Create('links')->inline();
    $menu->row()->uBtn('Github', 'https://github.com/askoldex/teletant')
         ->row()->uBtn('Packagist', 'https://packagist.org/packages/askoldex/teletant')
         ->row()->btn('А ютуб?', 'youtube');
    $ctx->replyMarkdown('Ссылки на *Teletant Framework*', $menu);
});

/*
 * Событие onAction
 * Приоритет: 3
 * Проверяет значение поля data у CallbackQuery (https://core.telegram.org/bots/api#callbackquery)
 * В этом событии достуны инструменты шаблонизации
 */
$bot->onAction('youtube', function (Context $ctx) {
    $ctx->editSelf(
        'YouTube',
        Menux::Create('youtube')
            ->inline()
            ->uBtn('Подписаться', 'https://youtube.com/askoldex')
    );
});

/*
 * Событие onInlineQuery
 * Приоритет: 3
 * Проверяет значение поля query у InlineQuery (https://core.telegram.org/bots/api#inlinequery)
 * В этом событии достуны инструменты шаблонизации
 */
$bot->onInlineQuery('прайс', function (Context $ctx) {
    $result = new Result();
    $article = new Article(1);
    $message = new InputTextMessageContent();
    $message->text('1000р за бота');
    $article->title('Разработка бота');
    $article->description('Прайс на разработку бота');
    $article->inputMessageContent($message);
    $article->keyboard(Menux::Create('a')->inline()->btn('просто кнопка')->getAsObject());
    $result->add($article);
    //$ctx->replyInlineQuery($result);
    $ctx->Api()->answerInlineQuery([
        'inline_query_id' => $ctx->getInlineQueryID(),
        'results' => (string) $result
    ]);
});

/*
 * Событие onMessage
 * Приоритет: 2
 * Аналогично onUpdate, но теперь field сверяется с Message (https://core.telegram.org/bots/api#message)
 * Если поле найдено, выполняет функцию.
 * Внимание: у этого события так же высокий приоритет, это событие рекомендуется определять
 * после onText, onCommand, onHears
 * Пример: ищем поле sticker (по факту проверяем, прислали ли нам стикер)
 */
$bot->onMessage('sticker', function (Context $ctx) {
    $ctx->reply('Прикольный стикер:)');
});

/*
 * Событие onUpdate
 * Приоритет: 1
 * Проверяет наличие поля field у Update (https://core.telegram.org/bots/api#update)
 * Если поле найдено, выполняет функцию.
 * Внимание: это событие имеет самый высокий приоритет, поскольку у него самая маленькая
 * глубина проверки (Если вы зададите событие onUpdate('message'..) и после него
 * onMessage('text'...), то событие onMessage никогда не будет выполнено, так как
 * будет сначала срабатывать событие onUpdate, так что РЕКОМЕНДУЕТСЯ onUpdate определять
 * после всех событий с большей глубиной
 * Пример: ищем поле message (по факту проверяем пришло ли обычное сообщение)
 */
$bot->onUpdate('message', function (Context $ctx) {
    $ctx->reply('Вы написали мне какое-то сообщение');
});

$bot->polling();
