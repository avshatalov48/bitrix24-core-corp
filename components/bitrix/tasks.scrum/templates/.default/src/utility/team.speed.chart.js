import {Loc} from 'main.core';

type Data = {
	sprintName: string,
	plan: number,
	done: number
}

//todo import amchart4 like es6
export class TeamSpeedChart
{
	constructor(data: Data)
	{
		this.data = data;

		this.chart = null;
	}

	createChart(chartDiv: HTMLElement)
	{
		am4core.useTheme(am4themes_animated);

		this.chart = am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = this.data;
		this.chart.paddingRight = 40;

		this.chart.scrollbarX = new am4core.Scrollbar();
		this.chart.scrollbarX.parent = this.chart.bottomAxesContainer;

		this.createAxises();

		this.createColumn('plan', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
		this.createColumn('done', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');

		this.createLegend();
	}

	createAxises()
	{
		const xAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
		xAxis.dataFields.category = 'sprintName';
		xAxis.renderer.grid.template.location = 0;

		const label = xAxis.renderer.labels.template;
		label.wrap = true;
		label.maxWidth = 120;

		const yAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
		yAxis.min = 0;
	}

	createColumn(valueY: string, name: string, color: string)
	{
		const series = this.chart.series.push(new am4charts.ColumnSeries());
		series.dataFields.valueY = valueY;
		series.dataFields.categoryX = 'sprintName';
		series.name = name;
		series.stroke = am4core.color(color);
		series.fill = am4core.color(color);

		// const bullet = series.bullets.push(new am4charts.LabelBullet())
		// bullet.dy = 10;
		// bullet.label.text = '{valueY}';
		// bullet.label.fill = am4core.color('#ffffff');

		return series;
	}

	createLegend()
	{
		this.chart.legend = new am4charts.Legend();
		this.chart.legend.position = 'bottom';
		this.chart.legend.paddingBottom = 20;
		this.chart.legend.itemContainers.template.clickable = false;
	}

	destroyChart()
	{
		this.chart.dispose();
	}
}