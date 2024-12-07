<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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

\Bitrix\Main\Localization\Loc::loadLanguageFile(__DIR__ . '/../.default/show.php');
$this->IncludeLangFile('show.php');

\Bitrix\Main\UI\Extension::load('mobile.diskfile');

if (
	empty($arResult['IMAGES'])
	&& empty($arResult['FILES'])
	&& empty($arResult['DELETED_FILES'])
)
{
	return;
}

if (
	$arParams['USE_TOGGLE_VIEW']
	&& (
		!isset($arParams['CONTROLLER_HIT'])
		|| !$arParams['CONTROLLER_HIT'] != 'Y'
)
)
{
	?><div class="disk-ui-file-container"><?
}

$jsIds = "";

if (!empty($arResult['IMAGES']))
{
	$imagesBlockClassesList = [ 'post-item-attached-img-wrap' ];
	$imagesBlockClassesList[] = (!empty($arResult['FILES']) ? 'post-item-attached-img-wrap-files' : 'post-item-attached-img-wrap-no-files');
	if ($arParams['USE_TOGGLE_VIEW'])
	{
		$filesBlockClassesList[] = 'post-item-attached-img-wrap-toggle';
	}

	?><div id="wdif-block-img-<?=$arResult['UID']?>" class="<?=implode(' ', $imagesBlockClassesList)?>"><?

	$counter = 0;

	foreach($arResult['IMAGES'] as $id => $file)
	{
		$counter++;

		$id = "disk-attach-".$file['ID'];

		if ($counter <= $arResult['IMAGES_LIMIT'])
		{
			$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
			$classList = [ 'post-item-attached-img-block' ];

			if ($counter == $arResult['IMAGES_LIMIT'])
			{
				$classList[] = 'post-item-attached-img-block-last';
			}

			if (
				!empty($file['HIDDEN'])
				&& $file['HIDDEN'] == 'Y'
			)
			{
				$classList[] = 'post-item-attached-img-block-hidden';
			}

			?><div data-bx-disk-image-container="Y" class="<?=implode(' ', $classList)?>"><?
				?><img<?
					?> class="post-item-attached-img"<?
					?> id="<?=$id?>"<?
					?> src="<?=CMobileLazyLoad::getBase64Stub()?>"<?
					?> data-src="<?=$file["THUMB"]["src"]?>"<?
					?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
					?> border="0"<?
					?> data-bx-title="<?=htmlspecialcharsbx($file["NAME"])?>"<?
					?> data-bx-size="<?=$file["SIZE"]?>"<?
					?> data-bx-width="<?=$file["BASIC"]["width"]?>"<?
					?> data-bx-height="<?=$file["BASIC"]["height"]?>"<?
					?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
					if ($file['XML_ID']): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;
					?> data-bx-src="<?=$file["BASIC"]["src"] ?>"<?
					?> data-bx-preview="<?=$file["PREVIEW"]["src"] ?>"<?
					?> data-bx-image="<?=$file["BASIC"]["src"] ?>"<?
				?>/><?
				if (
					$counter == $arResult['IMAGES_LIMIT']
					&& $arResult['IMAGES_COUNT'] > $arResult['IMAGES_LIMIT']
				)
				{
					?><span class="post-item-attached-img-value">+<?=($arResult['IMAGES_COUNT'] - $arResult['IMAGES_LIMIT'])?></span><?;
				}
			?></div><?
		}
		else
		{
			?><img<?
				?> style="display: none;"<?
				?> id="<?=$id?>"<?
				?> src="<?=CMobileLazyLoad::getBase64Stub()?>"<?
				?> data-src="<?=$file["THUMB"]["src"]?>"<?
				?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
				?> border="0"<?
				?> data-bx-title="<?=htmlspecialcharsbx($file["NAME"])?>"<?
				?> data-bx-size="<?=$file["SIZE"]?>"<?
				?> data-bx-width="<?=$file["BASIC"]["width"]?>"<?
				?> data-bx-height="<?=$file["BASIC"]["height"]?>"<?
				?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
				if ($file['XML_ID'])
				{
					?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?
				}
				?> data-bx-src="<?=$file["BASIC"]["src"] ?>"<?
				?> data-bx-preview="<?=$file["PREVIEW"]["src"] ?>"<?
				?> data-bx-image="<?=$file["BASIC"]["src"] ?>"<?
			?>/><?
		}
	}
	?></div><?
	if ($arParams['USE_TOGGLE_VIEW'])
	{
		?>
		<div class="post-item-attached-img-control">
			<div id="wdif-block-toggle-<?=$arResult['UID']?>" class="post-item-attached-file-more-link" data-bx-view-type="mobile_grid"><?=Bitrix\Main\Localization\Loc::getMessage('DISK_UF_FILE_MOBILE_GRID_TOGGLE_VIEW_GRID')?></div>
		</div>
		<?
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

	?><div id="wdif-block-<?=$arResult['UID']?>" class="<?=implode(' ', $filesBlockClassesList)?>"><?

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
			"bx-attach-id" => $file['ID'],
			"bx-attach-file-id" => $file['FILE_ID'],
			"data-bx-title" => htmlspecialcharsbx($file['NAME']),
			"title" => htmlspecialcharsbx($file['NAME'])
		);
		if ($file['XML_ID'])
			$attributes["bx-attach-xml-id"] = $file['XML_ID'];
		$t = "";
		foreach ($attributes as $k => $v)
		{
			$t .= $k.'="'.$v.'" ';
		}
		$attributes = $t;

		?><div id="wdif-doc-<?=$file['ID']?>" class="post-item-attached-file<?=($moreFilesBlockShown ? ' post-item-attached-file-hidden' : '')?>"><?
			if (in_array(mb_strtolower($file["EXTENSION"]), array("exe")))
			{
				?><span <?=$attributes?> class="post-item-attached-file-link"><?
					?><span class="post-item-attached-file-name"><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?
					?><span class="post-item-attached-file-extension">.<?=htmlspecialcharsbx($file['EXTENSION'])?></span><?
					?><span class="post-item-attached-file-size"><?=$file['SIZE']?></span><?
				?></span><?
			}
			else
			{
				?>
				<div class="ui-icon ui-icon-file-<?=mb_strtolower($file["EXTENSION"])?>"><i></i></div>
				<a <?=$attributes
					?>onclick="app.openDocument({'url' : '<?=$file['DOWNLOAD_URL']?>'}); return BX.PreventDefault(event);" <?
					?>href="javascript:void(0);" <?
					?>class="post-item-attached-file-link"><?
						?><span class="post-item-attached-file-name"><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?
						?><span class="post-item-attached-file-extension">.<?=htmlspecialcharsbx($file['EXTENSION'])?></span><?
						?><span class="post-item-attached-file-size"><?=$file['SIZE']?></span><?
				?></a><?
			}
		?></div><?

		if ($moreFilesBlockShown)
		{
			$moreFilesContent .= ob_get_contents();
			ob_end_clean();
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
		<div class="post-item-attached-file-more" >
			<div id="wdif-block-more-<?=$arResult['UID']?>" class="post-item-attached-file-more-link"><?=Bitrix\Main\Localization\Loc::getMessage('DISK_UF_FILE_MOBILE_FILES_MORE_LINK', ['#NUM#' => (count($arResult['FILES']) - $arResult['FILES_LIMIT'])])?></div>
		</div>
		<?=$moreFilesContent?>
		<?
	}

	?></div><?
}

