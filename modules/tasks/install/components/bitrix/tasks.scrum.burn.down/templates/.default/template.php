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
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'amcharts4',
	'amcharts4_theme_animated',
	'ui.entity-selector',
	'ui.buttons',
	'ui.fonts.opensans',
]);

$messages = Loc::loadLanguageFile(__FILE__);

$this->SetViewTarget('pagetitle');
?>

<div id="tasks-scrum-sprint-burn-down-chart-selector"></div>

<?php
$this->EndViewTarget();
?>

<div id="tasks-scrum-sprint-burn-down-chart-info" class="tasks-scrum-sprint-burn-down-info">
	<div class="tasks-scrum-sprint-burn-down-info-name">
		<?= HtmlFilter::encode($arResult['sprint']['name']) ?>
	</div>
</div>
<div id="tasks-scrum-sprint-burn-down-chart" class="tasks-scrum-sprint-burn-down-chart"></div>

<script>
	BX.ready(function()
	{
		BX.message(<?= Json::encode($messages) ?>);

		(new BX.Tasks.Scrum.BurnDownChart({
			groupId: <?= (int) $arResult['groupId'] ?>,
			selectorContainer: document.getElementById('tasks-scrum-sprint-burn-down-chart-selector'),
			infoContainer: document.getElementById('tasks-scrum-sprint-burn-down-chart-info'),
			currentSprint: <?= Json::encode($arResult['sprint']) ?>
		}))
			.render(
				document.getElementById('tasks-scrum-sprint-burn-down-chart'),
				<?= Json::encode($arResult['chart']) ?>
			)
		;
	});
</script>
