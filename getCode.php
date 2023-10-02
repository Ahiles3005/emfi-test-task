<?php


require_once 'AmoCrm.php';
$amo = new AmoCrm();

if (isset($_GET['code'])) {
    $amo->authorize($_GET['code']);
    var_dump(111);
} else {
    header("Location: https://www.amocrm.ru/oauth?client_id={$amo->clientId}&state=random&mode=post_message");
}


