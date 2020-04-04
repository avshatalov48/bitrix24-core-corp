<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['PATH'] = array(
	'SG' => \Bitrix\Tasks\UI::convertActionPathToBarNotation(
		$this->__component->findParameterValue('PATH_TO_GROUP'),
		array('group_id' => 'ID')
	),
	'U' => \Bitrix\Tasks\UI::convertActionPathToBarNotation(
		$this->__component->findParameterValue('PATH_TO_USER_PROFILE'),
		array('user_id' => 'ID')
	),
);