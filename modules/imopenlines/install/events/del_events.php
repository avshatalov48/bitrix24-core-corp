<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$connection = \Bitrix\Main\Application::getInstance()->getConnection();
$connection->query("DELETE FROM b_event_type WHERE EVENT_NAME in ('IMOL_HISTORY_LOG', 'IMOL_OPERATOR_ANSWER')");
$connection->query("DELETE FROM b_event_message WHERE EVENT_NAME in ('IMOL_HISTORY_LOG', 'IMOL_OPERATOR_ANSWER')");
