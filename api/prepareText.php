<?php

// Подготавливает текст после validate() на сайте к отображению на мобильном устройстве

function prepareText($verifiedText) {
    // Костыль для буквы х, почему-то регулярное выражение
    // воспринимает именно эту букву, как перенос строки
    $processedText = str_replace("х", "&KOSTYL;", $verifiedText);
    $processedText = preg_replace('/\s*\R\s*/', '\n', $processedText);
    $processedText = str_replace("&KOSTYL;", "х", $processedText);
    $processedText = str_replace("<br>", "", $processedText);
    $processedText = str_replace("<ul>", "", $processedText);
    $processedText = str_replace("<li>", "  •  ", $processedText);
    $processedText = str_replace("</li>", "", $processedText);
    $processedText = str_replace("</ul>", "", $processedText);
    $processedText = str_replace("&quot;", '\"', $processedText);
    $processedText = str_replace("&lt;", '<', $processedText);
    $processedText = str_replace("&gt;", '>', $processedText);
    $processedText = str_replace("&#039;", "'", $processedText);
    return $processedText;
}

?>