'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksReportEffectiveDetail != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksReportEffectiveDetail = BX.Tasks.Component.extend({
		sys: {
			code: 'effectiveDetail'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
				this.amchartInit();
				// create sub-instances through this.subInstance(), do some initialization, etc

				// do ajax call, like
				// this.callRemote('this.sampleCreateTask', {data: {TITLE: 'Sample Task'}}).then(function(result){ ... });
				// dont care about CSRF, SITE_ID and LANGUAGE_ID: it will be sent and checked automatically
			},

			bindEvents: function()
			{
				// do some permanent event bindings here, like i.e.
				/*
				this.bindControlPassCtx('some-div', 'click', this.showAddPopup);
				this.bindControlPassCtx('some-div', 'click', this.showActionPopup);
				this.bindControlPassCtx('some-div', 'click', this.showUnHideFieldPopup);
				this.bindDelegateControl('some-div', 'keypress', this.jamEnter, this.control('new-item-place'));
				*/
			},

			// add more methods, then call them like this.methodName()
			amchartInit: function()
			{
				var chart = AmCharts.makeChart(this.scope().id, {
					"theme": "light",
					"type": "serial",
					"autoMarginOffset": 20,
					"dataProvider": this.option('GRAPH_DATA'),
					"valueAxes": [{
						"id": "v1",
						"axisAlpha": 0.1
					}],
					"chartScrollbar": {
						"scrollbarHeight": 2,
						"backgroundAlpha": 0.1,
						"backgroundColor": "#868686",
						"selectedBackgroundColor": "#67b7dc",
						"selectedBackgroundAlpha": 1
					},
					"graphs": [{
						"useNegativeColorIfDown": true,
						"balloonText": "[[category]]<br><b>Ёффективность: [[value]]</b>",
						"bullet": "round",
						"bulletBorderAlpha": 1,
						"bulletBorderColor": "#FFFFFF",
						"hideBulletsCount": 50,
						"lineThickness": 2,
						"lineColor": "#fdd400",
						"negativeLineColor": "#67b7dc",
						"valueField": "KPI"
					}],
					"chartCursor": {
						"valueLineEnabled": true,
						"valueLineBalloonEnabled": true
					},
					"categoryField": "DATE",
					"categoryAxis": {
						"parseDates": true,
						"axisAlpha": 0,
						"minHorizontalGap": 60
					}
				});
			}
		}
	});

	// may be some sub-controllers here...

}).call(this);