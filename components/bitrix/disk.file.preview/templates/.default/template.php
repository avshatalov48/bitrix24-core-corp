<?
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/disk/css/disk.css');
?>

<div class="file-preview">
	<div class="file-preview-header">
		<span class="file-preview-header-icon bx-file-icon-container-small <?=$arResult["ICON_CLASS"]?>"></span>
		<span class="file-preview-header-title">
			<?=Loc::getMessage("FILE_PREVIEW_TITLE")?>:
			<a href="<?=$arResult['URL_DOWNLOAD']?>" target="_blank"><?= $arResult['NAME']?></a>
		</span>
	</div>

	<table class="file-preview-info">
		<tr>
			<td><?=Loc::getMessage('FILE_PREVIEW_SIZE')?>:</td>
			<td>
				<?=$arResult['SIZE']?>
			</td>
		</tr>
		<tr>
			<td><?=Loc::getMessage('FILE_PREVIEW_UPDATED')?>: </td>
			<td><?=$arResult['UPDATED']?></td>
		</tr>
	</table>
</div>
