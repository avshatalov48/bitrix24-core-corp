<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);
?>
<div class="salescenter-limit-container">
	<div class="salescenter-limit-inner">
		<div class="salescenter-limit-desc">
			<div class="salescenter-limit-img">
				<div class="salescenter-limit-img-lock"></div>
			</div>
			<div class="salescenter-limit-desc-text">
				<?=$arResult['message'];?>
			</div>
		</div>
		<div class="salescenter-limit-buttons">
			<?php
			\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->showTariffRestrictionButtons($arResult['featureName']);
			?>
		</div>
	</div>
</div>