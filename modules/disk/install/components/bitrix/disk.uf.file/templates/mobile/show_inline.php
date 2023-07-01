<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Localization\Loc::loadLanguageFile(__DIR__ . '/../.default/show.php');

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
if (
	sizeof($arResult['FILES']) <= 0
)
{
	return;
}

$jsIds = "";
foreach ($arResult['FILES'] as $file)
{
	if($file['IS_MARK_DELETED'])
	{
		?><span <?
		?>title="<?=htmlspecialcharsbx($file["NAME"])?>" <?
		?> class="post-item-inline-attached-file post-item-attached-file-deleted-name"<?
		?>><?
		?><span class="feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?>"></span><?
		?><span class="feed-com-file-name"><?=htmlspecialcharsbx($file["NAME"])?></span><?
		?><span class="feed-com-file-size"> (<?=$file['SIZE']?>)</span><?
		?><?
		?></span><?
	}
	elseif (array_key_exists("IMAGE", $file))
	{
		$nodeId = "webdav-inline-".$file["ID"]."-".$this->getComponent()->randString(4);
		$jsIds .= $jsIds !== "" ? ', "'.$nodeId.'"' : '"'.$nodeId.'"';
		?><img src="<?=CMobileLazyLoad::getBase64Stub()?>" <?
			?> border="0" <?
			?> data-preview-src="<?=$file["SMALL"]["src"]?>" <?
			?> data-src="<?=$file["INLINE"]['src']?>" <? // inline
			?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
			?> alt="<?=htmlspecialcharsbx($file['NAME'])?>" <?
			?> data-bx-image="<?=$file["BASIC"]["src"]?>" <? // gallery
			?> data-bx-preview="<?=$file["PREVIEW"]["src"]?>" <? // gallery preview
			?> width="<?=round($file["INLINE"]["width"]/2)?>" <?
			?> height="<?=round($file["INLINE"]["height"]/2)?>" <?
			?> id="<?=$nodeId?>" /><?
	}
	elseif (array_key_exists("VIDEO", $file))
	{
		echo $file['VIDEO'];
	}
	else
	{
		?><a onclick="app.openDocument({'url' : '<?=$file['DOWNLOAD_URL']?>'}); return BX.PreventDefault(event);" href="javascript:void()" <?
			?>id="wdif-doc-<?=$file['ID']?>" <?
			?>title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
			?>alt="<?=htmlspecialcharsbx($file['NAME'])?>" class="feed-com-file-wrap post-item-inline-attached-file"><?
			?><span class="feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?>"></span><?
			?><span class="feed-com-file-name"><?=htmlspecialcharsbx($file['NAME'])?></span><?
			?><span class="feed-com-file-size"> (<?=$file['SIZE']?>)</span><?
		?></a><?
	}
}

if ($jsIds <> '')
{
	?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], typeof oMSL != 'undefined' ? oMSL.checkVisibility : false);</script><?
}