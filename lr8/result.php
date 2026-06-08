<?php
// Лабораторная работа № А-8
// Тема: Основы работы со строковыми данными в PHP. Кодировка. Анализ текста.
// Выполнил: Ильин Кирилл Александрович, группа 241-353

// Указываем браузеру, что страница отдаётся в кодировке UTF-8.
header('Content-Type: text/html; charset=UTF-8');

// Универсальная функция для экранирования вывода в HTML.
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Функция перевода строки в нижний регистр с учётом русского и английского текста.
function lowerText(string $text): string
{
    // Если доступно расширение mbstring, используем корректную multibyte-функцию.
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }

    // Запасной вариант для кириллицы и латиницы, если mbstring не подключен.
    $upper = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяabcdefghijklmnopqrstuvwxyz';

    return strtr($text, $upper, $lower);
}

// Функция разбивает UTF-8 строку на отдельные символы.
function splitSymbols(string $text): array
{
    return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

// Функция делает служебные символы понятными при выводе в таблицу.
function visibleSymbol(string $symbol): string
{
    if ($symbol === ' ') {
        return '[пробел]';
    }

    if ($symbol === "\n") {
        return '[перевод строки]';
    }

    if ($symbol === "\r") {
        return '[возврат каретки]';
    }

    if ($symbol === "\t") {
        return '[табуляция]';
    }

    return $symbol;
}

// Функция считает количество вхождений каждого символа без учёта регистра.
function countSymbols(string $text): array
{
    $symbols = [];
    $lowerText = lowerText($text);

    foreach (splitSymbols($lowerText) as $symbol) {
        if (isset($symbols[$symbol])) {
            $symbols[$symbol]++;
        } else {
            $symbols[$symbol] = 1;
        }
    }

    ksort($symbols, SORT_STRING);
    return $symbols;
}

// Функция считает слова и количество их повторений без учёта регистра.
function countWords(string $text): array
{
    $words = [];
    $lowerText = lowerText($text);

    // Словом считаем последовательность букв: русских или английских.
    preg_match_all('/[\p{L}]+/u', $lowerText, $matches);

    foreach ($matches[0] as $word) {
        if (isset($words[$word])) {
            $words[$word]++;
        } else {
            $words[$word] = 1;
        }
    }

    ksort($words, SORT_STRING);
    return $words;
}

// Основная функция анализа текста.
function analyzeText(string $text): array
{
    $symbols = splitSymbols($text);

    $lettersAmount = 0;
    $lowerLettersAmount = 0;
    $upperLettersAmount = 0;
    $punctuationAmount = 0;
    $digitsAmount = 0;

    foreach ($symbols as $symbol) {
        if (preg_match('/^\p{L}$/u', $symbol)) {
            $lettersAmount++;
        }

        if (preg_match('/^\p{Ll}$/u', $symbol)) {
            $lowerLettersAmount++;
        }

        if (preg_match('/^\p{Lu}$/u', $symbol)) {
            $upperLettersAmount++;
        }

        if (preg_match('/^\p{P}$/u', $symbol)) {
            $punctuationAmount++;
        }

        if (preg_match('/^\p{Nd}$/u', $symbol)) {
            $digitsAmount++;
        }
    }

    $wordList = countWords($text);
    $symbolList = countSymbols($text);

    return [
        'chars' => count($symbols),
        'letters' => $lettersAmount,
        'lower_letters' => $lowerLettersAmount,
        'upper_letters' => $upperLettersAmount,
        'punctuation' => $punctuationAmount,
        'digits' => $digitsAmount,
        'words' => array_sum($wordList),
        'symbol_list' => $symbolList,
        'word_list' => $wordList,
    ];
}

// Получаем текст из формы. trim нужен только для проверки пустого ввода.
$sourceText = $_POST['data'] ?? '';
$hasText = trim($sourceText) !== '';
$analysis = $hasText ? analyzeText($sourceText) : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результат анализа текста | ЛР № А-8</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <div class="header-inner">
        <div class="logo-box">А-8</div>
        <div>
            <h1>Результат анализа текста</h1>
            <p>Ильин Кирилл Александрович | Группа 241-353</p>
            <p>PHP-анализатор строковых данных в кодировке UTF-8</p>
        </div>
    </div>
</header>

<main>
    <section class="card">
        <h2>Исходный текст</h2>

        <?php if ($hasText): ?>
            <div class="src-text"><?php echo nl2br(e($sourceText)); ?></div>
        <?php else: ?>
            <div class="src-error">Нет текста для анализа</div>
        <?php endif; ?>
    </section>

    <?php if ($hasText && $analysis !== null): ?>
        <section class="card">
            <h2>Информация о тексте</h2>
            <table class="result-table">
                <tr>
                    <th>Параметр</th>
                    <th>Значение</th>
                </tr>
                <tr>
                    <td>Количество символов в тексте, включая пробелы</td>
                    <td><?php echo e((string)$analysis['chars']); ?></td>
                </tr>
                <tr>
                    <td>Количество букв</td>
                    <td><?php echo e((string)$analysis['letters']); ?></td>
                </tr>
                <tr>
                    <td>Количество строчных букв</td>
                    <td><?php echo e((string)$analysis['lower_letters']); ?></td>
                </tr>
                <tr>
                    <td>Количество заглавных букв</td>
                    <td><?php echo e((string)$analysis['upper_letters']); ?></td>
                </tr>
                <tr>
                    <td>Количество знаков препинания</td>
                    <td><?php echo e((string)$analysis['punctuation']); ?></td>
                </tr>
                <tr>
                    <td>Количество цифр</td>
                    <td><?php echo e((string)$analysis['digits']); ?></td>
                </tr>
                <tr>
                    <td>Количество слов</td>
                    <td><?php echo e((string)$analysis['words']); ?></td>
                </tr>
            </table>
        </section>

        <section class="card">
            <h2>Вхождения каждого символа</h2>
            <table class="result-table small-table">
                <tr>
                    <th>Символ</th>
                    <th>Количество вхождений</th>
                </tr>
                <?php foreach ($analysis['symbol_list'] as $symbol => $amount): ?>
                    <tr>
                        <td><?php echo e(visibleSymbol($symbol)); ?></td>
                        <td><?php echo e((string)$amount); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <section class="card">
            <h2>Список слов по алфавиту</h2>

            <?php if (count($analysis['word_list']) > 0): ?>
                <table class="result-table small-table">
                    <tr>
                        <th>Слово</th>
                        <th>Количество вхождений</th>
                    </tr>
                    <?php foreach ($analysis['word_list'] as $word => $amount): ?>
                        <tr>
                            <td><?php echo e($word); ?></td>
                            <td><?php echo e((string)$amount); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="note">Слова в тексте не найдены.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="card action-card">
        <a href="index.html" class="another-analysis">Другой анализ</a>
    </section>
</main>

<footer>
    <p>Ильин К.А. ст.уч.гр. 241-353 | ЛР № А-8 | Анализ текста</p>
</footer>
</body>
</html>
