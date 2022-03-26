this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core) {
	'use strict';

	var BurnDownChart = /*#__PURE__*/function () {
	  function BurnDownChart() {
	    babelHelpers.classCallCheck(this, BurnDownChart);

	    /* eslint-disable */
	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.chart = null;
	  }

	  babelHelpers.createClass(BurnDownChart, [{
	    key: "render",
	    value: function render(chartDiv, data) {
	      var _this = this;

	      setTimeout(function () {
	        return _this.create(chartDiv, data);
	      }, 300);
	    }
	  }, {
	    key: "create",
	    value: function create(chartDiv, data) {
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
	      var _this2 = this;

	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.itemContainers.template.clickable = false;
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.itemContainers.template.events.on('over', function (event) {
	        _this2.processOver(event.target.dataItem.dataContext);
	      });
	      this.chart.legend.itemContainers.template.events.on('out', function () {
	        return _this2.processOut();
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
	  }]);
	  return BurnDownChart;
	}();

	exports.BurnDownChart = BurnDownChart;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX));
//# sourceMappingURL=script.js.map
