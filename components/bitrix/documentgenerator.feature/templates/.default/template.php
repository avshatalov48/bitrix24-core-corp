<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>
<div class="documentgenerator-limit-container">
	<div class="documentgenerator-limit-inner">
		<div class="documentgenerator-limit-desc">
			<div class="documentgenerator-limit-img">
				<div class="documentgenerator-limit-img-lock"></div>
			</div>
			<div class="documentgenerator-limit-desc-text">
				<?=$arResult['message'];?>
			</div>
		</div>
		<div class="documentgenerator-limit-buttons">
			<?php
			Bitrix24Manager::showTariffRestrictionButtons();
			?>
		</div>
	</div>
</div>