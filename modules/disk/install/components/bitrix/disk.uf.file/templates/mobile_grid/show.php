<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

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

\Bitrix\Main\UI\Extension::load('mobile.diskfile');

Loc::loadLanguageFile(__DIR__ . '/../.default/show.php');
$this->IncludeLangFile('show.php');

if (
	empty($arResult['IMAGES'])
	&& empty($arResult['FILES'])
	&& empty($arResult['DELETED_FILES'])
)
{
	return;
}

$jsIds = "";

if (
	$arParams['USE_TOGGLE_VIEW']
	&& (
		!isset($arParams['CONTROLLER_HIT'])
		|| !$arParams['CONTROLLER_HIT'] != 'Y'
	)
)
{
	?><div class="disk-ui-file-container"><?php
}

if (!empty($arResult['IMAGES']))
{
	$gridBlockClassesList = [ 'disk-ui-file-thumbnails-grid' ];
	$devicePixelRatio = empty($arResult['devicePixelRatio']) ? 1 : $arResult['devicePixelRatio'];
	$screenWidth = (int)($arResult['deviceWidth'] / $devicePixelRatio);
	$screenHeight = (int)($arResult['deviceHeight'] / $devicePixelRatio);

	$vertical = ($arResult['IMAGES'][0]['IMAGE']['HEIGHT'] > $arResult['IMAGES'][0]['IMAGE']['WIDTH']);
	$count = count($arResult['IMAGES']);

	if ($count > 1)
	{
		switch($count)
		{
			case 2:
				$suffix = 'two';
				break;
			case 3:
				$suffix = 'three';
				break;
			default:
				$suffix = 'full';
		}
		$gridBlockClassesList[] = (
			$vertical
				? 'disk-ui-file-thumbnails-grid-vertical-'.$suffix
				: 'disk-ui-file-thumbnails-grid-horizontal-'.$suffix
		);
		
		if ($count > 4)
		{
			$gridBlockClassesList[] = 'disk-ui-file-thumbnails-grid-more';
		}
	}
	else // == 1
	{
		$gridBlockClassesList[] = 'disk-ui-file-thumbnails-grid-flexible-img';
		if ($arResult['IMAGES'][0]['BASIC']['width'] >= $screenWidth)
		{
			$gridBlockClassesList[] = 'disk-ui-file-thumbnails-grid-flexible-width-img';
		}
	}

	$gridBlockClassesList[] = (!empty($arResult['FILES']) ? 'disk-ui-file-images-files' : 'disk-ui-file-images-no-files');

	if ($arParams['USE_TOGGLE_VIEW'])
	{
		$gridBlockClassesList[] = 'disk-ui-file-images-toggle';
	}

	$style = ($count > 1 ? ' style="height: '.$screenWidth.'px;"' : '');

	?>
	<div class="<?=implode(' ', $gridBlockClassesList)?>" id="wdif-block-img-<?=$arResult['UID']?>"<?=$style?>>
		<?php

		foreach($arResult['IMAGES'] as $key => $file)
		{
			$id = "disk-attach-".$file['ID'];

			if ($key <= 3)
			{
				$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';

				require($_SERVER["DOCUMENT_ROOT"] . $templateFolder . '/include/image.php');
			}
			else
			{
				require($_SERVER["DOCUMENT_ROOT"] . $templateFolder . '/include/image_hidden.php');
			}
		}

		if (!empty($arResult['INLINE_IMAGES']))
		{
			foreach ($arResult['INLINE_IMAGES'] as $file)
			{
				$id = 'disk-attach-' . $file['ID'];

				require($_SERVER["DOCUMENT_ROOT"] . $templateFolder . '/include/image_hidden.php');
			}
		}

	?></div><?php

	if ($arParams['USE_TOGGLE_VIEW'])
	{
		?>
		<div class="post-item-attached-img-control">
			<div id="wdif-block-toggle-<?=$arResult['UID']?>" class="post-item-attached-file-more-link" data-bx-view-type="mobile"><?=Bitrix\Main\Localization\Loc::getMessage('DISK_UF_FILE_MOBILE_GRID_TOGGLE_VIEW_GALLERY')?></div>
		</div>
		<?php
	}
}

$moreFilesBlockShown = false;
$moreFilesContent = '';

