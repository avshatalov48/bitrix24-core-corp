<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

if ($arResult['isMobile'])
{
	\Bitrix\Main\UI\Extension::load([
		'intranet.mobile-popup',
	]);
	?>
	<script>
		BX.ready(() => {
			(new BX.Intranet.MobilePopup()).show();
		});
	</script>
<?php
}
else
{
?>
	<script>
		if (BX.browser.IsMobile())
		{
			BX.Runtime.loadExtension('intranet.mobile-popup').then((exports) => {
				(new exports.MobilePopup()).show();
			});
		}
	</script>
<?php
}