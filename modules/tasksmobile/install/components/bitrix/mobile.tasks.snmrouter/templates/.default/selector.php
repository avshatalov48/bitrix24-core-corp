<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$filter = array();
if ($request->getPost("search"))
{
	$post = array("search" => $request->getPost("search"));
	CUtil::decodeURIComponent($post);
	$filter = array(
		"%TITLE" => $post["search"],
	);
}

$this->__component->arResult = $APPLICATION->IncludeComponent(
	'bitrix:tasks.task.selector',
	'.default',
	$arParams + array(
		"MULTIPLE" => "N",
		"FILTER" => $filter,
		"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
		"PATH_TO_USER_TASKS_SELECTOR" => $arParams['PATH_TO_USER_TASKS_SELECTOR'],
		"SELECT" => array('ID', 'TITLE', 'STATUS'),
	),
	$this->__component
);