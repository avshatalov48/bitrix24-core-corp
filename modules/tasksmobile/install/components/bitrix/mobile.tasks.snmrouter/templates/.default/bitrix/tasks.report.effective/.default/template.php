<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 */

Extension::load([
	'ui.alerts',
	'ui.buttons.icons',
	'ui.graph.circle',
]);

if (isset($arResult['ERROR']) && !empty($arResult['ERROR']))
{
	foreach ($arResult['ERROR'] as $error)
	{
		?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error['MESSAGE']) ?></span>
		</div>
		<?php
	}

	return;
}
if ($arResult['TASK_LIMIT_EXCEEDED'])
{
	?>
	<div class="ui-alert ui-alert-danger">
		<span class="ui-alert-message"><?= Loc::getMessage('TASKS_MOBILE_EFFICIENCY_LIMIT_EXCEEDED') ?></span>
	</div>
	<?php
	return;
}

$efficiencyData = $arResult['JS_DATA']['efficiencyData'];
?>

<div class="mobile-tasks-efficiency-wrap">
	<div class="mobile-tasks-efficiency-counter"></div>
	<div id="chartdiv" class="mobile-tasks-efficiency-graph"></div>
	<div class="mobile-tasks-efficiency-info-box">
		<div class="mobile-tasks-efficiency-info-row">
			<div class="mobile-tasks-efficiency-info-item mobile-tasks-efficiency-info-item--blue">
				<div class="mobile-tasks-efficiency-info-name"><?= Loc::getMessage('TASKS_MOBILE_EFFICIENCY_IN_PROGRESS') ?></div>
				<div class="mobile-tasks-efficiency-info-value"><?= (int)$efficiencyData['IN_PROGRESS'] ?></div>
			</div>
		</div>
		<div class="mobile-tasks-efficiency-info-row">
			<div class="mobile-tasks-efficiency-info-item mobile-tasks-efficiency-info-item--green">
				<div class="mobile-tasks-efficiency-info-name"><?= Loc::getMessage('TASKS_MOBILE_EFFICIENCY_COMPLETED') ?></div>
				<div class="mobile-tasks-efficiency-info-value"><?= (int)$efficiencyData['COMPLETED'] ?></div>
			</div>
			<div class="mobile-tasks-efficiency-info-item mobile-tasks-efficiency-info-item--red">
				<div class="mobile-tasks-efficiency-info-name"><?= Loc::getMessage('TASKS_MOBILE_EFFICIENCY_VIOLATIONS') ?></div>
				<div class="mobile-tasks-efficiency-info-value"><?= (int)$efficiencyData['VIOLATIONS'] ?></div>
			</div>
		</div>
	</div>
</div>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
<script>
	document.body.style.overflow = 'hidden';
	const rootElement = getComputedStyle(document.documentElement);
	const base4 = rootElement.getPropertyValue("--base4");

	am4core.ready(function() {
		am4core.useTheme(am4themes_animated);

		const chart = am4core.create('chartdiv', am4charts.XYChart);
		chart.data = <?= Json::encode($efficiencyData['GRAPH_DATA']) ?>;
		chart.maskBullets = false;

		const categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
		categoryAxis.dataFields.category = 'DATE';
		categoryAxis.renderer.minGridDistance = 30;
		categoryAxis.renderer.grid.template.location = 0;
		categoryAxis.renderer.labels.template.fontSize = 11;
		categoryAxis.renderer.labels.template.fill = base4;

		const valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
		valueAxis.renderer.labels.template.fontSize = 11;
		valueAxis.renderer.labels.template.fill = base4;
		valueAxis.min = 0;
		valueAxis.max = 100;
		valueAxis.strictMinMax = true;

		valueAxis.renderer.grid.template.strokeOpacity = 1;
		valueAxis.renderer.grid.template.strokeWidth = 1;
		valueAxis.renderer.grid.template.stroke = am4core.color(base4);
		valueAxis.renderer.line.strokeOpacity = 1;
		valueAxis.renderer.line.strokeWidth = 1;
		valueAxis.renderer.line.stroke = am4core.color(base4);

		const lineSeries = chart.series.push(new am4charts.LineSeries());
		lineSeries.dataFields.valueY = 'EFFECTIVE';
		lineSeries.dataFields.categoryX = 'DATE';
		lineSeries.strokeWidth = 2;

		const bullet = lineSeries.bullets.push(new am4charts.Bullet());
		const image = bullet.createChild(am4core.Circle);
		image.width = 9;
		image.height = 9;
		image.horizontalCenter = 'middle';
		image.verticalCenter = 'middle';
	});

	BX.ready(function() {
		const circle = new BX.UI.Graph.Circle(
			document.querySelector('.mobile-tasks-efficiency-counter'),
			150,
			<?= (int)$efficiencyData['EFFICIENCY'] ?>,
			null,
		);
		circle.show();
	});
</script>
