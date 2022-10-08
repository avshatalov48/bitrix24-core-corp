import {ajax} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Chart, ChartData} from './chart';
import {Stats, StatsData} from './stats';

import '../css/base.css';

type Params = {
	filterId: string,
	signedParameters: string,
	chartData: Array<ChartData>,
	statsData: StatsData
}

export class TeamSpeed
{
	constructor(params: Params)
	{
		this.filterId = params.filterId;
		this.signedParameters = params.signedParameters;

		this.chart = new Chart(params.chartData);
		this.stats = new Stats(params.statsData);

		/* eslint-disable */
		this.filterManager = BX.Main.filterManager.getById(this.filterId);
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.bindEvents();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	}

	renderTo(chartRoot: HTMLElement, statsRoot: HTMLElement)
	{
		this.chart.renderTo(chartRoot);
		this.stats.renderTo(statsRoot);
	}

	onFilterApply(event: BaseEvent)
	{
		const [filterId, values, filterInstance, promise, params] = event.getCompatData();

		if (this.filterId !== filterId)
		{
			return;
		}

		this.chart.showLoader();
		this.stats.showLoader();

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
				const chartData: Array<ChartData> = response.data.chartData;
				const statsData: StatsData = response.data.statsData;

				this.chart.render(chartData);
				this.stats.render(statsData);
			})
		;
	}
}