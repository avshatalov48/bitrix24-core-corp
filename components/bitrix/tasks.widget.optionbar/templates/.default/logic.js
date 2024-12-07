'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetOptionBar != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetOptionBar = BX.Tasks.Component.extend({
		sys: {
			code: 'wg-optbar'
		},
		methods: {
			bindEvents: function()
			{
				this.bindDelegateControl('flag', 'click', this.passCtx(this.onToggleFlag));
			},

			onToggleFlag: function(node)
			{
				var target = BX.data(node, 'target');
				if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					var flagNode = this.control(target);
					var flagName = BX.data(node, 'flag-name');

					var yesValue = BX.data(node, 'yes-value') || 'Y';
					var noValue = BX.data(node, 'no-value') || 'N';

					if(BX.type.isElementNode(flagNode))
					{
						flagNode.value = node.checked ? yesValue : noValue;
					}

					const limitExceeded = this.isLimitExceeded(flagName);
					if (limitExceeded)
					{
						this.performExceededActions(flagName, flagNode, node);

						this.showLimitDialog(
							this.getFeatureId(flagName),
							null,
							{
								module: 'tasks',
								source: 'taskEdit',
							},
						);
					}

					this.fireEvent('toggle', [flagName, flagNode.value == yesValue]);
				}
			},

			isLimitExceeded(flagName)
			{
				switch (flagName)
				{
					case 'TASK_CONTROL':
						return this.option('taskControlLimitExceeded');
					case 'MATCH_WORK_TIME':
						return this.option('taskSkipWeekendsLimitExceeded');
					default:
						return false;
				}
			},

			performExceededActions(flagName, flagNode, parenNode)
			{
				switch (flagName)
				{
					case 'TASK_CONTROL':
					case 'MATCH_WORK_TIME':
						if (flagNode.value === 'Y')
						{
							flagNode.value = 'N';
							parenNode.checked = false;
						}
				}
			},

			getFeatureId(flagName)
			{
				switch (flagName)
				{
					case 'TASK_CONTROL':
						return 'tasks_control';
					case 'MATCH_WORK_TIME':
						return 'tasks_skip_weekends';
					default:
						return '';
				}
			},

			showLimitDialog(featureId, bindElement, limitAnalyticsLabels)
			{
				return new Promise((resolve, reject) => {
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;

						Limit.showInstance({
							featureId,
							bindElement,
							limitAnalyticsLabels,
						});

						resolve();
					});
				});
			},

			toggleFlag: function(code, way)
			{
				// todo
			},

			/**
			 * Disables or enables option by a given flag code
			 * @param code
			 * @param way
			 */
			switchOption: function(code, way)
			{
				code = code.toLowerCase().replace(/_/g, '-');

				var node = this.control('flag-'+code);
				if(node)
				{
					if(way)
					{
						BX.Tasks.Util.enable(node);
					}
					else
					{
						BX.Tasks.Util.disable(node);
						if(this.isOptionChecked(node))
						{
							// todo: use 'change' event here!
							// uncheck
							node.checked = false;
							this.onToggleFlag(node);
						}
					}

					BX.data(this.control('flag-label-'+code), 'hint-enabled', !way);
				}
			},

			/**
			 * @deprecated
			 * @param code
			 * @param way
             * @returns {*}
             */
			switchFlag: function(code, way)
			{
				return this.switchOption(code, way);
			},

			isOptionChecked: function(node)
			{
				return node.checked;
			}
		}
	});

}).call(this);