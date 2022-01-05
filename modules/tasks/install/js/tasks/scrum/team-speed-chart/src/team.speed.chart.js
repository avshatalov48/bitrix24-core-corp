import {ajax, Loc, Runtime, Tag} from 'main.core';

import * as am4core from 'amcharts4';
import * as am4themes_animated from 'amcharts4_theme_animated';

import {Layout} from 'ui.sidepanel.layout';

import '../css/base.css';

type Params = {
	groupId: number
}

type ChartData = {
	sprintName: string,
	plan: number,
	done: number
}

type Response = {
	data: Array<ChartData>
}

export class TeamSpeedChart
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.chart = null;
		this.chartData = null;
	}

	show()
	{
		this.sidePanelManager.open(
			'tasks-scrum-sprint-team-speed-chart-side-panel',
			{
				cacheable: false,
				events: {
					onLoad: this.onSidePanelLoad.bind(this),
					onCloseComplete: this.onSidePanelAfterClose.bind(this)
				},
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.team-speed-chart'],
						title: Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_TITLE'),
						content: this.createContent.bind(this),
						design: {
							section: false
						},
						buttons: []
					});
				}
			}
		);
	}

	onSidePanelLoad(event)
	{
		const sidePanel = event.getSlider();

		setTimeout(() => {
			this.createChart(
				sidePanel.getContainer().querySelector('.tasks-scrum-sprint-team-speed-chart'),
				this.chartData
			);
		}, 300);
	}

	onSidePanelAfterClose()
	{
		this.destroyChart();
	}

	createContent()
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'bitrix:tasks.scrum.sprint.getTeamSpeedChartData',
				{
					data: {
						groupId: this.groupId
					}
				}
			)
				.then((response: Response) => {
					this.chartData = response.data;
					resolve(this.render());
				})
			;
		});
	}

	render(): HTMLElement
	{
		return Tag.render`<div class="tasks-scrum-sprint-team-speed-chart"></div>`;
	}

	createChart(chartDiv: HTMLElement, data: ChartData)
	{
		window.am4core.useTheme(am4themes_animated);

		this.chart = window.am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = data;
		this.chart.paddingRight = 40;

		this.chart.scrollbarX = new window.am4core.Scrollbar();
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
		series.stroke = window.am4core.color(color);
		series.fill = window.am4core.color(color);

		// const bullet = series.bullets.push(new am4charts.LabelBullet())
		// bullet.dy = 10;
		// bullet.label.text = '{valueY}';
		// bullet.label.fill = window.am4core.color('#ffffff');

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
		if (this.chart)
		{
			this.chart.dispose();
		}
	}
}