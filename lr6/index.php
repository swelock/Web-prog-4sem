<?php
// Лабораторная работа № А-6
// Тема: Использование форм для передачи данных в программу PHP. Тест математических знаний.
// Выполнил: Ильин Кирилл Александрович, группа 241-353

// Устанавливаем часовой пояс, чтобы дата и время выводились корректно.
date_default_timezone_set('Europe/Moscow');

// -------------------- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ --------------------

// Функция безопасного вывода текста на HTML-страницу.
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Функция преобразует введённое число к типу float.
// Допускаются десятичные дроби и с точкой, и с запятой.
function parseNumber($value, &$isValid)
{
    $normalized = str_replace(',', '.', trim((string)$value));

    if ($normalized === '' || !is_numeric($normalized)) {
        $isValid = false;
        return 0.0;
    }

    return (float)$normalized;
}

// Функция красиво форматирует число для вывода.
function formatNumber($value)
{
    if ($value === null) {
        return 'невозможно вычислить';
    }

    $rounded = round((float)$value, 2);

    if (abs($rounded) < 0.005) {
        $rounded = 0;
    }

    $text = number_format($rounded, 2, '.', '');
    $text = rtrim(rtrim($text, '0'), '.');

    return str_replace('.', ',', $text);
}

// Функция возвращает случайное число от 0 до 100 с двумя знаками после запятой.
function randomValue()
{
    return mt_rand(0, 10000) / 100;
}

// Функция решает выбранную математическую задачу.
function solveTask($task, $a, $b, $c, &$comment)
{
    $comment = '';

    switch ($task) {
        case 'triangle_area':
            // Площадь треугольника считаем по формуле Герона.
            if ($a <= 0 || $b <= 0 || $c <= 0 || $a + $b <= $c || $a + $c <= $b || $b + $c <= $a) {
                $comment = 'Треугольник с такими сторонами не существует.';
                return null;
            }

            $p = ($a + $b + $c) / 2;
            return sqrt($p * ($p - $a) * ($p - $b) * ($p - $c));

        case 'triangle_perimeter':
            return $a + $b + $c;

        case 'box_volume':
            return $a * $b * $c;

        case 'mean':
            return ($a + $b + $c) / 3;

        case 'sum':
            return $a + $b + $c;

        case 'product':
            return $a * $b * $c;

        case 'sum_squares':
            return $a * $a + $b * $b + $c * $c;

        case 'max_value':
            return max($a, $b, $c);

        default:
            $comment = 'Неизвестный тип задачи.';
            return null;
    }
}

