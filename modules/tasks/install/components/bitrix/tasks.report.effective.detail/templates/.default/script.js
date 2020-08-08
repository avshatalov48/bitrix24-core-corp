'use strict';

BX.namespace('BX.Tasks');

BX.Tasks.TasksReportEffectiveDetail = function(options)
{
	this.pathToTasks = options.pathToTasks;

	this.bindEvents();
	this.handleFeatureDisabling(options.taskLimitExceeded);
};

BX.Tasks.TasksReportEffectiveDetail.prototype = {
	constructor: BX.Tasks.TasksReportEffectiveDetail,

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

	handleFeatureDisabling: function(taskLimitExceeded)
	{
		if (taskLimitExceeded)
		{
			BX.UI.InfoHelper.show('limit_tasks_efficiency');
		}
	}
};