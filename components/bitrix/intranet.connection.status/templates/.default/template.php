<?

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$this->setFrameMode(true);

Extension::load('pull.vue3.status');

?>
<div class="connection-status" id="connection-status"></div>
<script>
	BX.Vue3.BitrixVue.createApp(window.PullStatus).mount(<?= Json::encode($arResult['rootContainer']) ?>);

	BX.ZIndexManager.register(
		document.getElementById('connection-status'),
		{ alwaysOnTop: true },
	)
</script>
