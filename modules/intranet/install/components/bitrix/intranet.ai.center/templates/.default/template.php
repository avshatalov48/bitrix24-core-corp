<?
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$tileManagerId = "intranet.ai.center";

$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
	"ID" => $tileManagerId,
	"LIST" => $arResult["ITEMS"],
]);

?>

<script type="text/javascript">
	BX.ready(function() {
		new BX.Intranet.AI.Center(<?=Json::encode([
			"assistantAppId" => $arResult["ASSISTANT_APP_ID"],
			"tileManagerId" => $tileManagerId,
		])?>);
	});
</script>
