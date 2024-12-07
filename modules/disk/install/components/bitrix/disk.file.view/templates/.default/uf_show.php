<?php
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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

CJSCore::Init([
	'disk',
	'core',
	'ui.buttons',
	'ui.design-tokens',
]);

Loc::loadMessages(__DIR__ . '/template.php');
$APPLICATION->setTitle(Loc::getMessage('DISK_FILE_VIEW_FILE_TITLE_USERFIELDS', ['#NAME#' => $arResult['FILE']['NAME'],]));
?>

<?
	if($arResult['CAN_UPDATE'])
	{
		?>
		<? $this->setViewTarget("inside_pagetitle", 10); ?>
			<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
				<div class="pagetitle-container pagetitle-align-right-container">
					<span id="bx-disk-edit-uf" class="ui-btn ui-btn-primary"><?= Loc::getMessage('DISK_FILE_VIEW_LINK_EDIT_USER_FIELDS') ?></span>
				</div>
			</div>
		<? $this->endViewTarget(); ?>
<?
	}
?>
<table>
	<tbody>
		<? foreach($arResult["USER_FIELDS"] as $arUserField) {?>
		<tr>
			<td class="bx-disk-filepage-fileinfo-param"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
			<td class="bx-disk-filepage-fileinfo-value">
				<? $APPLICATION->includeComponent(
					"bitrix:system.field.view",
					$arUserField["USER_TYPE"]["USER_TYPE_ID"],
					array("arUserField" => $arUserField),
					null,
					array("HIDE_ICONS"=>"Y")
				); ?>
			</td>
		</tr>
		<? }?>
	</tbody>
</table>

<script>
	BX(function () {
		var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
		if (sliderByWindow)
		{
			sliderByWindow.closeLoader();
		}

		BX.bind(BX('bx-disk-edit-uf'), 'click', function (event) {
			BX.SidePanel.Instance.open('?&action=editUserField', {
				allowChangeHistory: false
			});
		});
	});
</script>

