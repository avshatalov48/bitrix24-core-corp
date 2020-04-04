<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @global CMain $APPLICATION
 */

if ($arResult['INCLUDE_LANG'])
{
	\Bitrix\Main\Localization\Loc::loadLanguageFile(dirname(__FILE__)."/template.php");
}

?>
				<div class="meeting-detail-title"><?=htmlspecialcharsbx($arResult['ITEM']['TITLE'])?></div>
<?
if (strlen($arResult['ITEM']['DESCRIPTION']) > 0):
?>
				<div id="meeting-detail-description" class="meeting-detail-description"><?=$arResult['ITEM']['DESCRIPTION']?></div>
<?
endif;

?>
				<div class="meeting-detail-files">
<?
if (count($arResult['ITEM']['FILES']) > 0):
?>
					<label class="meeting-detail-files-title"><?=GetMessage('ME_FILES')?>:</label>
					<div class="meeting-detail-files-list">
<?
	foreach ($arResult['ITEM']['FILES'] as $ix => $arFile):
?>
						<div class="meeting-detail-file"><span class="meeting-detail-file-number"><?=$ix+1?>.</span><span class="meeting-detail-file-info"><?if($arFile['FILE_SRC']):?><a href="#message<?=$arFile['FILE_SRC']?>" class="meeting-detail-file-comment"></a><?endif?><a class="meeting-detail-file-link" href="<?=$arFile['DOWNLOAD_URL']?>"><?=$arFile['ORIGINAL_NAME']?></a><span class="meeting-detail-file-size">(<?=$arFile['FILE_SIZE_FORMATTED']?>)</span></span></div><input type="hidden" id="meeting_file_<?=$arFile['ID'];?>" name="ITEM_FILES[]" value="<?=$arFile['ID'];?>" />
<?
	endforeach;
?>
					</div>
<?
endif;
?>
				</div>