<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Integration\BIConnector\FlowBIAnalytics;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

$dashboards = FlowBIAnalytics::getInstance()->getFlowsDashboards();

if (empty($dashboards))
{
	return;
}

$manyDashboards = count($dashboards) > 1;
$buttonSplitStyle = $manyDashboards ? 'ui-btn-split' : '';
$buttonMainStyle = $manyDashboards ? 'ui-btn-main' : 'ui-btn ui-btn-no-caps';
?>
<div class="tasks-flow__bi-analytics-container">
	<div class="<?= $buttonSplitStyle ?> ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round">
		<button
			class="<?= $buttonMainStyle ?> tasks-flow__bi-analytics"
			onclick='
				BX.Tasks.Flow.BIAnalytics.create({
					dashboards: <?= Json::encode($dashboards) ?>,
					target: this,
				}).openFirstDashboard();
				'
		>
			<?= Loc::getMessage('TASKS_FLOW_LIST_ANALYTICS_BTN') ?>
		</button>

	<?php if ($manyDashboards): ?>
		<button
			class="ui-btn-menu tasks-flow__bi-analytics"
			onclick='
				BX.Tasks.Flow.BIAnalytics.create({
					dashboards: <?= Json::encode($dashboards) ?>,
					target: this,
				}).openMenu();
				'
		>
		</button>
	<?php endif; ?>
	</div>
</div>