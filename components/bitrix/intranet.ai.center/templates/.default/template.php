<?
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.dialogs.messagebox']);

$tileManagerId = "intranet.ai.center";

$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
	"ID" => $tileManagerId,
	"LIST" => $arResult["ITEMS"],
]);

?>

<script type="text/javascript">
	BX.message({
		'INTRANET_AI_CENTER_ML_REQUIRED': '<?= GetMessageJS('INTRANET_AI_CENTER_ML_REQUIRED')?>'
	});
	BX.ready(function() {
		new BX.Intranet.AI.Center(<?=Json::encode([
			"assistantAppId" => $arResult["ASSISTANT_APP_ID"],
			"mlInstalled" => $arResult["ML_INSTALLED"],
			"tileManagerId" => $tileManagerId,
		])?>);
	});
</script>
