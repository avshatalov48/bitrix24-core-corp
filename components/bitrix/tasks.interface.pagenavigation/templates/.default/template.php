<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

/**
 * @var array $arParams
 * @global CMain $APPLICATION
 */
$url = $arParams['URL'];
?><div class="modern-page-navigation">
	<span class="modern-page-title"><?=GetMessage("TASKS_PAGE_NAVIGATION_PAGES")?></span><?

	$pageNum = isset($arParams['PAGE_NUM']) ? $arParams['PAGE_NUM'] : 1;
	$enableNextPage = isset($arParams['ENABLE_NEXT_PAGE']) ? $arParams['ENABLE_NEXT_PAGE'] : false;
	$enableLastPage = array_key_exists('ENABLE_LAST_PAGE', $arParams) ? $arParams['ENABLE_LAST_PAGE'] : true;
	$navigationHtml = '';
	if($pageNum > 1):
		$firstNavPage = ($pageNum <= 11 ? 1 : ($pageNum - 10));
		if($firstNavPage !== 1):
			?><a class="modern-page-previous" href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => 1)))?>"><?=GetMessage("TASKS_PAGE_NAVIGATION_FIRST")?></a><?
		endif;
		?><a class="modern-page-previous" href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => $pageNum - 1)))?>"><?=GetMessage("TASKS_PAGE_NAVIGATION_PREV")?></a><?
		for($i = $firstNavPage; $i < $pageNum; $i++):
			if($i === $firstNavPage):
				?><a class="modern-page-first" href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => $i)))?>"><?=$i?></a><?
			else:
				?><a href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => $i)))?>"><?=$i?></a><?
			endif;
		endfor;
	endif;
	?><span class="<?=$pageNum === 1 ? "modern-page-first modern-page-current" : "modern-page-current" ?>"><?=$pageNum?></span><?
	if($enableNextPage):
		?><a class="modern-page-next" href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => $pageNum + 1)))?>"><?=GetMessage("TASKS_PAGE_NAVIGATION_NEXT")?></a><?
		if($enableLastPage):
		?><a class="modern-page-last" href="<?=htmlspecialcharsbx(\Bitrix\Tasks\Util\Url::AddUrlParams($url, array('page' => -1)))?>"><?=GetMessage("TASKS_PAGE_NAVIGATION_LAST")?></a><?
		endif;
	endif;

?></div><?