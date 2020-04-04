<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->includeComponent(
	'bitrix:intranet.mail.config.home',
	'',
	array(
		'PATH_TO_MAIL_CONFIG'     => $arResult['PATH_TO_MAIL_CONFIG'],
		'PATH_TO_MAIL_SUCCESS'    => $arResult['PATH_TO_MAIL_SUCCESS'],
		'PATH_TO_MAIL_CFG_HOME'   => $arResult['PATH_TO_MAIL_CFG_HOME'],
		'PATH_TO_MAIL_CFG_DOMAIN' => $arResult['PATH_TO_MAIL_CFG_DOMAIN'],
		'PATH_TO_MAIL_CFG_MANAGE' => $arResult['PATH_TO_MAIL_CFG_MANAGE'],
	),
	$component
);
