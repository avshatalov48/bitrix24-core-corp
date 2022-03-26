import {ajax, Loc, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import '../css/base.css';

type Params = {
	filterId: string,
	signedParameters: string
}

type ChartData = {
	sprintName: string,
	plan: number,
	done: number
}

export class TeamSpeedChart
{
	constructor(params: Params)
	{
		this.filterId = params.filterId;
		this.signedParameters = params.signedParameters;

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.chart = null;
		this.chartData = null;

		this.loader = null;

		//this.initUiFilterManager(); // todo return later

		//this.bindEvents(); // todo return later
	}

	initUiFilterManager()
	{
		/* eslint-disable */
		this.filterManager = BX.Main.filterManager.getById(this.filterId);
		/* eslint-enable */
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	}

	render(chartDiv: HTMLElement, data: Array<ChartData>)
	{
		setTimeout(() => this.create(chartDiv, data), 300);
	}

	create(chartDiv: HTMLElement, data: Array<ChartData>)
	{
		am4core.useTheme(am4themes_animated);

		this.chart = am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = data;
		this.chart.paddingRight = 40;
		this.chart.responsive.enabled = true;

		this.createAxises();

		this.createColumn('plan', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
		this.createColumn('done', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');

		this.createLegend();

		if (data.length === 0)
		{
			this.showLoader(Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL'));
		}
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

	showLoader(labelMessage?: string)
	{
		this.removeLoader();

		this.loader = this.chart.tooltipContainer.createChild(am4core.Container);

		this.loader.background.fill = am4core.color('#fff');
		this.loader.background.fillOpacity = 0.8;
		this.loader.width = am4core.percent(100);
		this.loader.height = am4core.percent(100);

		if (!Type.isUndefined(labelMessage))
		{
			const loaderLabel = this.loader.createChild(am4core.Label);

			loaderLabel.text = labelMessage;
			loaderLabel.align = 'center';
			loaderLabel.valign = 'middle';
			loaderLabel.fontSize = 20;
		}
	}

	removeLoader()
	{
		if (this.loader !== null)
		{
			this.loader.dispose();
		}
	}

	onFilterApply(event: BaseEvent)
	{
		const [filterId, values, filterInstance, promise, params] = event.getCompatData();

		if (this.filterId !== filterId)
		{
			return;
		}

		if (this.chart)
		{
			this.showLoader();
		}

		ajax.runComponentAction(
			'bitrix:tasks.scrum.team.speed',
			'applyFilter',
			{
				mode: 'class',
				signedParameters: this.signedParameters,
				data: {}
			}
		)
			.then((response) => {
				if (this.chart)
				{
					const data: Array<ChartData> = response.data;
					this.chart.data = data;
					if (data.length > 0)
					{
						this.removeLoader();
					}
					else
					{
						this.showLoader(Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL'));
					}
				}
			})
		;
	}
}