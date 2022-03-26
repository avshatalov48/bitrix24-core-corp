'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetReplicationView != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetReplicationView = BX.Tasks.Component.extend({
		sys: {
			code: 'replication'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.vars.enabled = this.option('enabled');
				this.vars.taskLimitExceeded = this.option('taskLimitExceeded');
			},

			bindEvents: function()
			{
				if(this.option('enableSync'))
				{
					this.bindControl('switch', 'click', this.onReplicationToggle.bind(this));
				}
			},

			onReplicationToggle: function()
			{
				if (!this.vars.enabled && this.vars.taskLimitExceeded)
				{
					BX.UI.InfoHelper.show('limit_tasks_recurring_tasks', {
						isLimit: true,
						limitAnalyticsLabels: {
							module: 'tasks',
							source: 'templateSidebar'
						}
					});
					return;
				}

				var action = (this.vars.enabled ? 'stopReplication' : 'startReplication');

				BX.ajax.runComponentAction('bitrix:tasks.widget.replication', action, {
					mode: 'class',
					data: {
						templateId: this.option('entityId')
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							return;
						}

						this.setEnabled(!this.vars.enabled);
					}.bind(this),
					function(response)
					{
					}.bind(this)
				);
			},

			setEnabled: function(flag)
			{
				this.vars.enabled = flag;
				this.changeCSSFlag('enabled', flag);

				BX.Tasks.Util.fadeSlideToggleByClass(this.control('detail'));
			}
		}
	});

}).call(this);