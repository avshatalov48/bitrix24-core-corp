<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if (sizeof($arResult['FILES']) <= 0)
	return;
WDUFLoadStyle();
?><script>BX.message({'WDUF_FILE_TITLE_REV_HISTORY':'<?=GetMessageJS("WDUF_FILE_TITLE_REV_HISTORY")?>'});</script><?
foreach ($arResult['FILES'] as $id => $arWDFile)
{
	if (CFile::IsImage($arWDFile['NAME'], $arWDFile["FILE"]["CONTENT_TYPE"]))
	{
		?><div id="wdif-doc-<?=$arWDFile['ID']?>" class="feed-com-file-inline feed-com-file-wrap wduf-files-entity"><?
			?><span class="feed-com-file-inline feed-com-img-wrap feed-com-img-load" style="width:<?=$arWDFile["width"]?>px;height:<?=$arWDFile["height"]?>px;"><?
				?><img onload="this.parentNode.className='feed-com-img-wrap';" <?
				?> src="<?=$arWDFile["src"]?>" <?
				?> width="<?=$arWDFile["width"]?>"<?
				?> height="<?=$arWDFile["height"]?>"<?
				?> alt="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
				?> data-bx-viewer="image"<?
				?> data-bx-title="<?=htmlspecialcharsbx($arWDFile["NAME"])?>"<?
				?> data-bx-src="<?=$arWDFile["basic"]["src"] ?>"<?
				?> data-bx-download="<?=$arWDFile["VIEW"] . '?&ncc=1&force_download=1'?>"<?
				?> data-bx-document="<?=$arWDFile['EDIT'] ?>"<?
				?> data-bx-width="<?=$arWDFile["basic"]["width"]?>"<?
				?> data-bx-height="<?=$arWDFile["basic"]["height"]?>"<?
				if (!empty($arWDFile["original"])) {
				?> data-bx-full="<?=$arWDFile["original"]["src"]?>"<?
				?> data-bx-full-width="<?=$arWDFile["original"]["width"]?>" <?
				?> data-bx-full-height="<?=$arWDFile["original"]["height"]?>"<?
				?> data-bx-full-size="<?=$arWDFile["SIZE"]?>"<? }
				?> /><?
			?></span><?
		?></div><?
	}
	else
	{
		$possiblePreview = isset($arResult['allowExtDocServices']) && $arResult['allowExtDocServices'] && in_array(ltrim($arWDFile["EXTENSION"], '.'), CWebDavExtLinks::$allowedExtensionsGoogleViewer);
		if($possiblePreview && $arWDFile["FILE"]['FILE_SIZE'] < CWebDavExtLinks::$maxSizeForView){
		?><a target="_blank" href="<?=htmlspecialcharsbx($arWDFile["PATH"])?>" <?
			?>title="<?=htmlspecialcharsbx($arWDFile["NAVCHAIN"])?>" <?
			?>onclick="WDInlineElementClickDispatcher(this, 'wdif-doc-<?=$arWDFile['ID']?>'); return false;" <?
			?> alt="<?=htmlspecialcharsbx($arWDFile["NAME"])?>" class="feed-com-file-inline feed-com-file-wrap wduf-files-entity"><?
			?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($arWDFile["EXTENSION"])?>"></span><?
			?><span class="feed-com-file-inline feed-com-file-name"><?=htmlspecialcharsbx($arWDFile["NAME"])?></span><?
			?><span class="feed-com-file-inline feed-com-file-size">(<?=$arWDFile["SIZE"]?>)</span><?
		?></a><? }else{
		?><a target="_blank" href="<?=htmlspecialcharsbx($arWDFile["PATH"])?>" <?
			?>title="<?=htmlspecialcharsbx($arWDFile["NAVCHAIN"])?>" <?
			?>alt="<?=htmlspecialcharsbx($arWDFile["NAME"])?>" class="feed-com-file-inline feed-com-file-wrap"><?
			?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($arWDFile["EXTENSION"])?>"></span><?
			?><span class="feed-com-file-inline feed-com-file-name"><?=htmlspecialcharsbx($arWDFile["NAME"])?></span><?
			?><span class="feed-com-file-inline feed-com-file-size">(<?=$arWDFile["SIZE"]?>)</span><?
		?></a><?
		}
	}
}
?>