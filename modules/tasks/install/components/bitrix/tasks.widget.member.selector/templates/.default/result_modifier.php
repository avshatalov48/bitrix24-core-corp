<?
use Bitrix\Tasks\Util\Site;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Util\User;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array(
		'tasks_itemsetpicker',
		'tasks_util',
		'tasks_util_query',
		'tasks_util_template',
	),
));

$this->__component->tryParseEnumerationParameter($arParams['DISPLAY'], array('inline', 'block'), 'block');
$this->__component->tryParseIntegerParameter($arParams['MAX_WIDTH'], 0);

$arParams["NAME_TEMPLATE"] = $helper->findParameterValue('NAME_TEMPLATE');
$this->__component->tryParseStringParameter($arParams['NAME_TEMPLATE'], Site::getUserNameFormat());

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');
$this->__component->tryParseBooleanParameter($arParams['READ_ONLY'], false);
$this->__component->tryParseBooleanParameter($arParams['SOLE_INPUT_IF_MAX_1'], false);
$this->__component->tryParseStringParameter($arParams['SOLE_INPUT_POSTFIX'], '');

$uPref = SocialNetwork::getUserEntityPrefix();
$gPref = SocialNetwork::getGroupEntityPrefix();
$dPref = SocialNetwork::getDepartmentEntityPrefix();

$uUrl = \Bitrix\Tasks\UI::convertActionPathToBarNotation(
	$helper->findParameterValue('PATH_TO_USER_PROFILE'),
	array('user_id' => 'ID')
);
$gUrl = \Bitrix\Tasks\UI::convertActionPathToBarNotation(
	$helper->findParameterValue('PATH_TO_GROUP'),
	array('group_id' => 'ID')
);

// parse data, define additional fields for server-side rendering
// see: BX.Tasks.UserItemSet.prepareData, BX.Tasks.UserItemSet.extractItemValue, BX.Tasks.UserItemSet.extractItemDisplay
// for client-side implementation of the same code
$data = array();
$ids = array();
foreach($arParams['DATA'] as $i => $item)
{
	if(!array_key_exists('ENTITY_TYPE', $item))
	{
		$entityType = $uPref;
	}
	else
	{
		$entityType = (string) $item['ENTITY_TYPE'];
		if($entityType != $uPref && $entityType != $gPref && $entityType != $dPref)
		{
			continue;
		}
	}

	$url = 'javascript:void(0);';
	if($entityType == $uPref)
	{
		$item = User::extractPublicData($item);
		$url = $uUrl;
	}
	elseif($entityType == $gPref)
	{
		$item = SocialNetwork\Group::extractPublicData($item);
		$url = $gUrl;
	}

	$item['entityType'] = $entityType;

	// define value
	$item['VALUE'] = $entityType.$item['ID'];

	// define display
	$display = $item['ID'];
	if($entityType == $uPref)
	{
		$display = ($item['LOGIN'] ?? null);
		if($arParams["NAME_TEMPLATE"])
		{
			$formatted = \Bitrix\Tasks\Util\User::formatName($item, false, $arParams["NAME_TEMPLATE"]);
			if($formatted != 'Noname')
			{
				$display = $formatted; // Noname - bad, login - good
			}
		}
	}
	else
	{
		if($item['NAME'])
		{
			$display = $item['NAME'];
		}
		elseif($item['TITLE'])
		{
			$display = $item['TITLE'];
		}
	}

	$item['DISPLAY'] = $display;

	// define URL
	$item['URL'] = ((int)$item['ID'] ? str_replace('{{ID}}', $item['ID'], $url) : 'javascript:void(0);');

	// define TYPE class
	$typeSet = array();
	if($entityType == $uPref)
	{
		if($item['IS_EXTRANET_USER'] ?? null)
		{
			$typeSet[] = 'extranet';
		}
		if($item['IS_CRM_EMAIL_USER'] ?? null)
		{
			$typeSet[] = 'crmemail';
		}
		if($item['IS_EMAIL_USER'] ?? null)
		{
			$typeSet[] = 'mail';
		}
	}
	elseif($entityType == $gPref)
	{
		$typeSet[] = 'group';
	}
	else
	{
		$typeSet[] = 'department';
	}
	$item['TYPE_SET'] = implode(' ', $typeSet);

	$item['ITEM_SET_INVISIBLE'] = '';

	if(!array_key_exists('EMAIL', $item))
	{
		$item['EMAIL'] = '';
	}

	$ids[] = $item['ID'];
	$data[$i] = $item;
}
$arResult['TEMPLATE_DATA']['IDS'] = $ids;
$arParams['DATA'] = $data;

$arResult['JS_DATA'] = array(
	'path' => array(
		'SG' => $gUrl,
		'U' => $uUrl,
	),
	'data' => $arParams['DATA'],
	'min' => $arParams['MIN'],
	'max' => is_infinite($arParams['MAX']) ? 99999 : $arParams['MAX'],
	'nameTemplate' => $arParams["NAME_TEMPLATE"],
	'types' => $arParams['TYPES'],
	'inputSpecial' => $arParams['SOLE_INPUT_IF_MAX_1'] && $arParams['MAX'] == 1,
	'readOnly' => $arParams['READ_ONLY'],
	'userType' => mb_substr($arParams['TEMPLATE_CONTROLLER_ID'], mb_strpos($arParams['TEMPLATE_CONTROLLER_ID'], '-') + 1),
	'taskLimitExceeded' => $arResult['TASK_LIMIT_EXCEEDED'],
	'networkEnabled' => \Bitrix\Tasks\Integration\Network\MemberSelector::isNetworkEnabled(),
	'context' => $arParams['CONTEXT'],
);