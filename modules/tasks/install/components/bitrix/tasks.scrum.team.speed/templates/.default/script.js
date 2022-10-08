this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_core) {
	'use strict';

	var Chart = /*#__PURE__*/function () {
	  function Chart(data) {
	    babelHelpers.classCallCheck(this, Chart);
	    this.data = data;
	    this.chart = null;
	    this.loader = null;
	  }

	  babelHelpers.createClass(Chart, [{
	    key: "renderTo",
	    value: function renderTo(chartDiv) {
	      var _this = this;

	      setTimeout(function () {
	        return _this.create(chartDiv);
	      }, 300);
	    }
	  }, {
	    key: "create",
	    value: function create(chartDiv) {
	      am4core.useTheme(am4themes_animated);
	      this.chart = am4core.create(chartDiv, am4charts.XYChart);
	      this.chart.data = this.data;
	      this.chart.paddingRight = 40;
	      this.chart.responsive.enabled = true;
	      this.createAxises();
	      this.createColumn('plan', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_PLAN_COLUMN'), '#2882b3');
	      this.createColumn('done', main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_DONE_COLUMN'), '#9c1f1f');
	      this.createLegend();

	      if (this.data.length === 0) {
	        this.showLoader(true);
	      }
	    }
	  }, {
	    key: "render",
	    value: function render(data) {
	      if (!this.chart) {
	        return;
	      }

	      this.data = data;
	      this.chart.data = this.data;

	      if (this.data.length > 0) {
	        this.removeLoader();
	      } else {
	        this.showLoader(true);
	      }
	    }
	  }, {
	    key: "createAxises",
	    value: function createAxises() {
	      var xAxis = this.chart.xAxes.push(new am4charts.CategoryAxis());
	      xAxis.dataFields.category = 'sprintName';
	      xAxis.renderer.grid.template.location = 0;
	      xAxis.renderer.labels.template.adapter.add('textOutput', function (text) {
	        return main_core.Type.isNil(text) ? text : text.replace(/ \(.*/, '');
	      });
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
	      series.fill = am4core.color(color);
	      series.columns.template.tooltipText = '{name}: [bold]{valueY}[/]';
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
	    value: function showLoader() {
	      var notData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.removeLoader();
	      this.loader = this.chart.tooltipContainer.createChild(am4core.Container);
	      this.loader.background.fill = am4core.color('#fff');
	      this.loader.background.fillOpacity = 0.8;
	      this.loader.width = am4core.percent(100);
	      this.loader.height = am4core.percent(100);

	      if (notData) {
	        var loaderLabel = this.loader.createChild(am4core.Label);
	        loaderLabel.text = main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_NOT_DATA_LABEL');
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
	  }]);
	  return Chart;
	}();

	var _templateObject;
	var Stats = /*#__PURE__*/function () {
	  function Stats(data) {
	    babelHelpers.classCallCheck(this, Stats);
	    this.data = data;
	    this.node = null;
	  }

	  babelHelpers.createClass(Stats, [{
	    key: "renderTo",
	    value: function renderTo(rootNode) {
	      this.node = this.build();
	      main_core.Dom.append(this.node, rootNode);
	    }
	  }, {
	    key: "render",
	    value: function render(data) {
	      if (!main_core.Type.isUndefined(data)) {
	        this.data = data;
	      }

	      if (this.node) {
	        this.sync(this.build(), this.node);
	      } else {
	        this.node = this.build();
	      }

	      return this.node;
	    }
	  }, {
	    key: "build",
	    value: function build() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-sprint-team-speed-stats-container\">\n\t\t\t\t<div class=\"tasks-scrum-sprint-team-speed-stats-row\">\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-team-speed-stats-row\">\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-sprint-team-speed-stats-row\">\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t<div>", "</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_AVERAGE_LABEL'), main_core.Text.encode(this.data.average), main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_MAX_LABEL'), main_core.Text.encode(this.data.maximum), main_core.Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_MIN_LABEL'), main_core.Text.encode(this.data.minimum));
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      if (this.node) {
	        main_core.Dom.addClass(this.node, '--loader');
	      }
	    } // todo move it to Dom library

	  }, {
	    key: "sync",
	    value: function sync(virtualNode, realNode) {
	      if (virtualNode.attributes) {
	        Array.from(virtualNode.attributes).forEach(function (attr) {
	          if (realNode.getAttribute(attr.name) !== attr.value) {
	            realNode.setAttribute(attr.name, attr.value);
	          }
	        });
	      }

	      if (virtualNode.nodeValue !== realNode.nodeValue) {
	        realNode.nodeValue = virtualNode.nodeValue;
	      } // Sync child nodes


	      var virtualChildren = virtualNode.childNodes;
	      var realChildren = realNode.childNodes;

	      for (var k = 0; k < virtualChildren.length || k < realChildren.length; k++) {
	        var virtual = virtualChildren[k];
	        var real = realChildren[k]; // Remove

	        if (virtual === undefined && real !== undefined) {
	          realNode.remove(real);
	        } // Update


	        if (virtual !== undefined && real !== undefined && virtual.tagName === real.tagName) {
	          this.sync(virtual, real);
	        } // Replace


	        if (virtual !== undefined && real !== undefined && virtual.tagName !== real.tagName) {
	          var newReal = this.createRealNodeByVirtual(virtual);
	          this.sync(virtual, newReal);
	          main_core.Dom.replace(real, newReal);
	        } // Add


	        if (virtual !== undefined && real === undefined) {
	          var _newReal = this.createRealNodeByVirtual(virtual);

	          this.sync(virtual, _newReal);
	          main_core.Dom.append(_newReal, realNode);
	        }
	      }
	    } // todo move it to Dom library

	  }, {
	    key: "createRealNodeByVirtual",
	    value: function createRealNodeByVirtual(virtual) {
	      if (virtual.nodeType === Node.TEXT_NODE) {
	        return document.createTextNode('');
	      }

	      return document.createElement(virtual.tagName);
	    }
	  }]);
	  return Stats;
	}();

	var TeamSpeed = /*#__PURE__*/function () {
	  function TeamSpeed(params) {
	    babelHelpers.classCallCheck(this, TeamSpeed);
	    this.filterId = params.filterId;
	    this.signedParameters = params.signedParameters;
	    this.chart = new Chart(params.chartData);
	    this.stats = new Stats(params.statsData);
	    /* eslint-disable */

	    this.filterManager = BX.Main.filterManager.getById(this.filterId);
	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.bindEvents();
	  }

	  babelHelpers.createClass(TeamSpeed, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	    }
	  }, {
	    key: "renderTo",
	    value: function renderTo(chartRoot, statsRoot) {
	      this.chart.renderTo(chartRoot);
	      this.stats.renderTo(statsRoot);
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply(event) {
	      var _this = this;

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

	      this.chart.showLoader();
	      this.stats.showLoader();
	      main_core.ajax.runComponentAction('bitrix:tasks.scrum.team.speed', 'applyFilter', {
	        mode: 'class',
	        signedParameters: this.signedParameters,
	        data: {}
	      }).then(function (response) {
	        var chartData = response.data.chartData;
	        var statsData = response.data.statsData;

	        _this.chart.render(chartData);

	        _this.stats.render(statsData);
	      });
	    }
	  }]);
	  return TeamSpeed;
	}();

	exports.TeamSpeed = TeamSpeed;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX));
//# sourceMappingURL=script.js.map
