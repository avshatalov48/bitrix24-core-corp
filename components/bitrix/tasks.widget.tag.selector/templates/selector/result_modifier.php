<?

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Update\TagConverter;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', 'tasks_util_itemset')
));

$this->__component->tryParseEnumerationParameter($arParams['DISPLAY'], array('inline', 'block'), 'block');
$this->__component->tryParseIntegerParameter($arParams['MAX_WIDTH'], 0);

// parse data, define additional fields for server-side rendering
// see: BX.Tasks.UserItemSet.prepareData, BX.Tasks.UserItemSet.extractItemValue, BX.Tasks.UserItemSet.extractItemDisplay
// for client-side implementation of the same code
$data = array();
if(\Bitrix\Tasks\Util\Collection::isA($arParams['DATA']))
{
	$arParams['DATA'] = $arParams['DATA']->toArray();
}
foreach($arParams['DATA'] as $i => $item)
{
	if(\Bitrix\Tasks\Util\Collection::isA($item))
	{
		$item = $item->toArray();
	}

	$item['DISPLAY'] = $item['NAME'];
	$item['VALUE'] = (string)abs((int) \Bitrix\Tasks\Util::hashCode($item['NAME']));
	$item['ITEM_SET_INVISIBLE'] = '';

	$arParams['DATA'][$i] = $item;
}

//Checking for working tags agent
$tagsAreConverting = TagConverter::isProceed();

$arResult['JS_DATA'] = array(
	'tagsAreConverting' => $tagsAreConverting,
	'data' => $arParams['DATA'],
	'groupId' => $arResult['GROUP_ID'],
	'userName' => CurrentUser::get()->getFormattedName(),
	'taskId' => $arParams['TASK_ID'],
	'isScrumTask' => $arResult['IS_SCRUM_TASK'],
);