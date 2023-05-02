<?php

require 'vendor/autoload.php';

use League\HTMLToMarkdown\HtmlConverter;

// Generate a new converter

$converter = new HtmlConverter();

// Custom filters 

function divFeaturePath($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $divs = $xpath->query('//div[@class="feature-path"]');

    foreach ($divs as $div) {
        $newDiv = $dom->createDocumentFragment();
        $newDiv->appendXML("`" . $div->nodeValue . "`" . PHP_EOL . PHP_EOL);
        $div->parentNode->replaceChild($newDiv, $div);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function divWellInfo($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $divs = $xpath->query('//div[@class="well info gtk mt-md"]');

    foreach ($divs as $div) {
        $newDiv = $dom->createDocumentFragment();
        $newDiv->appendXML("|| " . $div->nodeValue . PHP_EOL . PHP_EOL);
        $div->parentNode->replaceChild($newDiv, $div);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function spanBlack($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $spans = $xpath->query('//span[@style="color: #000000;"]');

    foreach ($spans as $span) {
        $newSpan = $dom->createDocumentFragment();
        $newSpan->appendXML(PHP_EOL . "**" . $span->nodeValue . "**");
        $span->parentNode->replaceChild($newSpan, $span);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function spanRed($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $spans = $xpath->query('//span[@style="color: #ff0000;"]');

    foreach ($spans as $span) {
        $newSpan = $dom->createDocumentFragment();
        $newSpan->appendXML("**" . $span->nodeValue . "**");
        $span->parentNode->replaceChild($newSpan, $span);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function divWell($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $divs = $xpath->query('//div[@class="well"]');

    foreach ($divs as $div) {
        $newDiv = $dom->createDocumentFragment();
        $newDiv->appendXML("| " . $div->nodeValue . PHP_EOL . PHP_EOL);
        $div->parentNode->replaceChild($newDiv, $div);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function regularDiv($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $xpath = new DOMXPath($dom);
    $divs = $xpath->query('//div');

    foreach ($divs as $div) {
        $newDiv = $dom->createDocumentFragment();
        $newDiv->appendXML(PHP_EOL . $div->nodeValue . PHP_EOL);
        $div->parentNode->replaceChild($newDiv, $div);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

function regularSpan($html)
{
    $decodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom = new DOMDocument();
    $dom->loadHTML($decodedHtml);

    $spans = $dom->getElementsByTagName('span');

    foreach ($spans as $span) {
        $newSpan = $dom->createDocumentFragment();
        $newSpan->appendXML($span->nodeValue);
        $span->parentNode->replaceChild($newSpan, $span);
    }

    $newHtml = $dom->saveHTML();

    return $newHtml;
}

// connect to the database

$configData = parse_ini_file('config.ini');

try {
    $pdo = new PDO(
        $configData['DB_TYPE'] . ':host=' . $configData['DB_HOST'] . ';dbname=' . $configData['DB_NAME'],
        $configData['DB_USER'],
        $configData['DB_PASS']
    );
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// get the content of the page
$pages = $pdo->query('SELECT * FROM `' . $configData['DB_TABLE'] . '`');

// loop through the pages
foreach ($pages as $page) {
    // get the html content
    $html = $page[$configData['DB_HTML_COLUMN']];

    // convert the content to markdown
    $markdown = $converter->convert($html);

    // apply the filters
    if ($markdown) {
        $markdown = divFeaturePath($markdown);
        $markdown = divWellInfo($markdown);
        $markdown = divWell($markdown);
        $markdown = spanBlack($markdown);
        $markdown = spanRed($markdown);
        $markdown = regularDiv($markdown);
        $markdown = regularSpan($markdown);
    }

    $markdown = strip_tags($markdown);

    // escape the quotes
    $markdown = str_replace('"', '\"', $markdown);
    $markdown = str_replace("****", "**", $markdown);
    $markdown = preg_replace("/^(\#{3}) /m", "$1# ", $markdown);
    $markdown = preg_replace("/^(.*$)\n^\-{4,}/m", "## $1", $markdown);
    $markdown = str_replace("- ", "* ", $markdown);

    // update the page
    $pdo->query('UPDATE `' . $configData['DB_TABLE'] . '` SET `' . $configData['DB_MD_COLUMN'] . '` = "' . $markdown . '" WHERE id = ' . $page['id']);
}

// <div class="feature-path">Paramètres &gt; Prestataires de paiement &gt; Mangopay</div>
// <div class="well">Kreezalid fournit une application native qui connecte simplement votre marketplace avec l'API MANGOPAY. Il n'y a pas de frais d'installation.</div>
// <iframe allowfullscreen="" frameborder="0" height="315" src="https://www.youtube.com/embed/HpMEWBbFD2U?rel=0" width="560"></iframe>