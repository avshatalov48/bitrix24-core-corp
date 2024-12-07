<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.sidepanel-content',
]);
?>

<div class="ui-slider-no-access-inner">
	<div class="ui-slider-no-access-title"><?php echo htmlspecialcharsbx($arResult['ERRORS'][0] ?? '') ?></div>
	<div class="ui-slider-no-access-subtitle"><?php echo htmlspecialcharsbx(Loc::getMessage('CRM_WEBFORM_UNAVAILABLE_ASK_ADMIN')); ?></div>
	<div class="ui-slider-no-access-img">
		<div class="ui-slider-no-access-img-inner"></div>
	</div>
</div>

<?php

if (($arResult['TARIFF_RESTRICTED'] ?? 'N') === 'Y')
{
?>
	<script>
		BX.ready(function() {
			BX.UI.InfoHelper.show('limit_crm_webform_edit');
		});
	</script>
<?php
}
