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

\Bitrix\Main\UI\Extension::load("ui.icons.service");

if (!is_array($arResult["ITEMS"]) || empty($arResult["ITEMS"]))
	return;
?>

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
}

$jsParams = array(
	"tileManagerId" => $arResult['TILE_ID'],
	"synchroTileManagerId" => $arResult['SYNCHRO_TILE_ID']
);
?>
<script>
	BX.ready(function () {
		BX.CrmStart.Onec.initTile(<?=CUtil::PhpToJSObject($jsParams)?>);
	});
</script>
