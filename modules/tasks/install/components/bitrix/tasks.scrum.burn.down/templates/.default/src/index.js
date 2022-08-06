import {Loc, Text, Type, ajax} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {Dialog} from 'ui.entity-selector';
import {Button} from 'ui.buttons';

import '../css/base.css';

type Params = {
	groupId: number,
	selectorContainer?: HTMLElement,
	infoContainer?: HTMLElement,
	currentSprint: Sprint,
}

type Sprint = {
	id: number,
	name: string,
	dateStartFormatted: string,
	dateEndFormatted: string
}

type ChartData = {
	day: number,
	idealValue: number,
	remainValue: number
}

export class BurnDownChart
{
	constructor(params: Params)
	{
		this.groupId = Type.isNumber(params.groupId) ? parseInt(params.groupId, 10) : 0;
		this.selectorContainer = params.selectorContainer;
		this.infoContainer = params.infoContainer;
		this.currentSprint = params.currentSprint;

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.chart = null;
	}

	render(chartDiv: HTMLElement, data: ChartData)
	{
		this.renderSelectorTo(this.selectorContainer);

		setTimeout(() => this.create(chartDiv, data), 300);
	}

	renderSelectorTo(selectorContainer: HTMLElement)
	{
		if (!Type.isElementNode(selectorContainer))
		{
			return;
		}

		this.selectorButton = new Button({
			text: this.currentSprint.dateStartFormatted + ' - ' + this.currentSprint.dateEndFormatted,
			color: Button.Color.LIGHT_BORDER,
			dropdown: true,
			className: 'ui-btn-themes',
			onclick: () => {
				const dialog = this.createSelectorDialog(this.selectorButton.getContainer(), this.currentSprint);
				dialog.show();
			},
		});

		this.selectorButton.renderTo(selectorContainer);
	}

	createSelectorDialog(targetNode: HTMLElement, currentSprint: Sprint): Dialog
	{
		return new Dialog({
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			compactView: true,
			showAvatars: false,
			cacheable: false,
			preselectedItems: [['sprint-selector' , currentSprint.id]],
			entities: [
				{
					id: 'sprint-selector',
					options: {
						groupId: this.groupId
					},
					dynamicLoad: true,
					dynamicSearch: true
				}
			],
			events: {
				'Item:onSelect': (event: BaseEvent) => {
					const { item: selectedItem } = event.getData();

					this.selectorButton.setText(selectedItem.customData.get('label'));

					this.changeChart(selectedItem.getId());
				},
			},
		});
	}

	changeChart(sprintId: number)
	{
		ajax.runComponentAction(
			'bitrix:tasks.scrum.burn.down',
			'changeChart',
			{
				mode: 'class',
				data: {
					groupId: this.groupId,
					sprintId: sprintId
				}
			}
		)
			.then((response) => {
				this.chart.data = response.data.chart;
				this.currentSprint = response.data.sprint;

				this.infoContainer
					.querySelector('.tasks-scrum-sprint-burn-down-info-name')
					.textContent = Text.encode(this.currentSprint.name)
				;
			})
		;
	}

	create(chartDiv: HTMLElement, data: ChartData)
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
}