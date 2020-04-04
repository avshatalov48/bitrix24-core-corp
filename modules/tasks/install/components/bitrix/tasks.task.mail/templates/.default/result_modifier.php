<?
use \Bitrix\Tasks\Util;
use \Bitrix\Tasks\UI;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$userId = Util\User::getId();
$data =& $arResult['DATA']['TASK'];
$path = $arResult["AUX_DATA"]['ENTITY_URL'];
$siteId = $arResult['AUX_DATA']['SITE']['SITE_ID'];
$server = $arResult['SERVER_PREFIX'] = 'http://'.$arResult['AUX_DATA']['SITE']['SERVER_NAME'].$arResult['AUX_DATA']['SITE']['DIR']; // need for absolute urls, kz it is a email

$arResult['TEMPLATE_FOLDER'] = $arResult['SERVER_PREFIX'].$this->__component->__template->__folder;

//Description
// todo: remove this when you get array access in $arResult['DATA']['TASK']
if((string) $data['DESCRIPTION'] != '')
{
	if($data['DESCRIPTION_IN_BBCODE'] == 'Y')
	{
		// convert to bbcode to html to show inside a document body
		$data['DESCRIPTION'] = UI::convertBBCodeToHtml($data['DESCRIPTION'], array(
			'PATH_TO_USER_PROFILE' => $path,
			'USER_FIELDS' => $arResult['AUX_DATA']['USER_FIELDS']
		));
	}
	else
	{
		// make our description safe to display
		$data['DESCRIPTION'] = UI::convertHtmlToSafeHtml($data['DESCRIPTION']);
	}
}

// checklist pre-format
// todo: remove this when use object with array access instead of ['ITEMS']['DATA']
$code = \Bitrix\Tasks\Manager\Task\CheckList::getCode(true);
if(is_array($arResult['DATA']['TASK'][$code]))
{
	foreach($arResult['DATA']['TASK'][$code] as &$item)
	{
		$item['TITLE_HTML'] = UI::convertBBCodeToHtmlSimple($item['TITLE']);
	}
	unset($item);

	$limit = 5;
	$arResult['CHECKLIST_LIMIT'] = count($arResult['DATA']['TASK'][$code]) - $limit > 2 ? $limit : count($arResult['DATA']['TASK'][$code]);
	$arResult['CHECKLIST_MORE'] = count($arResult['DATA']['TASK'][$code]) - $arResult['CHECKLIST_LIMIT'];
}

// members
$sender =& $arResult['DATA']['MEMBERS']['SENDER'];
$sender['AVATAR'] = UI::getAvatar($sender['PERSONAL_PHOTO']);
if(!$sender['AVATAR'])
{
	$sender['AVATAR'] = $arResult['TEMPLATE_FOLDER'].'/img/noavatar.gif';
}

$sender['NAME_FORMATTED'] = \Bitrix\Tasks\Util\User::formatName($sender, $siteId);
$sender['PERSONAL_GENDER'] = $sender['PERSONAL_GENDER'] == 'F' ? 'F' : 'M';

$receiver =& $arResult['DATA']['MEMBERS']['RECEIVER'];
$receiver['NAME_FORMATTED'] = \Bitrix\Tasks\Util\User::formatName($receiver, $siteId);

// date field translation
// we get date fields in local time of the current user, but we send the letter to some other user, whose time zone may differ, so..
if((string) $data['DEADLINE'] != '')
{
	$data['DEADLINE'] = \Bitrix\Tasks\Integration\SocialNetwork::formatDateTimeToGMT($data['DEADLINE'], $userId);
}
if((string) $data['START_DATE_PLAN'] != '')
{
	$data['START_DATE_PLAN'] = \Bitrix\Tasks\Integration\SocialNetwork::formatDateTimeToGMT($data['START_DATE_PLAN'], $userId);
}
if((string) $data['END_DATE_PLAN'] != '')
{
	$data['END_DATE_PLAN'] = \Bitrix\Tasks\Integration\SocialNetwork::formatDateTimeToGMT($data['END_DATE_PLAN'], $userId);
}

// members and dates in changes
if(is_array($arResult['AUX_DATA']['CHANGES']) && !empty($arResult['AUX_DATA']['CHANGES']))
{
	$changes =& $arResult['AUX_DATA']['CHANGES'];
	foreach($changes as $k => $v)
	{
		if($k == 'AUDITORS' || $k == 'ACCOMPLICES')
		{
			$toValueFormatted = array();

			$v = explode(',', $v['TO_VALUE']);

			if(is_array($v))
			{
				foreach($v as $mId)
				{
					if($arResult['DATA']['USER'][$mId])
					{
						$name = \Bitrix\Tasks\Util\User::formatName($arResult['DATA']['USER'][$mId], $siteId);
						if((string) $name != '')
						{
							$toValueFormatted[$mId] = $name;
						}
					}
				}
			}

			$changes[$k]['TO_VALUE'] = $toValueFormatted;
		}
	}
}

// stuff
$arResult['S_NEEDED'] = $arResult['DATA']['TASK']["REAL_STATUS"] != 4 && $arResult['DATA']['TASK']["REAL_STATUS"] != 5;