this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,amcharts4,am4themes_animated,ui_sidepanel_layout) {
	'use strict';

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-sprint-burn-down-chart\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var BurnDownChart = /*#__PURE__*/function () {
	  function BurnDownChart(params) {
	    babelHelpers.classCallCheck(this, BurnDownChart);
	    this.groupId = parseInt(params.groupId, 10);
	    this.sprintId = parseInt(params.sprintId, 10);
	    /* eslint-disable */

	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.chart = null;
	    this.chartData = null;
	  }

	  babelHelpers.createClass(BurnDownChart, [{
	    key: "show",
	    value: function show() {
	      var _this = this;

	      this.sidePanelManager.open('tasks-scrum-sprint-burn-down-chart-side-panel', {
	        cacheable: false,
	        events: {
	          onLoad: this.onSidePanelLoad.bind(this),
	          onCloseComplete: this.onSidePanelAfterClose.bind(this)
	        },
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.burn-down-chart'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_TITLE'),
	            content: _this.createContent.bind(_this),
	            design: {
	              section: false
	            },
	            buttons: []
	          });
	        }
	      });
	    }
	  }, {
	    key: "onSidePanelLoad",
	    value: function onSidePanelLoad(event) {
	      var _this2 = this;

	      var sidePanel = event.getSlider();
	      setTimeout(function () {
	        _this2.createChart(sidePanel.getContainer().querySelector('.tasks-scrum-sprint-burn-down-chart'), _this2.chartData);
	      }, 300);
	    }
	  }, {
	    key: "onSidePanelAfterClose",
	    value: function onSidePanelAfterClose() {
	      this.destroyChart();
	    }
	  }, {
	    key: "createContent",
	    value: function createContent() {
	      var _this3 = this;

	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.sprint.getBurnDownChartData', {
	          data: {
	            groupId: _this3.groupId,
	            sprintId: _this3.sprintId
	          }
	        }).then(function (response) {
	          _this3.chartData = response.data;
	          resolve(_this3.render());
	        });
	      });
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject());
	    }
	  }, {
	    key: "createChart",
	    value: function createChart(chartDiv, data) {
	      window.am4core.useTheme(am4themes_animated);
	      this.chart = window.am4core.create(chartDiv, am4charts.XYChart);
	      this.chart.data = data;
	      this.chart.paddingRight = 40;
	      this.createAxises();
	      this.createIdealLine();
	      this.createRemainLine();
	      this.createLegend();
	    }
	  }, {
	    key: "createAxises",
	    value: function createAxises() {
	      var categoryAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
	      categoryAxis.renderer.grid.template.location = 0;
	      categoryAxis.dataFields.category = 'day';
	      categoryAxis.renderer.minGridDistance = 60;
	      var valueAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
	      valueAxis.min = -0.1;
	    }
	  }, {
	    key: "createIdealLine",
	    value: function createIdealLine() {
	      var lineSeries = this.chart.series.push(new am4charts.LineSeries());
	      lineSeries.name = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_IDEAL_BURN_DOWN_CHART_LINE_LABEL');
	      lineSeries.stroke = window.am4core.color('#2882b3');
	      lineSeries.strokeWidth = 2;
	      lineSeries.dataFields.categoryX = 'day';
	      lineSeries.dataFields.valueY = 'idealValue';
	      var circleColor = '#2882b3';
	      var circleBullet = new am4charts.CircleBullet();
	      circleBullet.circle.radius = 4;
	      circleBullet.circle.fill = window.am4core.color(circleColor);
	      circleBullet.circle.stroke = window.am4core.color(circleColor);
	      lineSeries.bullets.push(circleBullet);
	      var segment = lineSeries.segments.template;
	      var hoverState = segment.states.create('hover');
	      hoverState.properties.strokeWidth = 4;
	    }
	  }, {
	    key: "createRemainLine",
	    value: function createRemainLine() {
	      var lineSeries = this.chart.series.push(new am4charts.LineSeries());
	      lineSeries.name = main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_REMAIN_BURN_DOWN_CHART_LINE_LABEL');
	      lineSeries.stroke = window.am4core.color('#9c1f1f');
	      lineSeries.strokeWidth = 2;
	      lineSeries.dataFields.categoryX = 'day';
	      lineSeries.dataFields.valueY = 'remainValue';
	      var circleColor = '#9c1f1f';
	      var circleBullet = new am4charts.CircleBullet();
	      circleBullet.circle.radius = 4;
	      circleBullet.circle.fill = window.am4core.color(circleColor);
	      circleBullet.circle.stroke = window.am4core.color(circleColor);
	      lineSeries.bullets.push(circleBullet);
	      var segment = lineSeries.segments.template;
	      var hoverState = segment.states.create('hover');
	      hoverState.properties.strokeWidth = 4;
	    }
	  }, {
	    key: "createLegend",
	    value: function createLegend() {
	      var _this4 = this;

	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.itemContainers.template.clickable = false;
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.itemContainers.template.events.on('over', function (event) {
	        _this4.processOver(event.target.dataItem.dataContext);
	      });
	      this.chart.legend.itemContainers.template.events.on('out', function () {
	        return _this4.processOut();
	      });
	    }
	  }, {
	    key: "processOver",
	    value: function processOver(hoveredLine) {
	      hoveredLine.toFront();
	      hoveredLine.segments.each(function (segment) {
	        return segment.setState('hover');
	      });
	    }
	  }, {
	    key: "processOut",
	    value: function processOut() {
	      this.chart.series.each(function (series) {
	        series.segments.each(function (segment) {
	          return segment.setState('default');
	        });
	        series.bulletsContainer.setState('default');
	      });
	    }
	  }, {
	    key: "destroyChart",
	    value: function destroyChart() {
	      if (this.chart) {
	        this.chart.dispose();
	      }
	    }
	  }]);
	  return BurnDownChart;
	}();

	exports.BurnDownChart = BurnDownChart;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX,BX,BX.UI.SidePanel));
//# sourceMappingURL=burn.down.chart.bundle.js.map
