<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @global CMain $APPLICATION
 */
$url = $arParams['URL'];
?><div class="modern-page-navigation">
	<span class="modern-page-title"><?=GetMessage("pages")?></span><?

	$pageNum = isset($arParams['PAGE_NUM']) ? $arParams['PAGE_NUM'] : 1;
	$enableNextPage = isset($arParams['ENABLE_NEXT_PAGE']) ? $arParams['ENABLE_NEXT_PAGE'] : false;
	$navigationHtml = '';
	if($pageNum > 1):
		$firstNavPage = ($pageNum <= 11 ? 1 : ($pageNum - 10));
		if($firstNavPage !== 1):
			?><a class="modern-page-previous" href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => 1)))?>"><?=GetMessage("nav_first")?></a><?
		endif;
		?><a class="modern-page-previous" href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => $pageNum - 1)))?>"><?=GetMessage("nav_prev")?></a><?
		for($i = $firstNavPage; $i < $pageNum; $i++):
			if($i === $firstNavPage):
				?><a class="modern-page-first" href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => $i)))?>"><?=$i?></a><?
			else:
				?><a href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => $i)))?>"><?=$i?></a><?
			endif;
		endfor;
	endif;
	?><span class="<?=$pageNum === 1 ? "modern-page-first modern-page-current" : "modern-page-current" ?>"><?=$pageNum?></span><?
	if($enableNextPage):
		?><a class="modern-page-next" href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => $pageNum + 1)))?>"><?=GetMessage("nav_next")?></a><?
		?><a class="modern-page-last" href="<?=htmlspecialcharsbx(CCrmUrlUtil::AddUrlParams($url, array('page' => -1)))?>"><?=GetMessage("nav_last")?></a><?
	endif;

?></div><?