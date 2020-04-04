<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("fileman"))
	return;

$arAccessRights = array(
	WIZARD_PORTAL_ADMINISTRATION_GROUP => 'F',
	WIZARD_PERSONNEL_DEPARTMENT_GROUP => 'F',
);

$arTaskIDs = array();
$dbRes = CTask::GetList(array(), array(
	'MODULE_ID' => 'fileman',
	'SYS' => 'Y',
	'LETTER' => implode('|', $arAccessRights),
	'BINDING' => 'module',
));

while ($arRes = $dbRes->Fetch())
{
	$arTaskIDs[$arRes['LETTER']] = $arRes['ID'];
}

$arTasksForModule = array();
foreach ($arAccessRights as $group => $letter)
{
	$APPLICATION->SetGroupRight('fileman', $group, $letter);
	$arTasksForModule[$group] = array('ID' => $arTaskIDs[$letter]);
}

CGroup::SetTasksForModule('fileman', $arTasksForModule);
?>