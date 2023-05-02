<?php

$configData = parse_ini_file('config.ini');

// required API credentials
$identifier = $configData['API_IDENTIFIER'];
$key = $configData['API_KEY'];

// authentication building
$login = sprintf('%s:%s', $identifier, $key);
$tier = 'user';

$headers = array(
    "Content-Type: application/json",
    "Authorization: " . sprintf('Basic %s', base64_encode($login)),
    "X-Crisp-Tier: " . $tier
);

// get the data to send from database
try {
    $pdo = new PDO(
        $configData['DB_TYPE'] . ':host=' . $configData['DB_HOST'] . ';dbname=' . $configData['DB_NAME'],
        $configData['DB_USER'],
        $configData['DB_PASS']
    );
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// get the data of the articles from the db
$articles = $pdo->query('
    SELECT *
    FROM `' . $configData['DB_TABLE'] . '`
    AND ' . $configData['DB_LOADED_COLUMN'] . ' IS NULL
    ');

// loop on the articles
foreach ($articles as $article) {
    // get the data of the article
    $title = $article[$configData['DB_TITLE_COLUMN']];
    $content = $article[$configData['DB_HTML_COLUMN']];
    $description = $article[$configData['DB_DESCRIPTION_COLUMN']];

    // create the article
    $urlPOST = 'https://api.crisp.chat/v1/website/' . $configData['WEBSITE_ID'] . '/helpdesk/locale/' . $configData['API_LOCALE'] . '/article';

    $curl = curl_init($urlPOST);
    curl_setopt($curl, CURLOPT_URL, $urlPOST);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $data = <<<DATA
{
    "title": "$title"
}
DATA;

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    // get the response
    $response = curl_exec($curl);

    // check for errors
    if (curl_errno($curl)) {
        echo 'Curl error:' . curl_error($curl) . PHP_EOL;
    }

    // get the response code
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // close the connection
    curl_close($curl);

    // get the created article id if 301
    if ($httpcode == 201) {
        $response = json_decode($response, true);
        $article_id = $response['data']['article_id'];
        // update the article
        $pdo->query('
            UPDATE ' . $configData['DB_TABLE'] . ' 
            SET ' . $configData['DB_CRISP_ID_COLUMN'] . ' = "' . $article_id . '"
            WHERE ' . $configData['DB_TITLE_COLUMN'] . ' = "' . $title . '"
        ');
        echo 'Success: "' . $article['title'] . '" has been created' . PHP_EOL;
    } else {
        echo 'Error: ' . $response . PHP_EOL;
    }

    // update the article with full content

    $urlPUT = 'https://api.crisp.chat/v1/website/' . $configData['WEBSITE_ID'] . '/helpdesk/locale/' . $configData['API_LOCALE'] . '/article/' . $article_id;

    $curl = curl_init($urlPUT);
    curl_setopt($curl, CURLOPT_URL, $urlPUT);
    curl_setopt($curl, CURLOPT_POST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $rawData = [
        "title" => $title,
        "description" => $description,
        "content" => $content,
        "featured" => false,
        "order" => 1
    ];

    $data = json_encode($rawData);

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

    // get the response
    $response = curl_exec($curl);

    // check for errors
    if (curl_errno($curl)) {
        echo 'Curl error:' . curl_error($curl) . PHP_EOL;
    }

    // get the response code
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // close the connection
    curl_close($curl);

    // get the created article id if 301
    if ($httpcode == 200) {
        $response = json_decode($response, true);
        // update the article
        $date = date_format(new DateTime(), 'Y-m-d H:i:s');

        $pdo->query(
            '
            UPDATE ' . $configData['DB_TABLE'] . ' 
            SET ' . $configData['DB_CRISP_ID_COLUMN'] . ' = "' . $date . '"
            WHERE ' . $configData['DB_TITLE_COLUMN'] . '  = "' . $title . '"'
        );
        echo 'Success: "' . $article['title'] . '" has fully loaded' . PHP_EOL;
    } else {
        echo 'Error: ' . $response . PHP_EOL;
    }
}