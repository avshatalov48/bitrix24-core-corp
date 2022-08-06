<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.vue',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.icons',
	'ui.common',
	'ui.forms',
	'ui.alerts',
	'ui.pinner',
	'ui.button.panel',
	'ui.progressbar',
	'ui.hint',
	'ui.sidepanel-content',
	'crm.config.catalog',
]);

?>
<div id="catalogConfig"></div>
<script>
	BX.ready(function() {
		(new BX.Crm.Config.Catalog.App({propsData: {
			initData: <?=CUtil::PhpToJSObject($arResult)?>
		}})).$mount(
			document.getElementById('catalogConfig')
		);
	});
</script>
