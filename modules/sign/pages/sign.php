<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var CMain */
global $APPLICATION;

if (!Loader::includeModule('sign'))
{
	ShowError('Module `signproxy` is not installed.');
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	exit;
}

Extension::load([
	'ui.buttons',
	'ui.forms',
	'crm.entity-editor',
	'sign.v2.ui.tokens',
	'sign.v2.wizard'
]);
?><html style="height: 100%">
<head>
	<title>Sign</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<?php $APPLICATION->ShowHead()?>
</head>
<body style="margin: 0; padding: 15px; height: 100%">
	<span class="ui-btn-success ui-btn ui-btn-lg ui-btn-no-caps">
		<?php echo Loc::getMessage('SIGN_PAGE_BUTTON_TITLE')?>
	</span>
	<script>
		BX.ready(() => {
			const wizard = new BX.Sign.V2.Wizard();
			const signButton = document.querySelector('.ui-btn-success');
			signButton.addEventListener('click', () => wizard.show());
		});
	</script>
</body>
</html><?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");