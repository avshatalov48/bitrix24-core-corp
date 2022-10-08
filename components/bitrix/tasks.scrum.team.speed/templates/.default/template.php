<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var \CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'amcharts4',
	'amcharts4_theme_animated',
]);

$messages = Loc::loadLanguageFile(__FILE__);

?>

<div class="tasks-scrum-sprint-team-speed">
	<div class="tasks-scrum-sprint-team-speed-filter">
		<div class="pagetitle-container pagetitle-flexible-space">
			<?php
				$APPLICATION->includeComponent(
					'bitrix:main.ui.filter',
					'',
					[
						'FILTER_ID' => $arResult['filterId'],
						'FILTER' => $arResult['filterFields'],
						'FILTER_PRESETS' => $arResult['filterPresets'],
						'ENABLE_LIVE_SEARCH' => false,
						'ENABLE_LABEL' => true,
						'DISABLE_SEARCH' => true,
						'RESET_TO_DEFAULT_MODE' => true,
						'VALUE_REQUIRED_MODE' => false,
					]
				);
			?>
		</div>
	</div>
	<div id="tasks-scrum-sprint-team-speed-chart" class="tasks-scrum-sprint-team-speed-chart"></div>
	<div id="tasks-scrum-sprint-team-speed-stats" class="tasks-scrum-sprint-team-speed-stats"></div>
</div>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode($messages) ?>);

		(new BX.Tasks.Scrum.TeamSpeed({
			filterId: '<?=$arResult['filterId']?>',
			signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
			chartData: <?= Json::encode($arResult['chartData']) ?>,
			statsData: <?= Json::encode($arResult['statsData']) ?>
		}))
			.renderTo(
				document.getElementById('tasks-scrum-sprint-team-speed-chart'),
				document.getElementById('tasks-scrum-sprint-team-speed-stats')
			)
		;
	});
</script>
