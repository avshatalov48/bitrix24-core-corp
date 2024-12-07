<?php

require($_SERVER["DOCUMENT_ROOT"] . "/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

/** @global CMain $APPLICATION */

$APPLICATION->includeComponent(
	'bitrix:bizprocmobile.comments',
	'',
	[
		'USER_ID' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
		'WORKFLOW_ID' => $_GET['workflowId'] ?? null,
	]
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
