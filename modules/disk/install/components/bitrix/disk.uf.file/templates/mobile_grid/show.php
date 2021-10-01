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

\Bitrix\Main\UI\Extension::load('mobile.diskfile');

\Bitrix\Main\Localization\Loc::loadLanguageFile(__DIR__ . '/../.default/show.php');
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
	?><div class="disk-ui-file-container"><?
}

if (!empty($arResult['IMAGES']))
{
	$gridBlockClassesList = [ 'disk-ui-file-thumbnails-grid' ];
	$screenWidth = intval($arResult['deviceWidth'] / $arResult['devicePixelRatio']);
	$screenHeight = intval($arResult['deviceHeight'] / $arResult['devicePixelRatio']);

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
		<?

			foreach($arResult['IMAGES'] as $key => $file)
			{
				$id = "disk-attach-".$file['ID'];

				if ($key <= 3)
				{
					$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';

					$imgClassList = [
						'disk-ui-file-thumbnails-grid-img'
					];

					switch($key)
					{
						case 0:
							if ($count == 1)
							{
								$maxWidth = $maxHeight = $screenWidth;
							}
							elseif (
								$count == 2
								|| $count == 3
							)
							{
								$maxWidth = ($vertical ? $screenWidth/2 : $screenWidth);
								$maxHeight = ($vertical ? $screenWidth : $screenWidth/2);
							}
							elseif ($count >= 4)
							{
								$maxWidth = ($vertical ? $screenWidth*2/3 : $screenWidth);
								$maxHeight = ($vertical ? $screenWidth : $screenWidth*2/3);
							}
							break;
						case 1:
							if ($count == 2)
							{
								$maxWidth = ($vertical ? $screenWidth/2 : $screenWidth);
								$maxHeight = ($vertical ? $screenWidth : $screenWidth/2);
							}
							elseif ($count == 3)
							{
								$maxWidth = $maxHeight = $screenWidth/2;
							}
							elseif ($count >= 4)
							{
								$maxWidth = $maxHeight = $screenWidth/3;
							}
							break;
						case 2:
							if ($count == 3)
							{
								$maxWidth = $maxHeight = $screenWidth/2;
							}
							elseif ($count >= 4)
							{
								$maxWidth = $maxHeight = $screenWidth/3;
							}
							break;
						default: // 3
							$maxWidth = $maxHeight = $screenWidth/3;
					}

					if (
						$count > 1
						&& (
							$file["BASIC"]["width"] > $maxWidth
							|| $file["BASIC"]["height"] > $maxHeight
						)
					)
					{
						$imgClassList[] = 'disk-ui-file-thumbnails-grid-img-cover';
					}

					?>
					<figure data-bx-disk-image-container="Y" class="disk-ui-file-thumbnails-grid-item disk-ui-file-thumbnails-grid-item-<?=($key+1)?>">
						<img<?
						?> class="<?=implode(' ', $imgClassList)?>"<?
						?> id="<?=$id?>"<?
						?> src="<?=CMobileLazyLoad::getBase64Stub()?>"<?
						?> data-src="<?=$file["BASIC"]["src"] ?>"<?
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
						if (
							$count == 1
							&& !in_array('disk-ui-file-thumbnails-grid-flexible-width-img', $gridBlockClassesList)
						)
						{
							?> width="<?=$file["BASIC"]["width"]?>"<?
							?> height="<?=$file["BASIC"]["height"]?>"<?
						}
						?> />
						<?
						if (
							$key == 3
							&& $count > 4
						)
						{
							?>
							<span class="disk-ui-file-thumbnails-grid-number">+<?=($count-4)?></span>
							<?
						}
						?>
					</figure>
					<?
				}
				else
				{
					?>
					<img style="display: none"<?
					?> class="disk-ui-file-thumbnails-grid-img"<?
					?> id="<?=$id?>"<?
					?> src="<?=CMobileLazyLoad::getBase64Stub()?>"<?
					?> data-src="<?=$file["BASIC"]["src"] ?>"<?
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
					?> />
					<?
				}
			}

	?></div><?

	if ($arParams['USE_TOGGLE_VIEW'])
	{
		?>
		<div class="post-item-attached-img-control">
			<div id="wdif-block-toggle-<?=$arResult['UID']?>" class="post-item-attached-file-more-link" data-bx-view-type="mobile"><?=Bitrix\Main\Localization\Loc::getMessage('DISK_UF_FILE_MOBILE_GRID_TOGGLE_VIEW_GALLERY')?></div>
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
			"bx-attach-file-id" => $file['FILE_ID'],
			"data-bx-title" => htmlspecialcharsbx($file["NAME"]),
			"title" => htmlspecialcharsbx($file['NAVCHAIN'])
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
			if (in_array(tolower($file["EXTENSION"]), array("exe")))
			{
				?><span <?=$attributes?> class="post-item-attached-file-link"><?
					?><span><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?
					?><span class="post-item-attached-file-extension">.<?=htmlspecialcharsbx($file['EXTENSION'])?></span><?
					?><span class="post-item-attached-file-size"><?=$file['SIZE']?></span><?
				?></span><?
			}
			else
			{
				?>
				<div class="ui-icon ui-icon-file-<?=tolower($file["EXTENSION"])?>"><i></i></div>
				<a <?=$attributes
					?>onclick="app.openDocument({'url' : '<?=$file['DOWNLOAD_URL']?>'}); return BX.PreventDefault(event);" <?
					?>href="javascript:void(0);" <?
					?>class="post-item-attached-file-link"><?
						?><span><?=htmlspecialcharsbx($file['NAME_WO_EXTENSION'])?></span><?
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
			<div id="wdif-block-more-<?=$arResult['UID']?>" class="post-item-attached-file-more-link"><?=Bitrix\Main\Localization\Loc::getMessage('DISK_UF_FILE_MOBILE_GRID_FILES_MORE_LINK', ['#NUM#' => (count($arResult['FILES']) - $arResult['FILES_LIMIT'])])?></div>
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
			imagesNode: document.getElementById('wdif-block-img-<?=CUtil::JSescape($arResult['UID'])?>'),
			moreFilesNode: document.getElementById('wdif-block-more-<?=CUtil::JSescape($arResult['UID'])?>'),
			toggleViewNode: document.getElementById('wdif-block-toggle-<?=CUtil::JSescape($arResult['UID'])?>'),
			imagesIdList: [<?=$jsIds?>],
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

