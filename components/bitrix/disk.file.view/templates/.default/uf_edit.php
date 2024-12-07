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
<div id="bx-disk-file-uf-errors" class="errortext" style="color: red; padding-bottom: 10px;"></div>
<form action="<?php echo POST_FORM_ACTION_URI ?>" method="post" name="file-edit-form" id="file-edit-form" enctype="multipart/form-data">
<?php echo bitrix_sessid_post() ?>
	<input type="hidden" name="fileId" value="<?= $arResult['FILE']['ID'] ?>">
<table>
	<tbody>
		<? foreach($arResult["USER_FIELDS"] as $arUserField) {?>
		<tr>
			<td class="bx-disk-filepage-fileinfo-param"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
			<td class="bx-disk-filepage-fileinfo-value">
				<?
					$APPLICATION->IncludeComponent(
						"bitrix:system.field.edit",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"bVarsFromForm" => false,
							"arUserField" => $arUserField,
							"form_name" => "file-edit-form",
						), null, array("HIDE_ICONS" => "Y")
					);
				 ?>
			</td>
		</tr>
		<? }?>
	</tbody>
</table>
	<div class="webform-buttons disk-detail-uf-form-buttons-fixed">
		<div class="disk-detail-uf-form-footer-container">
			<button class="ui-btn ui-btn-success" id="bx-disk-submit-uf-file-edit-form"><?= Loc::getMessage('DISK_FILE_VIEW_BTN_EDIT_USER_FIELDS') ?></button>
			<button class="ui-btn ui-btn-link" id="bx-disk-submit-uf-file-discard-form"><?= Loc::getMessage('DISK_FILE_VIEW_BTN_DISCARD_USER_FIELDS') ?></button>
		</div>
	</div>
</form>


<script>
	BX(function () {
		var submitForm = function(e){
			if(BX.hasClass(BX('bx-disk-submit-uf-file-edit-form'), 'clock'))
			{
				BX.PreventDefault(e);
				return;
			}
			BX('bx-disk-submit-uf-file-discard-form').style.opacity = '0';
			BX.addClass(BX('bx-disk-submit-uf-file-edit-form'), 'clock');
			BX.ajax.submitAjax(BX('file-edit-form'), {
				url: BX.Disk.addToLinkParam('/bitrix/components/bitrix/disk.file.view/ajax.php', 'action', 'saveUserField'),
				dataType : "json",
				method : "POST",
				onsuccess: BX.delegate(function (response){

					if (!response) {
						BX.removeClass(BX('bx-disk-submit-uf-file-edit-form'), 'clock');
						return;
					}
					if(response.status === 'error')
					{
						BX.removeClass(BX('bx-disk-submit-uf-file-edit-form'), 'clock');

						var msg = [];
						for(var i in response.errors)
						{
							if(!response.errors.hasOwnProperty(i))
								continue;
							msg.push(response.errors[i].message);
						}
						BX.adjust(BX('bx-disk-file-uf-errors'), {
							html: msg.join('<br/>')
						});
						BX.scrollToNode(BX('bx-disk-file-uf-errors'));
					}
					if(response.status === 'success')
					{
						var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
						if (sliderByWindow)
						{
							BX.SidePanel.Instance.postMessageAll(window, 'Disk.File.Uf:onUpdated');

							var sliderWithShow = BX.SidePanel.Instance.getPreviousSlider(sliderByWindow);
							sliderByWindow.close();

							if (sliderWithShow && sliderWithShow.getUrl().indexOf('action=showUserField'))
							{
								sliderWithShow.showLoader();
								sliderWithShow.getFrameWindow().location.reload();
							}
						}
					}
				}, this)
			});

			BX.PreventDefault(e);
		};
		var discardForm = function(e){

			var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
			if (sliderByWindow)
			{
				sliderByWindow.close();
			}
		};

		BX.bind(BX('bx-disk-submit-uf-file-discard-form'), 'click', discardForm);

		BX.bind(BX('file-edit-form'), 'submit', submitForm);
	});
</script>

