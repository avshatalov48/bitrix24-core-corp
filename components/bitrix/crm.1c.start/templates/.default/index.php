<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\Localization\Loc;

global $APPLICATION;
$this->setFrameMode(true);
$APPLICATION->SetTitle(Bitrix\Main\Localization\Loc::getMessage("CRM_1C_START_INDEX_NAME"));

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icons.service',
	'ui.forms',
	'ui.buttons',
	'ui.feedback.form',
]);

if (!is_array($arResult["ITEMS"]) || empty($arResult["ITEMS"]))
	return;
?>

<?php
if (is_array($arResult['INTEGRATION_ITEMS']) && !empty($arResult["INTEGRATION_ITEMS"]))
{
?>
<div class="crm-onec-block-title"><?=Loc::getMessage('CRM_1C_START_INTEGRATION_TITLE')?></div>
<div class="onec-block">
	<div class="onec-wrap" id="onec-wrap-integration">
		<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
			'ID' => $arResult['INTEGRATION_TILE_ID'],
			'LIST' => $arResult['INTEGRATION_ITEMS'],
		]);?>
	</div>
</div>

<br>
<br>
<?php
}
?>

<div class="crm-onec-block-title"><?=Loc::getMessage('CRM_1C_START_CONNECTION_TITLE')?></div>
<div class="onec-block">
	<div class="onec-wrap" id="onec-wrap">
		<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
			'ID' => $arResult['TILE_ID'],
			'LIST' => $arResult['ITEMS'],
		]);?>
	</div>
</div>

<br>
<br>

<?
if (is_array($arResult['SYNCHRO_ITEMS']) && !empty($arResult["ITEMS"]))
{
?>
	<div class="crm-onec-block-title"><?=Loc::getMessage('CRM_1C_START_SYNCHRO_TITLE')?></div>
	<div class="onec-block">
		<div class="onec-wrap" id="onec-synchro-wrap">
			<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
				'ID' => $arResult['SYNCHRO_TILE_ID'],
				'LIST' => $arResult['SYNCHRO_ITEMS'],
			]);?>
		</div>
	</div>
<?
}?>
<?
if (is_array($arResult['PLACEMENT_ITEMS']) && !empty($arResult["PLACEMENT_ITEMS"]))
{
	?>
	<div class="crm-onec-block-title"><?=Loc::getMessage('CRM_1C_START_OTHER_TITLE')?></div>
	<div class="onec-block onec-placement-block">
		<div class="onec-wrap" id="<?=htmlspecialcharsbx($arResult['PLACEMENT_ITEMS_ID'])?>">
			<div class="ui-tile-list-block">
				<div class="ui-tile-list-wrap">
					<div class="ui-tile-list-list">
						<? foreach ($arResult['PLACEMENT_ITEMS'] as $placement) :?>
							<div class="ui-tile-list-item">
								<?php
								$APPLICATION->includeComponent(
									'bitrix:app.layout',
									'',
									array(
										'ID' => $placement['APP_ID'],
										'PLACEMENT' => $placement['CODE'],
										'PLACEMENT_ID' => $placement['ID'],
										'SHOW_LOADER' => 'N',
										'SET_TITLE' => 'N',
										'PARAM' => [
											'FRAME_WIDTH' => '202px',
											'FRAME_HEIGHT' => '112px',
										],
										'PLACEMENT_OPTIONS' => $placement['OPTIONS'],
									),
									$component,
									array('HIDE_ICONS' => 'Y')
								);
								?>
							</div>
						<? endforeach;?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?
}
?>

<br>
<br>

<div class="crm-onec-block-title"><?=Loc::getMessage('CRM_1C_START_HELPER_TITLE')?></div>
<div class="onec-block">
	<div class="onec-wrap crm-onec-helper" id="onec-wrap">
		<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
			'ID' => $arResult['HELPER_TILE_ID'],
			'LIST' => $arResult['HELPER_ITEMS'],
		]);?>
	</div>
</div>

<?php
$jsParams = array(
	"tileManagerId" => $arResult['TILE_ID'],
	"synchroTileManagerId" => $arResult['SYNCHRO_TILE_ID'],
	"integrationTileManagerId" => $arResult['INTEGRATION_TILE_ID'],
	"otherTileManagerId" => $arResult['OTHER_TILE_ID'],
	"helperTileManagerId" => $arResult['HELPER_TILE_ID'],
	"formPortalUri" => $arResult['FORM_PORTAL_URI'],
);
?>
<script>
	BX.ready(function () {
		BX.CrmStart.Onec.initTile(<?=CUtil::PhpToJSObject($jsParams)?>);
	});
</script>
