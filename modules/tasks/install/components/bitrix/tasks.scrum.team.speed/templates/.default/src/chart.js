import {Loc, Type} from 'main.core';

export type ChartData = {
	sprintName: string,
	plan: number,
	done: number
}

export class Chart
{
	constructor(data: Array<ChartData>)
	{
		this.data = data;

		this.chart = null;
		this.loader = null;
	}

	renderTo(chartDiv: HTMLElement)
	{
		setTimeout(() => this.create(chartDiv), 300);
	}

	create(chartDiv: HTMLElement)
	{
		am4core.useTheme(am4themes_animated);

		this.chart = am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = this.data;
		this.chart.paddingRight = 40;
		this.chart.responsive.enabled = true;

		this.createAxises();

		this.createColumn('plan', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
		this.createColumn('done', Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');

		this.createLegend();

		if (this.data.length === 0)
		{
			this.showLoader(true);
		}
	}

	render(data: Array<ChartData>)
	{
		if (!this.chart)
		{
			return;
		}

		this.data = data;

		this.chart.data = this.data;

		if (this.data.length > 0)
		{
			this.removeLoader();
		}
		else
		{
			this.showLoader(true);
		}
	}

	createAxises()
	{
		const xAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
		xAxis.dataFields.category = 'sprintName';
		xAxis.renderer.grid.template.location = 0;
		xAxis.renderer.labels.template.adapter
			.add('textOutput', (text) => Type.isNil(text) ? text : text.replace(/ \(.*/, ''))
		;

		const label = xAxis.renderer.labels.template;
		label.wrap = true;
		label.maxWidth = 120;

		const yAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
		yAxis.min = 0;
	}

	createColumn(valueY: string, name: string, color: string): Object
	{
		const series = this.chart.series.push(new am4charts.ColumnSeries());
		series.dataFields.valueY = valueY;
		series.dataFields.categoryX = 'sprintName';
		series.name = name;
		series.stroke = am4core.color(color);
		series.fill = am4core.color(color);
		series.columns.template.tooltipText = '{name}: [bold]{valueY}[/]';

		return series;
	}

	createLegend()
	{
		this.chart.legend = new am4charts.Legend();
		this.chart.legend.position = 'bottom';
		this.chart.legend.paddingBottom = 20;
		this.chart.legend.itemContainers.template.clickable = false;
	}

	showLoader(notData: boolean = false)
	{
		this.removeLoader();

		this.loader = this.chart.tooltipContainer.createChild(am4core.Container);

		this.loader.background.fill = am4core.color('#fff');
		this.loader.background.fillOpacity = 0.8;
		this.loader.width = am4core.percent(100);
		this.loader.height = am4core.percent(100);

		if (notData)
		{
			const loaderLabel = this.loader.createChild(am4core.Label);

			loaderLabel.text = Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL');
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
}