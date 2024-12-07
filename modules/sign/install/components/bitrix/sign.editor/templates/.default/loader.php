<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/template.php');
?>

<div style="padding: 200px;">
	<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_LOAD')?>
</div>

<script>
	BX.ready(function()
	{
		setTimeout(function() {
			window.location.href = BX.Uri.addParam(window.location.href);
		}, 2000);
	});
</script>
