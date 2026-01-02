<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;
$APPLICATION->SetTitle('Ава виджет');
$APPLICATION->IncludeComponent('bitrix:intranet.avatar.widget', '', []);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
