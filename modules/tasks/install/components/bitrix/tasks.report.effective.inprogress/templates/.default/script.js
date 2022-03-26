'use strict';

BX.namespace('BX.Tasks');

BX.Tasks.TasksReportEffectiveInProgress = function(options)
{
	this.pathToTasks = options.pathToTasks;

	this.bindEvents();

	var isLimit = options.taskLimitExceeded || options.kpiLimitExceeded;
	this.handleFeatureDisabling(isLimit);
};

BX.Tasks.TasksReportEffectiveInProgress.prototype = {
	constructor: BX.Tasks.TasksReportEffectiveInProgress,

	bindEvents: function()
	{
		BX.addCustomEvent('SidePanel.Slider:onClose', this.onSliderClose.bind(this));
	},

	onSliderClose: function(event)
	{
		if (event.getSlider().getUrl() === 'ui:info_helper')
		{
			window.location.href = this.pathToTasks;
		}
	},

	handleFeatureDisabling: function(isLimit)
	{
		if (isLimit)
		{
			BX.UI.InfoHelper.show('limit_tasks_efficiency', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: 'tasks',
					source: 'inProgress'
				},
			});
		}
	}
};