if(!empty($arResult['DELETED_FILES']))
{
	?><div id="wdif-block-deleted-files-<?=$arResult['UID']?>" class="post-item-attached-file-list"><?
		foreach($arResult['DELETED_FILES'] as $file)
		{
			?><div id="wdif-doc-<?=$file['ID']?>" class="post-item-attached-file"><?
				?><span style="display: none;"></span><?
				?><span class="post-item-attached-file-deleted-name"><?=htmlspecialcharsbx($file['NAME'])?><span style="display: none;"></span><span> (<?=$file['SIZE']?>)</span><span class="post-item-attached-file-text-notice" href="#"><?= GetMessage('DISK_UF_FILE_IS_DELETED') ?></span></span><?
			?></div><?
		}
	?></div><?
}
?>

<script>
	BX.ready(function ()
	{
		new BX.Mobile.DiskFile({
			images : {
				imagesNode: document.getElementById('wdif-block-img-<?=CUtil::JSescape($arResult['UID'])?>'),
				imagesIdList: [<?=$jsIds?>],
				toggleViewNode: document.getElementById('wdif-block-toggle-<?=CUtil::JSescape($arResult['UID'])?>'),
			},
			files: {
				container: document.getElementById('wdif-block-<?=$arResult['UID']?>'),
				files: <?=CUtil::PhpToJSObject(is_array($arResult['FILES']) ? array_map(function($file) {
					return [
						'id' => $file['ID'],
						'downloadUrl' => $file['DOWNLOAD_URL'],
						'extension' => $file['EXTENSION'],
						'name' => $file['NAME'],
						'size' => $file['SIZE'],
					];
				}, $arResult['FILES']) : [])?>
			},
			signedParameters: '<?=\Bitrix\Main\Component\ParameterSigner::signParameters($this->getComponent()->getName(), $arResult['SIGNED_PARAMS'])?>'
		});
	});
</script>
<?

if (
	$arParams['USE_TOGGLE_VIEW']
	&& (
		!isset($arParams['CONTROLLER_HIT'])
		|| !$arParams['CONTROLLER_HIT'] != 'Y'
	)
)
{
	?></div><? // disk-ui-file-container
}
?>