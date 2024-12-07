if (typeof(BX.FilterEntitySelector) === "undefined")
{
	BX.FilterEntitySelector = function ()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;
		this._selector = null;

		this._inputKeyPressHandler = BX.delegate(this.keypress, this);
	};

	BX.FilterEntitySelector.prototype =
		{
			initialize: function (id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._fieldId = this.getSetting("fieldId", "");

				BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
				BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));

			},
			getId: function ()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			keypress: function (e)
			{
				//e.target.value
			},
			open: function (field, query)
			{
				this._selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
					scope: field,
					id: this.getId() + "-selector",
					mode: this.getSetting("mode"),
					query: false,
					useSearch: true,
					useAdd: false,
					parent: this,
					popupOffsetTop: 5,
					popupOffsetLeft: 40
				});
				this._selector.bindEvent("item-selected", BX.delegate(function (data)
				{
					this._control.setData(BX.util.htmlspecialcharsback(data.nameFormatted), data.id);
					if (!this.getSetting("multi"))
					{
						this._selector.close();
					}
				}, this));
				this._selector.open();
			},
			close: function ()
			{
				if (this._selector)
				{
					this._selector.close();
				}
			},
			onCustomEntitySelectorOpen: function (control)
			{
				this._control = control;

				//BX.bind(control.field, "keyup", this._inputKeyPressHandler);

				if (this._fieldId !== control.getId())
				{
					this._selector = null;
					this.close();
				}
				else
				{
					this._selector = control;
					this.open(control.field);
				}
			},
			onCustomEntitySelectorClose: function (control)
			{
				if (this._fieldId !== control.getId())
				{
					this.close();
					//BX.unbind(control.field, "keyup", this._inputKeyPressHandler);
				}
			}
		};
	BX.FilterEntitySelector.closeAll = function ()
	{
		for (var k in this.items)
		{
			if (this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.FilterEntitySelector.items = {};
	BX.FilterEntitySelector.create = function(id, settings)
	{
		var self = new BX.FilterEntitySelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

(function() {
	'use strict';

	BX.namespace('Tasks.Component');

	if (typeof BX.Tasks.Component.TasksReportEffective != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksReportEffective = BX.Tasks.Component.extend({
		sys: {
			code: 'effective'
		},
		methodsStatic: {
			instance: {},
			getInstance: function()
			{
				return BX.Tasks.Component.TasksReportEffective.instance;
			},
			addInstance: function(obj)
			{
				BX.Tasks.Component.TasksReportEffective.instance = obj;
			}
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				BX.Tasks.Component.TasksReportEffective.addInstance(this);

				if(this.option('show_sl_effective_more'))
				{
					this.spotLightInit();
				}

				var efficiencyData = this.option('efficiencyData');
				this.render({
					efficiency: efficiencyData.EFFICIENCY,
					completed: efficiencyData.COMPLETED,
					violations: efficiencyData.VIOLATIONS,
					inProgress: efficiencyData.IN_PROGRESS,
					graphData: efficiencyData.GRAPH_DATA,
					minPeriod: efficiencyData.GRAPH_MIN_PERIOD
				});
			},

			bindEvents: function()
			{
				BX.addCustomEvent('BX.Main.Filter:apply', this.onFilterApply.bind(this));
				BX.addCustomEvent('SidePanel.Slider:onClose', this.onSliderClose.bind(this));

				BX.SidePanel.Instance.bindAnchors({
					rules: [
						{
							condition: [
								"/company/personal/user/" + this.option('userId') + "/tasks/effective/show/",
								"/company/personal/user/" + this.option('userId') + "/tasks/effective/inprogress/"
							],
							options: {
								cacheable: false
							},
							stopParameters: [
								"PAGEN_(\\d+)",
								"grid_action=pagination",
								"nav-effective",
								"MID"
							]
						}
					]
				});
			},

			onFilterApply: function(id, data, ctx, promise, params)
			{
				if (this.option('taskLimitExceeded') || !this.option('tasksEfficiencyEnabled'))
				{
					BX.Runtime.loadExtension('tasks.limit').then((exports) => {
						const { Limit } = exports;
						Limit.showInstance({
							featureId: 'tasks_efficiency',
							limitAnalyticsLabels: {
								module: 'tasks',
								source: 'filter',
							},
						});
					});

					return;
				}

				params.autoResolve = false;

				var instance = BX.Tasks.Component.TasksReportEffective.getInstance();

				var requestData = ctx.getFilterFieldsValues();
				requestData.userId = instance.option('userId');

				BX.ajax.runComponentAction('bitrix:tasks.report.effective', 'getEfficiencyData', {
					mode: 'class',
					data: {
						userId: instance.option('userId')
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

						var data = response.data;

						instance.render({
							efficiency: data.EFFICIENCY,
							completed: data.COMPLETED,
							violations: data.VIOLATIONS,
							inProgress: data.IN_PROGRESS,
							graphData: data.GRAPH_DATA,
							minPeriod: data.GRAPH_MIN_PERIOD
						});

					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			},

			onSliderClose: function(event)
			{
				if (event.getSlider().getUrl() === 'ui:info_helper')
				{
					window.location.href = this.option('pathToTasks');
				}
			},

			render: function(params)
			{
				try
				{
					this.graphCircleInit(params.efficiency);

					BX(this.control('completed')).innerHTML = params.completed;
					BX(this.control('violations')).innerHTML = params.violations;
					BX(this.control('in-progress')).innerHTML = params.inProgress;

					this.amchartInit(params.graphData, params.minPeriod);
				}
				catch (e)
				{
					document.location.href = document.location.href;
				}
			},

			graphCircleInit: function(efficiency)
			{
				var circle = this.option('circle');

				if (!circle)
				{
					// circle = new BX.Tasks.Graph.Circle(BX(this.control('circle')), 200, efficiency);
					circle = new BX.UI.Graph.Circle(this.control('circle'), 187, efficiency, null);
					circle.show();
					this.option('circle', circle);
				}
				else
				{
					circle.updateCounter(efficiency);
				}
			},

			amchartInit: function(graphData, minPeriod)
			{
				var messages = this.option('messages');
				var chart = this.option('amChart');

				if (!chart)
				{
					var monthNames = [];
					var shortMonthNames = [];
					for(var m = 1; m <= 12; m++)
					{
						monthNames.push(BX.message["MONTH_" + m.toString()]);
						shortMonthNames.push(BX.message["MON_" + m.toString()]);
					}
					AmCharts.monthNames = monthNames;
					AmCharts.shortMonthNames = shortMonthNames;


					chart = AmCharts.makeChart(BX(this.control('amchart')), {
						"type": "serial",
						"marginLeft": 20,
						"language": "ru",
						"dataProvider": graphData,
						"graphs": [
							{
								"id": "g1",
								"title": messages.graph_title_kpi,
								"valueField": "EFFECTIVE",
								"balloonText": "[[title]]: [[value]]%",
								"lineColor": "#4fc3f7",
								// "type": "smoothedLine",
								// "fillAlphas": 0.4
								"bullet": "round",
								"bulletBorderAlpha": 1,
								// "bulletColor": "#000",
								"bulletSize": 5
							}
							// ,
							// {
							// 	"id": "g2",
							// 	// "title": 'avg',
							// 	"valueField": "AVG",
							// 	"balloonText": '',
							// 	"lineColor": "#000",
							// 	"type": "line",
							// 	"lineAlpha": 0.1,
							// 	"fillAlphas": 0
							// }
						],
						"dataDateFormat": "YYYY-MM-DD HH:mm",
					 	"categoryField": "DATE",
						"categoryAxis": {
							"axisAlpha": 0.5,
							"axisColor": "#808992",
							"parseDates": true,
							"minPeriod": minPeriod,
							"dateFormats": [
								{ "period": "fff", "format": "JJ:NN:SS" },
								{ "period": "ss", "format": "JJ:NN:SS" },
								{ "period": "mm", "format": "JJ:NN" },
								{ "period": "hh", "format": "JJ:NN" },
								{ "period": "DD", "format": "DD MMM" },
								{ "period": "WW", "format": "DD MMM" },
								{ "period": "MM", "format": "MMM" },
								{ "period": "YYYY", "format": "YYYY" }
							]
						},

						"color": "#808992",

						"chartCursor": {
							"enabled": true,
							"oneBalloonOnly": true,
							"categoryBalloonEnabled": true,
							"categoryBalloonColor": "#000000",
							"categoryBalloonDateFormat": "DD MMM"
						},
						"numberFormatter": { "decimalSeparator": ".", "thousandsSeparator": " " },
						"valueAxes": [{
							"id": "v1",
							"axisAlpha": 0.3,
							"gridAlpha": 0,
							"axisColor": "#808992",
							"maximum": 100,
							"minimum": 0
						}
						]
					});

					if (!graphData.length)
					{
						chart.addLabel("50%", "50%", messages.no_data_text, "middle", 15);
					}
					this.option('amchart', chart);
				}
				else
				{
					chart.dataProvider = graphData;
					chart.validateData();
				}
			},

			spotLightInit: function()
			{
				var self = this;
				var moreBtn = BX("tasks-effective-more");

				var spotlight = new BX.SpotLight({
					id: 'tasks_sl_effective_more',
					targetElement: moreBtn,
					content: self.option('text_sl_effective_more'),
					targetVertex: "middle-center",
					autoSave: true
				});
				spotlight.show();
			}
		}
	});
})();
