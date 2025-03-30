<? if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

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

use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

UI\Extension::load([
	'ui.tooltip',
	'ui.viewer',
	'disk.document',
	'disk.viewer.actions',
	'loader',
	'ui.design-tokens',
	'ui.icon-set.main'
]);

if (
	empty($arResult['IMAGES'])
	&& empty($arResult['FILES'])
	&& empty($arResult['DELETED_FILES'])
)
{
	return;
}

Asset::getInstance()->addCss('/bitrix/js/disk/css/legacy_uf_common.css');
Asset::getInstance()->addJs('/bitrix/components//bitrix/disk.uf.file/templates/.default/script.js');

$this->IncludeLangFile("../.default/show.php");

include_once(str_replace(array("\\", "//"), "/", __DIR__."/messages.php"));

?><div id="disk-attach-block-<?=$arResult['UID']?>" class="feed-com-files diskuf-files-entity diskuf-files-toggle-container"><?

	$jsIds = "";

	if (!empty($arResult['IMAGES']))
	{
		$gridBlockClassesList = [ 'disk-ui-file-thumbnails-web-grid' ];

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
					? 'disk-ui-file-thumbnails-web-grid-vertical-'.$suffix
					: 'disk-ui-file-thumbnails-web-grid-horizontal-'.$suffix
			);

			if ($count > 4)
			{
				$gridBlockClassesList[] = 'disk-ui-file-thumbnails-web-grid-more';
			}
		}
		else // == 1
		{
			$gridBlockClassesList[] = 'disk-ui-file-thumbnails-web-grid-flexible-img';
		}

		?>
		<div class="disk-ui-file-thumbnails-web-wrapper">
			<div class="<?=implode(' ', $gridBlockClassesList)?>">
				<?
				foreach($arResult['IMAGES'] as $key => $file)
				{
					$id = "disk-attach-image-grid-".$file['ID'];

					if ($key <= 3)
					{
						if (
							isset($arParams["LAZYLOAD"])
							&& $arParams["LAZYLOAD"] == "Y"
						)
						{
							$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
						}

						$imgClassList = [
							'disk-ui-file-thumbnails-web-grid-img'
						];

						switch($key)
						{
							case 0:
								if ($count == 1)
								{
									$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH'];
								}
								elseif (
									$count == 2
									|| $count == 3
								)
								{
									$maxWidth = ($vertical ? $arResult['BLOCK_WIDTH']/2 : $arResult['BLOCK_WIDTH']);
									$maxHeight = ($vertical ? $arResult['BLOCK_WIDTH'] : $arResult['BLOCK_WIDTH']/2);
								}
								elseif ($count >= 4)
								{
									$maxWidth = ($vertical ? $arResult['BLOCK_WIDTH']*2/3 : $arResult['BLOCK_WIDTH']);
									$maxHeight = ($vertical ? $arResult['BLOCK_WIDTH'] : $arResult['BLOCK_WIDTH']*2/3);
								}
								break;
							case 1:
								if ($count == 2)
								{
									$maxWidth = ($vertical ? $arResult['BLOCK_WIDTH']/2 : $arResult['BLOCK_WIDTH']);
									$maxHeight = ($vertical ? $arResult['BLOCK_WIDTH'] : $arResult['BLOCK_WIDTH']/2);
								}
								elseif ($count == 3)
								{
									$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH']/2;
								}
								elseif ($count >= 4)
								{
									$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH']/3;
								}
								break;
							case 2:
								if ($count == 3)
								{
									$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH']/2;
								}
								elseif ($count >= 4)
								{
									$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH']/3;
								}
								break;
							default: // 3
								$maxWidth = $maxHeight = $arResult['BLOCK_WIDTH']/3;
						}

						if (
							$count > 1
							&& (
								$file["BASIC"]["width"] > $maxWidth
								&& $file["BASIC"]["height"] > $maxHeight
							)
						)
						{
							$imgClassList[] = 'disk-ui-file-thumbnails-web-grid-img-cover';
						}

						?>
						<figure class="disk-ui-file-thumbnails-web-grid-item disk-ui-file-thumbnails-web-grid-item-<?=$key+1?>" id="disk-attach-<?=$file['ID']?>">
							<?
							if ($count == 1)
							{
								?>
								<img <?
									?> class="disk-ui-file-thumbnails-web-grid-img-item"<?
									?> id="<?=$id?>"<?
									?> width="<?=$file["INLINE"]["width"] ?>"<?
									?> height="<?=$file["INLINE"]["height"] ?>"<?
									if (
										isset($arParams["LAZYLOAD"])
										&& $arParams["LAZYLOAD"] == "Y"
									)
									{
										?> src="<?=\Bitrix\Disk\Ui\LazyLoad::getBase64Stub()?>"<?
										?> data-thumb-src="<?=$file["INLINE"]["src"] ?>"<?
									}
									else
									{
										?> src="<?=$file["INLINE"]["src"] ?>"<?
									}
									?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
                                    ?> <?=$file['ATTRIBUTES_FOR_VIEWER']?> <?
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
									?> data-bx-preview="<?=$file["INLINE"]["src"] ?>"<?
								?> />
								<?
							}
							else
							{
								?>
								<div <?
									?> class="<?=implode(' ', $imgClassList)?>"<?
									?> id="<?=$id?>"<?
									if (
										isset($arParams["LAZYLOAD"])
										&& $arParams["LAZYLOAD"] == "Y"
									)
									{
										?> style="background-image: url('<?=\Bitrix\Disk\Ui\LazyLoad::getBase64Stub()?>')"<?
										?> data-thumb-src="<?=$file["INLINE"]["src"] ?>"<?
									}
									else
									{
										?> style="background-image: url('<?=$file["INLINE"]["src"] ?>')"<?
									}
									?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
									?> <?=$file['ATTRIBUTES_FOR_VIEWER']?> <?
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
									?> data-bx-preview="<?=$file["INLINE"]["src"] ?>"<?
								?>/>
								<?
							}
							?>

							<?
							if (
								$key == 3
								&& $count > 4
							)
							{
								?>
								<span class="disk-ui-file-thumbnails-web-grid-number">+<?=($count-3)?></span>
								<?
							}
							?>
						</figure>
						<?
					}
					else
					{
						?>
						<span id="disk-attach-<?=$file['ID']?>">
							<img style="display: none"<?
								?> class="disk-ui-file-thumbnails-grid-img"<?
								?> id="<?=$id?>"<?
								?> src="<?=\Bitrix\Disk\Ui\LazyLoad::getBase64Stub()?>"<?
								?> alt="<?=htmlspecialcharsbx($file["NAME"])?>"<?
								?> data-thumb-src="<?=$file["THUMB"]["src"] ?>"<?
								?> <?=$file['ATTRIBUTES_FOR_VIEWER']?> <?
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
								?> data-bx-preview="<?=$file["INLINE"]["src"] ?>"<?
							?> />
						</span>
						<?
					}
				}
				?>
			</div>
			<?

			if ($arParams['USE_TOGGLE_VIEW'])
			{
				?>
				<a href="javascript:void(0);" class="disk-uf-file-switch-control" data-bx-view-type="gallery"><?=Loc::getMessage('DISK_UF_FILE_SHOW_GALLERY')?></a>
				<?
			}
			?>
		</div>
		<?

		if ($jsIds <> '')
		{
			?><script>BX.LazyLoad.registerImages([<?=$jsIds?>], null, {dataSrcName: "thumbSrc"});</script><?
		}
	}

	if (!empty($arResult['FILES']) || !empty($arResult['DELETED_FILES']))
	{
		?>
		<script>BX.load(['/bitrix/js/disk/css/legacy_uf_common.css']);</script>
		<div class="feed-com-files">
			<div class="feed-com-files-title"><?=GetMessage('WDUF_FILES')?></div>
			<div class="feed-com-files-cont"><?

		$className = 'feed-com-file-wrap'.(count($arResult['FILES']) >= 5 ? ' feed-com-file-wrap-fullwidth' : '');
		foreach($arResult['FILES'] as $file)
		{
			$tooltipUserId = (
				$file['IS_LOCKED']
					? $file['CREATED_BY']
					: ''
			);
			?><div class="<?=$className?>">
				<span id="lock-anchor-created-<?= $file['ID'] ?>-<?= $component->getComponentId() ?>" bx-tooltip-user-id="<?=$tooltipUserId?>" class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?> js-disk-locked-document-tooltip">
				<? if($file['IS_LOCKED']) { ?>
					<div class="disk-locked-document-block-icon-small-file"></div>
				<? } ?>
				</span>
				<span class="feed-com-file-name-wrap">
					<a <?= ($file['FROM_EXTERNAL_SYSTEM'] && $file['CAN_UPDATE'])? 'style="color:#d9930a;"' : '' ?> target="_blank" href="<?=htmlspecialcharsbx($file['DOWNLOAD_URL'])?>"<?
						?> id="disk-attach-<?=$file['ID']?>"<?
						?> class="feed-com-file-name" <?
						?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
						?> bx-attach-file-id="<?=$file['FILE_ID']?>"<?
						if (isset($file['XML_ID'])): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;
						if (isset($file['TYPE_FILE'])): ?> bx-attach-file-type="<?=$file['TYPE_FILE']?>"<?endif;
						?> data-bx-baseElementId="disk-attach-<?=$file['ID']?>" <?=
							$file['ATTRIBUTES_FOR_VIEWER']
						?> alt="<?=htmlspecialcharsbx($file['NAME'])?>"<?
					?>><?=htmlspecialcharsbx($file['NAME'])?><?
					?></a><?
					?><span class="feed-com-file-size"><?=$file['SIZE']?></span><?
					?><script>
						BX.namespace("BX.Disk.Files");
						BX.Disk.Files['<?= $file['ID'] ?>'] = [
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --document"></span><span>' + BX.message('JS_CORE_VIEWER_VIEW_ELEMENT') +'</span>',
								onclick: function(e){
									top.BX.UI.Viewer.Instance.openByNode(BX("disk-attach-<?=$file['ID']?>"));
									BX.PopupMenu.currentItem.popupWindow.close();
									return e.preventDefault();
								}},
							<? if($file['EDITABLE'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF']) && !$arParams['DISABLE_LOCAL_EDIT']){ ?>
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --edit-pencil"></span><span>' + BX.message('JS_CORE_VIEWER_EDIT') +'</span>',
								onclick: function(e){
									top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$file['ID']?>"), 'edit', {
										modalWindow: BX.Disk.openBlankDocumentPopup()
									});
									BX.PopupMenu.currentItem.popupWindow.close();
									return e.preventDefault();
								}},
							<? } ?>
							<? if(!$arParams['DISABLE_LOCAL_EDIT']){ ?>
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --disk"></span><span>' + BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES_MSGVER_1') + '</span>',
								href : "#",
								onclick: function(e){
									top.BX.UI.Viewer.Instance.runActionByNode(BX("disk-attach-<?=$file['ID']?>"), 'copyToMe');
									BX.PopupMenu.currentItem.popupWindow.close();
									return e.preventDefault();
								}},
							<? } ?>
							<? if($file['FROM_EXTERNAL_SYSTEM'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF'])){ ?>
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --upload"></span><span>' + '<?= GetMessageJS("DISK_UF_FILE_RUN_FILE_IMPORT") ?>' + '</span>',
								onclick: function(e){
									top.BX.Disk.UF.runImport({id: <?= $file['ID'] ?>, name: '<?= CUtil::JSEscape($file['NAME']) ?>'});
									BX.PopupMenu.currentItem.popupWindow.close();
									return e.preventDefault();
								}},
							<? } ?>
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --download-3"></span><span>' + BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC') + '</span>',
								href : "<?=$file["DOWNLOAD_URL"]?>",
								onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}}
							<? if(!$arParams['DISABLE_LOCAL_EDIT']){ ?>
							,
							{
								className : 'disk-uf-file__popup-menu_item menu-popup-no-icon',
								html : '<span class="ui-icon-set --settings-2"></span><span>' + '<?= GetMessageJS("DISK_UF_FILE_SETTINGS_DOCS") ?>' + '</span>',
								onclick: function(e){
									BX.Disk.InformationPopups.openWindowForSelectDocumentService({viewInUf: true});
									BX.PopupMenu.currentItem.popupWindow.close();
									return e.preventDefault();
								}}
							<? } ?>
						];
					</script><?
					if($file['EDITABLE'] && $file['CAN_UPDATE'] && (!$file['IS_LOCKED'] || $file['IS_LOCKED_BY_SELF']) && !$arParams['DISABLE_LOCAL_EDIT']) {
						?><a class="feed-con-file-changes-link" href="#" onclick="top.BX.UI.Viewer.Instance.runActionByNode(BX('disk-attach-<?=$file['ID']?>'), 'edit', {
							modalWindow: BX.Disk.openBlankDocumentPopup()
						}); return false;"><?= GetMessage('WDUF_FILE_EDIT') ?></a><?
					}
					?><span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return DiskActionFileMenu('<?= $file['ID'] ?>', this, BX.Disk.Files['<?= $file['ID'] ?>']); return false;"><?= GetMessage('WDUF_MORE_ACTIONS') ?></span>
				</span>
			</div><?
		}
		foreach($arResult['DELETED_FILES'] as $file)
		{
			?><div class="<?=$className?>">
				<span id="lock-anchor-created-<?= $file['ID'] ?>-<?= $component->getComponentId() ?>" bx-tooltip-user-id="<?=$file['CREATED_BY']?>" class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?>">
				</span>
				<span class="feed-com-file-name-wrap">
					<span <?
						?> id="disk-attach-<?=$file['ID']?>"<?
						?> class="feed-com-file-deleted-name" <?
						?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
					?>><?=htmlspecialcharsbx($file['NAME'])?><?
					?></span><?
					?><span class="feed-com-file-size"><?=$file['SIZE']?></span>
					<span class="feed-con-file-text-notice" href="#"><?= GetMessage('DISK_UF_FILE_IS_DELETED') ?></span><?
					if($file['CAN_RESTORE'] && $file['TRASHCAN_URL']) {
						?><a class="feed-con-file-changes-link" href="<?= $file['TRASHCAN_URL'] ?>"><?= GetMessage('DISK_UF_FILE_RESTORE') ?></a><?
					} ?>
				</span>
			</div><?
		}
		?></div>
		</div>
		<?
	}

	if(
		$arResult['ENABLED_MOD_ZIP'] &&
		!empty($arResult['ATTACHED_IDS']) &&
		count($arResult['ATTACHED_IDS']) > 1
	)
	{
		?>
		<div class="disk-uf-file-download-archive">
			<a href="<?=$arResult['DOWNLOAD_ARCHIVE_URL']?>" class="disk-uf-file-download-archive-text"><?=GetMessage('WDUF_FILE_DOWNLOAD_ARCHIVE')?></a>
			<span class="disk-uf-file-download-archive-size">(<?=CFile::FormatSize($arResult['COMMON_SIZE'])?>)</span>
		</div>
		<?
	}
?>
</div>
<script>
	BX.ready(function() {
		new BX.Disk.UFShowController({
			nodeId: 'disk-attach-block-<?=$arResult['UID']?>',
			signedParameters: '<?=\Bitrix\Main\Component\ParameterSigner::signParameters($this->getComponent()->getName(), $arResult['SIGNED_PARAMS'])?>'
		});
	});
	<?

	if($arParams['DISABLE_LOCAL_EDIT'])
	{
		?>
		BX.Disk.Document.Local.Instance.disable();
		if(!BX.message('disk_document_service'))
		{
			BX.message({disk_document_service: 'g'});
		}
		<?
	}
?>
</script>
