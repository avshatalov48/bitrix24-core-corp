this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,amcharts4,am4themes_animated,ui_sidepanel_layout) {
	'use strict';

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"tasks-scrum-sprint-team-speed-chart\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var TeamSpeedChart = /*#__PURE__*/function () {
	  function TeamSpeedChart(params) {
	    babelHelpers.classCallCheck(this, TeamSpeedChart);
	    this.groupId = parseInt(params.groupId, 10);
	    /* eslint-disable */

	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.chart = null;
	    this.chartData = null;
	  }

	  babelHelpers.createClass(TeamSpeedChart, [{
	    key: "show",
	    value: function show() {
	      var _this = this;

	      this.sidePanelManager.open('tasks-scrum-sprint-team-speed-chart-side-panel', {
	        cacheable: false,
	        events: {
	          onLoad: this.onSidePanelLoad.bind(this),
	          onCloseComplete: this.onSidePanelAfterClose.bind(this)
	        },
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.team-speed-chart'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_TITLE'),
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
	        _this2.createChart(sidePanel.getContainer().querySelector('.tasks-scrum-sprint-team-speed-chart'), _this2.chartData);
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
	        main_core.ajax.runAction('bitrix:tasks.scrum.sprint.getTeamSpeedChartData', {
	          data: {
	            groupId: _this3.groupId
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
	      this.chart.scrollbarX = new window.am4core.Scrollbar();
	      this.chart.scrollbarX.parent = this.chart.bottomAxesContainer;
	      this.createAxises();
	      this.createColumn('plan', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
	      this.createColumn('done', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');
	      this.createLegend();
	    }
	  }, {
	    key: "createAxises",
	    value: function createAxises() {
	      var xAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
	      xAxis.dataFields.category = 'sprintName';
	      xAxis.renderer.grid.template.location = 0;
	      var label = xAxis.renderer.labels.template;
	      label.wrap = true;
	      label.maxWidth = 120;
	      var yAxis = this.chart.yAxes.push(new am4charts.ValueAxis());
	      yAxis.min = 0;
	    }
	  }, {
	    key: "createColumn",
	    value: function createColumn(valueY, name, color) {
	      var series = this.chart.series.push(new am4charts.ColumnSeries());
	      series.dataFields.valueY = valueY;
	      series.dataFields.categoryX = 'sprintName';
	      series.name = name;
	      series.stroke = window.am4core.color(color);
	      series.fill = window.am4core.color(color); // const bullet = series.bullets.push(new am4charts.LabelBullet())
	      // bullet.dy = 10;
	      // bullet.label.text = '{valueY}';
	      // bullet.label.fill = window.am4core.color('#ffffff');

	      return series;
	    }
	  }, {
	    key: "createLegend",
	    value: function createLegend() {
	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.paddingBottom = 20;
	      this.chart.legend.itemContainers.template.clickable = false;
	    }
	  }, {
	    key: "destroyChart",
	    value: function destroyChart() {
	      if (this.chart) {
	        this.chart.dispose();
	      }
	    }
	  }]);
	  return TeamSpeedChart;
	}();

	exports.TeamSpeedChart = TeamSpeedChart;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX,BX,BX.UI.SidePanel));
//# sourceMappingURL=team.speed.chart.bundle.js.map