if (!empty($arResult['FILES']))
{
	$filesBlockClassesList = [ 'post-item-attached-file-list' ];
	if (
		!empty($arResult['FILES'])
		&& count($arResult['FILES']) > $arResult['FILES_LIMIT']
	)
	{
		$filesBlockClassesList[] = 'post-item-attached-file-list-more';
	}

	if ($arParams['USE_TOGGLE_VIEW'])
	{
		$filesBlockClassesList[] = 'post-item-attached-file-list-toggle';
	}

	?><div id="wdif-block-<?=$arResult['UID']?>" class="<?=implode(' ', $filesBlockClassesList)?>"><?php

	$counter = 0;

	foreach($arResult['FILES'] as $file)
	{
		$counter++;

		if ($moreFilesBlockShown)
		{
			ob_start();
		}

		$attributes = array(
			"id" => "disk-attach-".$file['ID'],
			"bx-attach-file-id" => $file['FILE_ID'],
			"data-bx-title" => htmlspecialcharsbx($file["NAME"]),
			"title" => htmlspecialcharsbx($file['NAME'])
		);
		if ($file['XML_ID'])
		{
			$attributes["bx-attach-xml-id"] = $file['XML_ID'];
		}
		$t = "";
		foreach ($attributes as $k => $v)
		{
			$t .= $k.'="'.$v.'" ';
		}
		$attributes = $t;

		?><div id="wdif-doc-<?= $file['ID'] ?>" class="post-item-attached-file<?=($moreFilesBlockShown ? ' post-item-attached-file-hidden' : '')?>"><?php
			if (tolower($file["EXTENSION"]) === "exe")
			{
				?><span <?=$attributes?> class="post-item-attached-file-link"><?php
					?><span><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?php
					?><span class="post-item-attached-file-extension">.<?=htmlspecialcharsbx($file['EXTENSION'])?></span><?php
					?><span class="post-item-attached-file-size"><?=$file['SIZE']?></span><?php
				?></span><?php
			}
			else
			{
				?>
				<div class="ui-icon ui-icon-file-<?= tolower($file["EXTENSION"]) ?>"><i></i></div>
				<a <?=$attributes
					?>onclick="app.openDocument({'url' : '<?= $file['DOWNLOAD_URL'] ?>'}); return BX.PreventDefault(event);" <?php
					?>href="javascript:void(0);" <?php
					?>class="post-item-attached-file-link"><?php
						?><span><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?php
						?><span class="post-item-attached-file-extension">.<?=htmlspecialcharsbx($file['EXTENSION'])?></span><?php
						?><span class="post-item-attached-file-size"><?=$file['SIZE']?></span><?php
				?></a><?php
			}
		?></div><?php

		if ($moreFilesBlockShown)
		{
			$moreFilesContent .= ob_get_clean();
		}

		if (
			!$moreFilesBlockShown
			&& $counter == $arResult['FILES_LIMIT']
			&& count($arResult['FILES']) > $arResult['FILES_LIMIT']
		)
		{
			$moreFilesBlockShown = true;
		}
	}

	if (!empty($moreFilesContent))
	{
		?>
		<div class="post-item-attached-file-more">
			<div id="wdif-block-more-<?=$arResult['UID']?>" class="post-item-attached-file-more-link"><?= Loc::getMessage('DISK_UF_FILE_MOBILE_GRID_FILES_MORE_LINK', ['#NUM#' => (count($arResult['FILES']) - $arResult['FILES_LIMIT'])]) ?></div>
		</div>
		<?= $moreFilesContent ?>
		<?php
	}

	?></div><?php
}

if(!empty($arResult['DELETED_FILES']))
{
	?><div id="wdif-block-deleted-files-<?=$arResult['UID']?>" class="post-item-attached-file-list"><?php
		foreach($arResult['DELETED_FILES'] as $file)
		{
			?><div id="wdif-doc-<?=$file['ID']?>" class="post-item-attached-file"><?php
				?><span style="display: none;"></span><?php
				?><span class="post-item-attached-file-deleted-name"><?= htmlspecialcharsbx($file['NAME']) ?><span style="display: none;"></span><span> (<?=$file['SIZE']?>)</span><span class="post-item-attached-file-text-notice" href="#"><?= Loc::getMessage('DISK_UF_FILE_IS_DELETED') ?></span></span><?php
			?></div><?php
		}
	?></div><?php
}
?>
<script>
	BX.ready(function ()
	{
		new BX.Mobile.DiskFile({
			imagesNode: document.getElementById('wdif-block-img-<?=CUtil::JSescape($arResult['UID'])?>'),
			moreFilesNode: document.getElementById('wdif-block-more-<?=CUtil::JSescape($arResult['UID'])?>'),
			toggleViewNode: document.getElementById('wdif-block-toggle-<?=CUtil::JSescape($arResult['UID'])?>'),
			imagesIdList: [<?=$jsIds?>],
			signedParameters: '<?=\Bitrix\Main\Component\ParameterSigner::signParameters($this->getComponent()->getName(), $arResult['SIGNED_PARAMS'])?>'
		});
	});
</script>
<?php

if (
	$arParams['USE_TOGGLE_VIEW']
	&& (
		!isset($arParams['CONTROLLER_HIT'])
		|| !$arParams['CONTROLLER_HIT'] != 'Y'
	)
)
{
	?></div><?php // disk-ui-file-container
}
