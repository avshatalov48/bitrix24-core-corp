<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$this->IncludeLangFile("show.php");
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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
if (sizeof($arResult['FILES']) <= 0)
{
	return;
}

$this->setFrameMode(true);

$jsIds = "";

foreach ($arResult['FILES'] as $id => $file)
{
	if($file['IS_MARK_DELETED'])
	{
		?><span <?
		?>title="<?=htmlspecialcharsbx($file["NAVCHAIN"])?>" <?
		?> class="feed-com-file-inline feed-com-file-wrap diskuf-files-entity"<?
		?>><?
		?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($file["EXTENSION"])?>"></span><?
		?><span class="feed-com-file-inline feed-com-file-deleted-name"><?=htmlspecialcharsbx($file["NAME"])?></span><?
		?><?
		?></span><?
	}
	elseif (array_key_exists("IMAGE", $file))
	{
		?><div id="disk-attach-<?=$file['ID']?>" class="feed-com-file-inline feed-com-file-inline-image feed-com-file-wrap diskuf-files-entity"><?
			?><span class="feed-com-file-inline feed-com-img-wrap feed-com-img-load" style="width:<?=$file["INLINE"]["width"]?>px;height:<?=$file["INLINE"]["height"]?>px;"><?

				$id = "disk-inline-image-".$file['ID']."-".$this->getComponent()->randString(4);
				if (
					isset($arParams["LAZYLOAD"]) 
					&& $arParams["LAZYLOAD"] == "Y"
				)
				{
					$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
				}

				?><img id="<?=$id?>" onload="this.parentNode.className='feed-com-img-wrap';" <?
				if (
					isset($arParams["LAZYLOAD"]) 
					&& $arParams["LAZYLOAD"] == "Y"
				)
				{
					?> src="<?=\Bitrix\Disk\Ui\LazyLoad::getBase64Stub()?>" <?
					?> data-thumb-src="<?=$file["INLINE"]["src"] ?>"<?
				}
				else
				{
					?> src="<?=$file["INLINE"]["src"]?>" <?
				}
				?> width="<?=$file["INLINE"]["width"]?>"<?
				?> height="<?=$file["INLINE"]["height"]?>"<?
				?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
				?> <?= $file['ATTRIBUTES_FOR_VIEWER']
				?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
				if ($file['XML_ID']): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;
				?> data-bx-width="<?=$file["BASIC"]["width"]?>"<?
				?> data-bx-height="<?=$file["BASIC"]["height"]?>"<?
				if (!empty($file["ORIGINAL"])) {
				?> data-bx-full="<?=$file["ORIGINAL"]["src"]?>"<?
				?> data-bx-full-width="<?=$file["ORIGINAL"]["width"]?>" <?
				?> data-bx-full-height="<?=$file["ORIGINAL"]["height"]?>"<?
				?> data-bx-full-size="<?=$file["SIZE"]?>"<? }
				?> data-bx-onload="Y"<?
				?> /><?
			?></span><?
		?></div><?
	}
	elseif (array_key_exists("VIDEO", $file))
	{
		echo $file['VIDEO'];
	}
	else
	{
		$onClick = (
			SITE_TEMPLATE_ID == 'landing24'
				? ""
				: "WDInlineElementClickDispatcher(this, 'disk-attach-".$file['ID']."'); return false;"
		);
		$href = (
			SITE_TEMPLATE_ID == 'landing24'
				? htmlspecialcharsbx($file["DOWNLOAD_URL"])
				: htmlspecialcharsbx($file["PATH"])
		);

		?><a target="_blank" href="<?=$href?>" <?
			?>title="<?=htmlspecialcharsbx($file["NAVCHAIN"])?>" <?
			?>onclick="<?=$onClick?>" <?
			?> alt="<?=htmlspecialcharsbx($file["NAME"])?>" <?
			?> class="feed-com-file-inline feed-com-file-wrap diskuf-files-entity"<?
			?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
			if ($file['XML_ID']){ ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?}
			?>><?
			?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($file["EXTENSION"])?>"></span><?
			?><span class="feed-com-file-inline feed-com-file-name"><?=htmlspecialcharsbx($file["NAME"])?></span><?
			?><?
		?></a><?
	}
}

if ($jsIds <> '')
{
	?><script>BX.LazyLoad.registerImages([<?=$jsIds?>], typeof oLF != 'undefined' ? oLF.LazyLoadCheckVisibility : false, {dataSrcName: "thumbSrc"});</script><?
}