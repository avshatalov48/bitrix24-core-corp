import {Loc} from 'main.core';

type Data = {
	day: number,
	idealValue: number,
	remainValue: number
}

//todo import amchart4 like es6
export class BurnDownChart
{
	constructor(data: Data)
	{
		this.data = data;
	}

	createChart(chartDiv: HTMLElement)
	{
		am4core.useTheme(am4themes_animated);

		this.chart = am4core.create(chartDiv, am4charts.XYChart);
		this.chart.data = this.data;
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
		lineSeries.name = Loc.getMessage('TASKS_SCRUM_SPRINT_IDEAL_BURN_DOWN_CHART_LINE_TITLE'); //todo move from class
		lineSeries.stroke = am4core.color('#2882b3');
		lineSeries.strokeWidth = 2;

		lineSeries.dataFields.categoryX = 'day';
		lineSeries.dataFields.valueY = 'idealValue';

		const circleColor = '#2882b3';
		const circleBullet = new am4charts.CircleBullet();
		circleBullet.circle.radius = 4;
		circleBullet.circle.fill = am4core.color(circleColor);
		circleBullet.circle.stroke = am4core.color(circleColor);

		lineSeries.bullets.push(circleBullet);

		const segment = lineSeries.segments.template;
		const hoverState = segment.states.create('hover');
		hoverState.properties.strokeWidth = 4;
	}

	createRemainLine()
	{
		const lineSeries = this.chart.series.push(new am4charts.LineSeries());
		lineSeries.name = Loc.getMessage('TASKS_SCRUM_SPRINT_REMAIN_BURN_DOWN_CHART_LINE_TITLE'); //todo move from class
		lineSeries.stroke = am4core.color('#9c1f1f');
		lineSeries.strokeWidth = 2;

		lineSeries.dataFields.categoryX = 'day';
		lineSeries.dataFields.valueY = 'remainValue';

		const circleColor = '#9c1f1f';
		const circleBullet = new am4charts.CircleBullet();
		circleBullet.circle.radius = 4;
		circleBullet.circle.fill = am4core.color(circleColor);
		circleBullet.circle.stroke = am4core.color(circleColor);

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

	destroyBurnDownChart()
	{
		this.chart.dispose();
	}
}