// Функция убирает HTML-теги из отчёта для отправки обычным текстовым письмом.
function htmlReportToPlainText($html)
{
    $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
    $text = strip_tags($text);
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// -------------------- ИСХОДНЫЕ ДАННЫЕ ДЛЯ СТРАНИЦЫ --------------------

// Массив задач для селектора.
$tasks = [
    'triangle_area' => 'Площадь треугольника',
    'triangle_perimeter' => 'Периметр треугольника',
    'box_volume' => 'Объем параллелепипеда',
    'mean' => 'Среднее арифметическое',
    'sum' => 'Сумма чисел',
    'product' => 'Произведение чисел',
    'sum_squares' => 'Сумма квадратов',
    'max_value' => 'Максимальное число'
];

// Массив вариантов внешнего вида результата.
$viewTypes = [
    'browser' => 'Версия для просмотра в браузере',
    'print' => 'Версия для печати'
];

// Проверяем, была ли отправлена форма.
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isSubmitted = ($requestMethod === 'POST' && isset($_POST['A']));

// Значения для полей формы при первой загрузке или при повторном тесте.
$formFio = isset($_GET['fio']) ? $_GET['fio'] : '';
$formGroup = isset($_GET['group']) ? $_GET['group'] : '';
$formA = formatNumber(randomValue());
$formB = formatNumber(randomValue());
$formC = formatNumber(randomValue());

// Переменные результата.
$outText = '';
$emailMessage = '';
$viewType = 'browser';
$testPassed = false;
$taskComment = '';
$numbersAreValid = true;
$answerStatus = '';

// -------------------- ОБРАБОТКА ДАННЫХ ФОРМЫ --------------------

if ($isSubmitted) {
    $fio = trim((string)($_POST['FIO'] ?? ''));
    $group = trim((string)($_POST['GROUP'] ?? ''));
    $about = trim((string)($_POST['ABOUT'] ?? ''));
    $task = (string)($_POST['TASK'] ?? 'mean');
    $answerRaw = trim((string)($_POST['result'] ?? ''));
    $email = trim((string)($_POST['MAIL'] ?? ''));
    $sendMail = array_key_exists('send_mail', $_POST);
    $viewType = array_key_exists(($_POST['VIEW'] ?? ''), $viewTypes) ? $_POST['VIEW'] : 'browser';

    $a = parseNumber($_POST['A'] ?? '', $numbersAreValid);
    $b = parseNumber($_POST['B'] ?? '', $numbersAreValid);
    $c = parseNumber($_POST['C'] ?? '', $numbersAreValid);

    $programResult = null;

    if ($numbersAreValid) {
        $programResult = solveTask($task, $a, $b, $c, $taskComment);
    } else {
        $taskComment = 'Одно или несколько значений A, B, C введены некорректно.';
    }

    if ($answerRaw === '') {
        $answerStatus = 'Задача самостоятельно решена не была';
        $testPassed = false;
    } else {
        $answerIsValid = true;
        $userAnswer = parseNumber($answerRaw, $answerIsValid);

        if (!$answerIsValid || $programResult === null) {
            $testPassed = false;
        } else {
            // Для вещественных чисел используем погрешность 0.01, чтобы ответ 3.33 и 3.333 считались совпадающими.
            $testPassed = abs($userAnswer - $programResult) <= 0.01;
        }
    }

    $taskTitle = $tasks[$task] ?? 'Неизвестная задача';

    // Формируем отчёт один раз, чтобы использовать его и для браузера, и для письма.
    $outText .= '<p><strong>ФИО:</strong> ' . h($fio !== '' ? $fio : 'не указано') . '</p>';
    $outText .= '<p><strong>Группа:</strong> ' . h($group !== '' ? $group : 'не указана') . '</p>';
    $outText .= '<p><strong>Сведения о студенте:</strong> ' . h($about !== '' ? $about : 'не заполнено') . '</p>';
    $outText .= '<p><strong>Тип задачи:</strong> ' . h($taskTitle) . '</p>';
    $outText .= '<p><strong>Входные данные:</strong> A = ' . h(formatNumber($a)) . ', B = ' . h(formatNumber($b)) . ', C = ' . h(formatNumber($c)) . '</p>';
    $outText .= '<p><strong>Предполагаемый результат:</strong> ' . h($answerRaw !== '' ? $answerRaw : $answerStatus) . '</p>';
    $outText .= '<p><strong>Результат, вычисленный программой:</strong> ' . h(formatNumber($programResult)) . '</p>';

    if ($taskComment !== '') {
        $outText .= '<p><strong>Комментарий:</strong> ' . h($taskComment) . '</p>';
    }

    if ($testPassed) {
        $outText .= '<p class="success"><strong>Тест пройден</strong></p>';
    } else {
        $outText .= '<p class="error"><strong>Ошибка: тест не пройден</strong></p>';
    }

    if ($sendMail) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subject = 'Результат математического теста';
            $message = htmlReportToPlainText($outText);
            $headers = "From: auto@mami.ru\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

            // На локальном сервере функция mail() может не отправить письмо без настройки почтового сервера.
            @mail($email, $subject, $message, $headers);

            $emailMessage = 'Результаты теста были автоматически отправлены на e-mail ' . h($email) . '.';
        } else {
            $emailMessage = 'E-mail указан некорректно, поэтому письмо не было отправлено.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ильин Кирилл Александрович | 241-353 | ЛР № А-6</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef2f7;
            color: #222222;
            line-height: 1.5;
        }

        header {
            background: #243447;
            color: #ffffff;
            padding: 22px 34px;
        }

        header h1 {
            font-size: 24px;
            margin-bottom: 6px;
        }

        header p {
            font-size: 15px;
            opacity: 0.92;
        }

        main {
            padding: 28px 34px 42px;
        }

        .card {
            max-width: 920px;
            background: #ffffff;
            border: 1px solid #d8dde6;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            margin-bottom: 18px;
            color: #243447;
            font-size: 22px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 190px 340px;
            align-items: center;
            gap: 12px;
            margin-bottom: 13px;
        }

        label {
            font-weight: 700;
            color: #333333;
        }

        input[type="text"],
        input[type="email"],
        textarea,
        select {
            width: 340px;
            padding: 10px 12px;
            border: 1px solid #aeb7c4;
            border-radius: 7px;
            font-size: 15px;
            font-family: Arial, Helvetica, sans-serif;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
        }

        .checkbox-row {
            display: grid;
            grid-template-columns: 190px 340px;
            align-items: center;
            gap: 12px;
            margin-bottom: 13px;
        }

        .checkbox-box {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 400;
        }

        #emailBlock {
            display: none;
        }

        button {
            margin-left: 202px;
            padding: 11px 26px;
            border: 1px solid #172536;
            border-radius: 7px;
            background: #243447;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #172536;
        }

        .report {
            max-width: 860px;
        }

        .report p {
            margin-bottom: 10px;
        }

        .success {
            color: #0f7a28;
            font-size: 18px;
        }

        .error {
            color: #b00020;
            font-size: 18px;
        }

        .mail-info {
            margin-top: 14px;
            padding: 12px 14px;
            border-left: 4px solid #243447;
            background: #f0f4fa;
        }

        .repeat-button {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 18px;
            border: 1px solid #243447;
            border-radius: 7px;
            background: #ffffff;
            color: #243447;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .repeat-button:hover {
            background: #243447;
            color: #ffffff;
        }

        .print-mode {
            background: #ffffff;
            color: #000000;
        }

        .print-mode header {
            background: #ffffff;
            color: #000000;
            border-bottom: 1px solid #000000;
        }

        .print-mode .card {
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
        }

        .print-mode main {
            padding: 24px 34px;
        }

        footer {
            padding: 15px 34px;
            background: #243447;
            color: #ffffff;
            text-align: center;
            font-size: 14px;
        }

        .print-mode footer {
            background: #ffffff;
            color: #000000;
            border-top: 1px solid #000000;
        }

        @media (max-width: 700px) {
            header,
            main {
                padding-left: 18px;
                padding-right: 18px;
            }

            .form-row,
            .checkbox-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            input[type="text"],
            input[type="email"],
            textarea,
            select {
                width: 100%;
            }

            button {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
    <script>
        function toggleEmailBlock(checkbox) {
            var emailBlock = document.getElementById('emailBlock');

            if (checkbox.checked) {
                emailBlock.style.display = 'grid';
            } else {
                emailBlock.style.display = 'none';
            }
        }
    </script>
</head>
<body class="<?php echo ($isSubmitted && $viewType === 'print') ? 'print-mode' : ''; ?>">
<header>
    <h1>Лабораторная работа № А-6</h1>
    <p>Ильин Кирилл Александрович | Группа 241-353 | Тест математических знаний</p>
</header>

<main>
    <?php if ($isSubmitted): ?>
        <section class="card report">
            <h2>Результаты теста</h2>

            <?php echo $outText; ?>

            <?php if ($emailMessage !== ''): ?>
                <p class="mail-info"><?php echo $emailMessage; ?></p>
            <?php endif; ?>

            <?php if ($viewType === 'browser'): ?>
                <a class="repeat-button" href="?fio=<?php echo urlencode($fio); ?>&group=<?php echo urlencode($group); ?>">Повторить тест</a>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="card">
            <h2>Форма математического теста</h2>

            <form name="math_test" method="post" action="">
                <div class="form-row">
                    <label for="FIO">ФИО</label>
                    <input type="text" id="FIO" name="FIO" value="<?php echo h($formFio); ?>" required>
                </div>

                <div class="form-row">
                    <label for="GROUP">Номер группы</label>
                    <input type="text" id="GROUP" name="GROUP" value="<?php echo h($formGroup); ?>" required>
                </div>

                <div class="form-row">
                    <label for="ABOUT">Немного о себе</label>
                    <textarea id="ABOUT" name="ABOUT" placeholder="Например: студент Московского Политеха, изучаю PHP"></textarea>
                </div>

                <div class="form-row">
                    <label for="TASK">Математическая задача</label>
                    <select id="TASK" name="TASK">
                        <?php foreach ($tasks as $taskKey => $taskTitle): ?>
                            <option value="<?php echo h($taskKey); ?>"><?php echo h($taskTitle); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="A">Значение A</label>
                    <input type="text" id="A" name="A" value="<?php echo h($formA); ?>" required>
                </div>

                <div class="form-row">
                    <label for="B">Значение B</label>
                    <input type="text" id="B" name="B" value="<?php echo h($formB); ?>" required>
                </div>

                <div class="form-row">
                    <label for="C">Значение C</label>
                    <input type="text" id="C" name="C" value="<?php echo h($formC); ?>" required>
                </div>

                <div class="form-row">
                    <label for="result">Ваш ответ</label>
                    <input type="text" id="result" name="result" placeholder="Введите свой результат">
                </div>

                <div class="checkbox-row">
                    <span></span>
                    <label class="checkbox-box">
                        <input type="checkbox" name="send_mail" value="1" onclick="toggleEmailBlock(this)">
                        Отправить результат теста по e-mail
                    </label>
                </div>

                <div class="form-row" id="emailBlock">
                    <label for="MAIL">Ваш e-mail</label>
                    <input type="email" id="MAIL" name="MAIL" placeholder="student@example.com">
                </div>

                <div class="form-row">
                    <label for="VIEW">Вид результата</label>
                    <select id="VIEW" name="VIEW">
                        <?php foreach ($viewTypes as $viewKey => $viewTitle): ?>
                            <option value="<?php echo h($viewKey); ?>"><?php echo h($viewTitle); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Проверить</button>
            </form>
        </section>
    <?php endif; ?>
</main>

<footer>
    <?php echo 'Ильин К.А. ст. уч. гр. 241-353, ЛР6 | Сформировано ' . date('d.m.Y') . ' в ' . date('H:i:s'); ?>
</footer>
</body>
</html>
