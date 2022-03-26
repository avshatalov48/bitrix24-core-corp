<?

use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$tileManagerId = 'intranet.automation.center';

$APPLICATION->IncludeComponent('bitrix:ui.tile.list', '', [
	'ID' => $tileManagerId,
	'LIST' => $arResult['ITEMS'],
]);
?>

<script>
	BX.Event.ready(function() {
		new BX.Intranet.Automation.Center(<?=Json::encode([
			"tileManagerId" => $tileManagerId,
		])?>);
	});
</script>
