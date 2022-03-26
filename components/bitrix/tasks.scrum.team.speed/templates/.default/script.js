this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var TeamSpeedChart = /*#__PURE__*/function () {
	  function TeamSpeedChart(params) {
	    babelHelpers.classCallCheck(this, TeamSpeedChart);
	    this.filterId = params.filterId;
	    this.signedParameters = params.signedParameters;
	    /* eslint-disable */

	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.chart = null;
	    this.chartData = null;
	    this.loader = null; //this.initUiFilterManager(); // todo return later
	    //this.bindEvents(); // todo return later
	  }

	  babelHelpers.createClass(TeamSpeedChart, [{
	    key: "initUiFilterManager",
	    value: function initUiFilterManager() {
	      /* eslint-disable */
	      this.filterManager = BX.Main.filterManager.getById(this.filterId);
	      /* eslint-enable */
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	    }
	  }, {
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
	      am4core.useTheme(am4themes_animated);
	      this.chart = am4core.create(chartDiv, am4charts.XYChart);
	      this.chart.data = data;
	      this.chart.paddingRight = 40;
	      this.chart.responsive.enabled = true;
	      this.createAxises();
	      this.createColumn('plan', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
	      this.createColumn('done', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');
	      this.createLegend();

	      if (data.length === 0) {
	        this.showLoader(main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL'));
	      }
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
	      series.stroke = am4core.color(color);
	      series.fill = am4core.color(color); // const bullet = series.bullets.push(new am4charts.LabelBullet())
	      // bullet.dy = 10;
	      // bullet.label.text = '{valueY}';
	      // bullet.label.fill = am4core.color('#ffffff');

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
	    key: "showLoader",
	    value: function showLoader(labelMessage) {
	      this.removeLoader();
	      this.loader = this.chart.tooltipContainer.createChild(am4core.Container);
	      this.loader.background.fill = am4core.color('#fff');
	      this.loader.background.fillOpacity = 0.8;
	      this.loader.width = am4core.percent(100);
	      this.loader.height = am4core.percent(100);

	      if (!main_core.Type.isUndefined(labelMessage)) {
	        var loaderLabel = this.loader.createChild(am4core.Label);
	        loaderLabel.text = labelMessage;
	        loaderLabel.align = 'center';
	        loaderLabel.valign = 'middle';
	        loaderLabel.fontSize = 20;
	      }
	    }
	  }, {
	    key: "removeLoader",
	    value: function removeLoader() {
	      if (this.loader !== null) {
	        this.loader.dispose();
	      }
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply(event) {
	      var _this2 = this;

	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 5),
	          filterId = _event$getCompatData2[0],
	          values = _event$getCompatData2[1],
	          filterInstance = _event$getCompatData2[2],
	          promise = _event$getCompatData2[3],
	          params = _event$getCompatData2[4];

	      if (this.filterId !== filterId) {
	        return;
	      }

	      if (this.chart) {
	        this.showLoader();
	      }

	      main_core.ajax.runComponentAction('bitrix:tasks.scrum.team.speed', 'applyFilter', {
	        mode: 'class',
	        signedParameters: this.signedParameters,
	        data: {}
	      }).then(function (response) {
	        if (_this2.chart) {
	          var data = response.data;
	          _this2.chart.data = data;

	          if (data.length > 0) {
	            _this2.removeLoader();
	          } else {
	            _this2.showLoader(main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL'));
	          }
	        }
	      });
	    }
	  }]);
	  return TeamSpeedChart;
	}();

	exports.TeamSpeedChart = TeamSpeedChart;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map
