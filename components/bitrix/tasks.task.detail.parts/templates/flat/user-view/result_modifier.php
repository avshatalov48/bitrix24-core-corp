<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Tasks\Util\Type;

$role = (string) $arResult['TEMPLATE_DATA']['ROLE'];
if($role == '' || !in_array($role, array('RESPONSIBLE', 'ORIGINATOR', 'AUDITORS', 'ACCOMPLICES')))
{
    $role = 'RESPONSIBLE';
}
$arResult['TEMPLATE_DATA']['ROLE'] = $role;

$arResult['TEMPLATE_DATA']['MULTIPLE'] = $arResult['TEMPLATE_DATA']['MULTIPLE'] == true || $arResult['TEMPLATE_DATA']['MULTIPLE'] == 'Y';
$arResult['TEMPLATE_DATA']['AUTO_SYNC'] = $arResult['TEMPLATE_DATA']['AUTO_SYNC'] == true || $arResult['TEMPLATE_DATA']['AUTO_SYNC'] == 'Y';

$arResult['TEMPLATE_DATA']['EDITABLE'] =
	!$arParams["PUBLIC_MODE"] &&
	intval($arResult['TEMPLATE_DATA']['TASK_ID']) &&
	$arResult['TEMPLATE_DATA']['TASK_CAN_EDIT'];

$arResult['TEMPLATE_DATA']['EDITABLE_OR_AUDITOR'] = $arResult['TEMPLATE_DATA']['EDITABLE'] || $role == 'AUDITORS';
$arResult['TEMPLATE_DATA']['EMPTY_LIST'] = 
	!Type::isIterable($arResult['TEMPLATE_DATA']['ITEMS']['DATA']) || 
	count($arResult['TEMPLATE_DATA']['ITEMS']['DATA']) < 1; 

if(!Type::isIterable($arResult['TEMPLATE_DATA']['ITEMS']['DATA']))
{
    $arResult['TEMPLATE_DATA']['ITEMS']['DATA'] = array();
}
else
{
    $currentUser = $GLOBALS['USER']->getId();
    $needCurrentUser = $role == 'AUDITORS' && Type::isIterable($arResult['TEMPLATE_DATA']['USER']);

    $formattedUsers = array();
    foreach($arResult['TEMPLATE_DATA']['ITEMS']['DATA'] as $i => $item)
    {
        $formattedUsers[$item['ID']] = $item;
    }

    if($needCurrentUser)
    {
        $formattedUsers[$currentUser] = \Bitrix\Tasks\Util\User::extractPublicData($arResult['TEMPLATE_DATA']['USER']);
    }

	$arParams["PATH_TO_USER_PROFILE"] = \Bitrix\Tasks\Integration\Socialnetwork\Task::addContextToURL($arParams["PATH_TO_USER_PROFILE"], $arResult['TEMPLATE_DATA']['TASK_ID']);

    foreach($formattedUsers as $i => $item)
    {
        $formattedUsers[$i]['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($item['PERSONAL_PHOTO'], 100, 100);
        $formattedUsers[$i]['URL'] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => $item["ID"]));
        $formattedUsers[$i]['NAME_FORMATTED'] = CUser::FormatName($arParams["NAME_TEMPLATE"], $item, true, false);

		$userType = 'employee';
		if ($item['IS_EMAIL_USER'])
		{
			$userType = 'mail';
		}
		else if ($item['IS_EXTRANET_USER'])
		{
			$userType = 'extranet';
		}

		$formattedUsers[$i]['USER_TYPE'] = $userType;
    }

    $tmp = array();
    foreach($arResult['TEMPLATE_DATA']['ITEMS']['DATA'] as $i => $item)
    {
        $tmp[intval($item['ID'])] = $formattedUsers[intval($item['ID'])];
    }

    $arResult['TEMPLATE_DATA']['ITEMS']['DATA'] = $tmp;

    if($arResult['TEMPLATE_DATA']['EDITABLE_OR_AUDITOR'])
    {
        $arResult['TEMPLATE_DATA']['ITEMS']['DATA'][] = array(
            'ID' =>                 '{{VALUE}}',
            'NAME_FORMATTED' =>     '{{DISPLAY}}',
            'WORK_POSITION' =>      '{{WORK_POSITION}}',
            'URL' =>                CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array("user_id" => '{{VALUE}}')),
            'AVATAR' =>             '{{AVATAR}}',
            'USER_TYPE' =>          '{{USER_TYPE}}',
        );
    }

    if($needCurrentUser)
    {
        $arResult['TEMPLATE_DATA']['USER'] = $formattedUsers[$currentUser];
    }
}

$imAuditor = false;
foreach($arResult['TEMPLATE_DATA']['ITEMS']['DATA'] as $item)
{
    if($item['ID'] == $GLOBALS['USER']->getId())
    {
        $imAuditor = true;
        break;
    }
}
$arResult['TEMPLATE_DATA']['IM_AUDITOR'] = $imAuditor;
$arResult['TEMPLATE_DATA']['CAN_ADD_MAIL_USERS'] = \Bitrix\Main\ModuleManager::isModuleInstalled("mail");