<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (empty($arResult["ELEMENTS_LIST"]))
	return true;

CUtil::InitJSCore(array('ajax'));

$arParams["ID"] = md5(serialize(array("default", $arParams["FILTER"], $arParams["SORTING"])));

/********************************************************************
				Input params
********************************************************************/

if (!empty($arResult["ERROR_MESSAGE"])):
	?><div class="photo-error"><?=ShowError($arResult["ERROR_MESSAGE"])?></div><?
endif;

if ($arParams["LIVEFEED_EVENT_ID"] == "photo")
{
	$albumToken = randString(6);
	?><div class="post-item-attached-img-wrap" id="album_wrap_<?=$albumToken?>"><?
	$jsIds = "";

	foreach ($arResult["ELEMENTS_LIST"] as $key => $arItem)
	{
		$id = "photo-attached-".$arItem["~PREVIEW_PICTURE"];
		$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
		$arItem["TITLE"] = trim(htmlspecialcharsEx($arItem["~PREVIEW_TEXT"]), " -");
		?><div class="post-item-attached-img-block"><?
			?><img 
				class="post-item-attached-img" 
				id="<?=$id?>" 
				src="<?=CMobileLazyLoad::getBase64Stub()?>" 
				data-src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" 
				border="0" 
				title="<?= $arItem["TITLE"]?>" 
				data-bx-image="<?=$arItem["BIG_PICTURE"]["SRC"]?>"
				data-bx-section-image="<?=$arResult["SECTION_ELEMENTS_SRC"][$arItem["PROPERTIES"]["REAL_PICTURE"]["VALUE"]]["SRC"]?>"
				data-bx-section-preview="<?=$arResult["SECTION_ELEMENTS_SRC"][$arItem["PROPERTIES"]["REAL_PICTURE"]["VALUE"]]["PREVIEW_SRC"]?>"
			/><?
		?></div><?
	}
	?><script>
	if (app.enableInVersion(6))
	{
		BX.bindDelegate(BX("album_wrap_<?=$albumToken?>"), "click", { 'tag': 'IMG' }, function(e)
		{
			var
				arPhotos = [],
				currentImage = false,
				currentPreview = false;
			<?
			foreach ($arResult["SECTION_ELEMENTS_SRC"] as $photo)
			{
				$photoSrc = $photo['SRC'];
				$previewSrc = (!empty($photo['PREVIEW_SRC']) ? $photo['PREVIEW_SRC'] : '');
				?>
					arPhotos[arPhotos.length] = {
						url: '<?=$photoSrc?>',
						preview: '<?=$previewSrc?>',
						description: ''
					};
				<?
			}
			?>
			var oParams = {
				"photos": arPhotos
			};

			if (this.tagName.toUpperCase() == 'IMG')
			{
				currentImage = this.getAttribute('data-bx-section-image');
				if (
					typeof currentImage != 'undefined'
					&& currentImage.length > 0
				)
				{
					oParams.default_photo = currentImage;
				}

				currentPreview = this.getAttribute('data-bx-section-preview');
				if (
					typeof currentPreview != 'undefined'
					&& currentPreview.length > 0
				)
				{
					oParams.default_preview = currentPreview;
				}
			}

			BXMobileApp.UI.Photo.show(oParams);

			return BX.PreventDefault(e);
		});
	}
	</script><?
	?></div><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?
}
else
{
	?><div id="gallery_wrap"><?
	$jsIds = "";
	foreach ($arResult["ELEMENTS_LIST"] as $key => $arItem)
	{
		$id = "photo-inline-".$arItem["~PREVIEW_PICTURE"];
		$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
		$arItem["TITLE"] = trim(htmlspecialcharsEx($arItem["~PREVIEW_TEXT"]), " -");
		?><img 
			class="post-item-post-img" 
			src="<?=CMobileLazyLoad::getBase64Stub()?>" 
			id="<?=$id?>" 
			width="<?=$arItem["PREVIEW_PICTURE"]["WIDTH"]?>" 
			height="<?=$arItem["PREVIEW_PICTURE"]["HEIGHT"]?>" 
			data-src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" 
			border="0" 
			title="<?= $arItem["TITLE"]?>"
			data-bx-section-image="<?=$arResult["SECTION_ELEMENTS_SRC"][$arItem["PROPERTIES"]["REAL_PICTURE"]["VALUE"]]["SRC"]?>"
			data-bx-section-preview="<?=$arResult["SECTION_ELEMENTS_SRC"][$arItem["PROPERTIES"]["REAL_PICTURE"]["VALUE"]]["PREVIEW_SRC"]?>"
			data-bx-image="<?=$arItem["BIG_PICTURE"]["SRC"]?>" /><?
	};
	?></div><?
	?><script>
	if (app.enableInVersion(6))
	{
		BX.bindDelegate(BX("gallery_wrap"), "click", { 'tag': 'IMG' }, function(e)
		{
			var
				arPhotos = [],
				currentImage = false,
				currentPreview = false;
			<?
			foreach ($arResult["ELEMENTS_LIST"] as $photo)
			{
				$sectionPhoto = $arResult["SECTION_ELEMENTS_SRC"][$photo["PROPERTIES"]["REAL_PICTURE"]["VALUE"]];
				$sectionPhotoSrc = $sectionPhoto['SRC'];
				$sectionPreviewSrc = (!empty($sectionPhoto['PREVIEW_SRC']) ? $sectionPhoto['PREVIEW_SRC'] : '');

				?>
				arPhotos[arPhotos.length] = {
					url: '<?=$sectionPhotoSrc?>',
					preview: '<?=$sectionPreviewSrc?>',
					description: ''
				};
				<?
			}
			?>
			var oParams = {
				"photos": arPhotos
			};

			if (this.tagName.toUpperCase() == 'IMG')
			{
				currentImage = this.getAttribute('data-bx-section-image');
				if (
					typeof currentImage != 'undefined'
					&& currentImage.length > 0
				)
				{
					oParams.default_photo = currentImage;
				}

				currentPreview = this.getAttribute('data-bx-section-preview');
				if (
					typeof currentPreview != 'undefined'
					&& currentPreview.length > 0
				)
				{
					oParams.default_preview = currentPreview;
				}
			}

			BXMobileApp.UI.Photo.show(oParams);

			return BX.PreventDefault(e);
		});
	}
	</script><?

	?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?
}
?>