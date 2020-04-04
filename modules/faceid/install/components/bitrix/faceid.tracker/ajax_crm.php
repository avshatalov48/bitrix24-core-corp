<?php

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');
\Bitrix\Main\Loader::includeModule('crm');

if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
{
	die;
}

CUtil::JSPostUnescape();

if (!empty($_POST['action']))
{
	$lead = \Bitrix\Faceid\TrackingVisitorsTable::createCrmLead($_POST['visitor_id'], $_POST['lead_title']);
	echo \Bitrix\Main\Web\Json::encode(array('id' => $lead['ID'], 'url' => '/crm/lead/show/'.$lead['ID'].'/', 'name' => $lead['TITLE']));
}

CMain::FinalActions();