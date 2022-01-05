import {ajax, Loc, Runtime, Tag} from 'main.core';

import * as am4core from 'amcharts4';
import * as am4themes_animated from 'amcharts4_theme_animated';

import {Layout} from 'ui.sidepanel.layout';

import '../css/base.css';

type Params = {
	groupId: number,
	sprintId: number
}

type ChartData = {
	day: number,
	idealValue: number,
	remainValue: number
}

type Response = {
	data: Array<ChartData>
}

export class BurnDownChart
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);
		this.sprintId = parseInt(params.sprintId, 10);

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.chart = null;
		this.chartData = null;
	}

	show()
	{
		this.sidePanelManager.open(
			'tasks-scrum-sprint-burn-down-chart-side-panel',
			{
				cacheable: false,
				events: {
					onLoad: this.onSidePanelLoad.bind(this),
					onCloseComplete: this.onSidePanelAfterClose.bind(this)
				},
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.burn-down-chart'],
						title: Loc.getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_TITLE'),
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
				sidePanel.getContainer().querySelector('.tasks-scrum-sprint-burn-down-chart'),
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
				'bitrix:tasks.scrum.sprint.getBurnDownChartData',
				{
					data: {
						groupId: this.groupId,
						sprintId: this.sprintId
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
		return Tag.render`<div class="tasks-scrum-sprint-burn-down-chart"></div>`;
	}

	createChart(chartDiv: HTMLElement, data: ChartData)
	{
		window.am4core.useTheme(am4themes_animated);

		this.chart = window.am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = data;
		this.chart.paddingRight = 40;

		this.createAxises();

		this.createIdealLine();
		this.createRemainLine();

		this.createLegend();
	}

	createAxises()
	{
		const categoryAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
		categoryAxis.renderer.grid.template.location = 0;
		categoryAxis.dataFields.category = 'day';
		categoryAxis.renderer.minGridDistance = 60;

		const valueAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
		valueAxis.min = -0.1;
	}

	createIdealLine()
	{
		const lineSeries = this.chart.series.push(new am4charts.LineSeries());
		lineSeries.name = Loc.getMessage('TASKS_SCRUM_SPRINT_IDEAL_BURN_DOWN_CHART_LINE_LABEL');
		lineSeries.stroke = window.am4core.color('#2882b3');
		lineSeries.strokeWidth = 2;

		lineSeries.dataFields.categoryX = 'day';
		lineSeries.dataFields.valueY = 'idealValue';

		const circleColor = '#2882b3';
		const circleBullet = new am4charts.CircleBullet();
		circleBullet.circle.radius = 4;
		circleBullet.circle.fill = window.am4core.color(circleColor);
		circleBullet.circle.stroke = window.am4core.color(circleColor);

		lineSeries.bullets.push(circleBullet);

		const segment = lineSeries.segments.template;
		const hoverState = segment.states.create('hover');
		hoverState.properties.strokeWidth = 4;
	}

	createRemainLine()
	{
		const lineSeries = this.chart.series.push(new am4charts.LineSeries());
		lineSeries.name = Loc.getMessage('TASKS_SCRUM_SPRINT_REMAIN_BURN_DOWN_CHART_LINE_LABEL');
		lineSeries.stroke = window.am4core.color('#9c1f1f');
		lineSeries.strokeWidth = 2;

		lineSeries.dataFields.categoryX = 'day';
		lineSeries.dataFields.valueY = 'remainValue';

		const circleColor = '#9c1f1f';
		const circleBullet = new am4charts.CircleBullet();
		circleBullet.circle.radius = 4;
		circleBullet.circle.fill = window.am4core.color(circleColor);
		circleBullet.circle.stroke = window.am4core.color(circleColor);

		lineSeries.bullets.push(circleBullet);

		const segment = lineSeries.segments.template;
		const hoverState = segment.states.create('hover');
		hoverState.properties.strokeWidth = 4;
	}

	createLegend()
	{
		this.chart.legend = new am4charts.Legend();
		this.chart.legend.itemContainers.template.clickable = false;
		this.chart.legend.position = 'bottom';
		this.chart.legend.itemContainers.template.events.on('over', (event) => {
			this.processOver(event.target.dataItem.dataContext);
		});
		this.chart.legend.itemContainers.template.events.on('out', () => this.processOut());
	}

	processOver(hoveredLine)
	{
		hoveredLine.toFront();
		hoveredLine.segments.each((segment) => segment.setState('hover'));
	};

	processOut()
	{
		this.chart.series.each((series) => {
			series.segments.each((segment) => segment.setState('default'));
			series.bulletsContainer.setState('default');
		});
	};

	destroyChart()
	{
		if (this.chart)
		{
			this.chart.dispose();
		}
	}
}