<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
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

$jsMessagesCodes = array(
	'FACEID_FTS_DELETE_ALL_CONFIRM'
);

$jsMessages = array();

foreach ($jsMessagesCodes as $code)
{
	$jsMessages[$code] = Loc::getMessage($code);
}

\Bitrix\Main\UI\Extension::load([
	"ui.design-tokens",
	"ui.buttons",
]);
?>

<div class="ftr-set-main-wrap" id="ftr-set-main-wrap">
	<div class="ftr-set-top-title"></div>
	<div class="ftr-set-inner-wrap">
		<form action="<?=POST_FORM_ACTION_URI?>" method="POST" id="fts_config_edit_form">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="form" value="ftracker_settings_edit_form" />
			<input type="hidden" name="action" value="save" id="fts_config_edit_form_action" />
			<div class="ftr-set-cont-block">
				<?if($arResult["ERROR"] <> ''):?>
					<div class="ftr-set-cont-error"><?=$arResult['ERROR']?></div>
				<?endif;?>
				<?if($arResult["INFO"] <> ''):?>
					<div class="ftr-set-cont-info"><?=$arResult['INFO']?></div>
				<?endif;?>
				<div class="ftr-set-item">
					<div class="ftr-set-item-cont-block">
						<div class="ftr-set-item-cont"  <? if ($arResult['IS_CRM_INSTALLED'] == "N") { ?>style="display:none"<? } ?>>
							<div class="ftr-set-item-select-block" style="background-color: #fff; padding-top: 10px;">
								<div class="ftr-set-item-select-text"><?= Loc::getMessage("FACEID_FTS_AUTO_LEAD_SETTING") ?> &mdash; </div>
								<select class="ftr-set-inp ftr-set-item-select" name="FTRACKER_AUTO_CREATE_LEAD">
									<option value="N" <?if($arResult["FTRACKER_AUTO_CREATE_LEAD"] == "N") { ?>selected<? }?>><?= Loc::getMessage("FACEID_FTS_AUTO_LEAD_SETTING_M") ?></option>
									<option value="Y" <?if($arResult["FTRACKER_AUTO_CREATE_LEAD"] == "Y") { ?>selected<? }?>><?=Loc::getMessage("FACEID_FTS_AUTO_LEAD_SETTING_A")?></option>
								</select>
							</div>
							<div  class="ftr-set-item-select-block ftr-set-item-crm-rule" style="background-color: #fff; height: 55px;">
								<div class="ftr-set-item-select-text"><?=Loc::getMessage("FACEID_FTS_LEAD_SOURCE_SETTING")?> &nbsp; &mdash; &nbsp;</div>
								<select class="ftr-set-inp ftr-set-item-select" name="FTRACKER_LEAD_SOURCE">
									<?foreach ($arResult['CRM_SOURCES'] as $value => $name):?>
										<option value="<?=$value?>" <?if($arResult["FTRACKER_LEAD_SOURCE"] == $value) { ?>selected<? }?> ><?=htmlspecialcharsbx($name)?></option>
									<?endforeach;?>
								</select>
							</div>
							<div class="ftr-set-item-select-block ftr-set-item-crm-rule"  style="<?=($arResult["CONFIG"]["CRM_CREATE"] != 'none'? 'height: 19px;': '')?>">
								<input id='faceid_fts_socnet_enabled' type="checkbox" name="FTRACKER_SOCNET_ENABLED" <?if($arResult["FTRACKER_SOCNET_ENABLED"] == "Y") { ?>checked<? }?>  value="Y" class="ftr-set-checkbox"/>
								<div class="ftr-set-item-select-text"><label for="faceid_fts_socnet_enabled"><?= Loc::getMessage("FACEID_FTS_SOCNET_ENABLED") ?></label></div>
							</div>
						</div>
					</div>
				</div>
				<div class="ftr-set-item">
					<div class="ftr-set-item-cont-block">
						<div class="ftr-set-item-cont">
							<div class="ftr-set-item-select-block ftr-set-item-crm-rule"  style="<?=($arResult["CONFIG"]["CRM_CREATE"] != 'none'? 'height: 19px;': '')?>">
								<a id="fts_config_delete_button" href="" style="color: red"><?= Loc::getMessage("FACEID_FTS_DELETE_ALL") ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>

		<form action="<?=POST_FORM_ACTION_URI?>" method="POST" id="fts_config_delete_all_form">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="form" value="ftracker_delete_all_form" />
		</form>

		<div class="ftr-set-footer-btn">
			<?if ($arResult['CAN_EDIT']):?>
				<button class="ui-btn ui-btn-md ui-btn-success" onclick="BX.submit(BX('fts_config_edit_form'))"><?=Loc::getMessage("FACEID_FTS_CONFIG_EDIT_SAVE")?></button>
			<?endif?>
			<a href="<?=$arResult['PATH_TO_FTRACKER']?>" class="ui-btn ui-btn-md ui-btn-link ui-btn-no-caps ftr-set-footer-btn-link"><?= Loc::getMessage("FACEID_FTS_TO_FTRACKER") ?></a>
		</div>
	</div>
</div>

<script type="text/javascript">

	BX.ready(function () {

		BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);

		BX.bind(BX('fts_config_delete_button'), 'click', function(e) {
			e.preventDefault();

			if (confirm(BX.message('FACEID_FTS_DELETE_ALL_CONFIRM')))
			{
				document.getElementById('fts_config_delete_all_form').submit();
			}
		});
	});
</script>