this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,main_core_events,ui_entitySelector,ui_buttons) {
	'use strict';

	var BurnDownChart = /*#__PURE__*/function () {
	  function BurnDownChart(params) {
	    babelHelpers.classCallCheck(this, BurnDownChart);
	    this.groupId = main_core.Type.isNumber(params.groupId) ? parseInt(params.groupId, 10) : 0;
	    this.selectorContainer = params.selectorContainer;
	    this.infoContainer = params.infoContainer;
	    this.currentSprint = params.currentSprint;
	    /* eslint-disable */

	    this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    this.chart = null;
	  }

	  babelHelpers.createClass(BurnDownChart, [{
	    key: "render",
	    value: function render(chartDiv, data) {
	      var _this = this;

	      this.renderSelectorTo(this.selectorContainer);
	      setTimeout(function () {
	        return _this.create(chartDiv, data);
	      }, 300);
	    }
	  }, {
	    key: "renderSelectorTo",
	    value: function renderSelectorTo(selectorContainer) {
	      var _this2 = this;

	      if (!main_core.Type.isElementNode(selectorContainer)) {
	        return;
	      }

	      this.selectorButton = new ui_buttons.Button({
	        text: this.currentSprint.dateStartFormatted + ' - ' + this.currentSprint.dateEndFormatted,
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        dropdown: true,
	        className: 'ui-btn-themes',
	        onclick: function onclick() {
	          var dialog = _this2.createSelectorDialog(_this2.selectorButton.getContainer(), _this2.currentSprint);

	          dialog.show();
	        }
	      });
	      this.selectorButton.renderTo(selectorContainer);
	    }
	  }, {
	    key: "createSelectorDialog",
	    value: function createSelectorDialog(targetNode, currentSprint) {
	      var _this3 = this;

	      return new ui_entitySelector.Dialog({
	        targetNode: targetNode,
	        width: 400,
	        height: 300,
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        compactView: true,
	        showAvatars: false,
	        cacheable: false,
	        preselectedItems: [['sprint-selector', currentSprint.id]],
	        entities: [{
	          id: 'sprint-selector',
	          options: {
	            groupId: this.groupId
	          },
	          dynamicLoad: true,
	          dynamicSearch: true
	        }],
	        events: {
	          'Item:onSelect': function ItemOnSelect(event) {
	            var _event$getData = event.getData(),
	                selectedItem = _event$getData.item;

	            _this3.selectorButton.setText(selectedItem.customData.get('label'));

	            _this3.changeChart(selectedItem.getId());
	          }
	        }
	      });
	    }
	  }, {
	    key: "changeChart",
	    value: function changeChart(sprintId) {
	      var _this4 = this;

	      main_core.ajax.runComponentAction('bitrix:tasks.scrum.burn.down', 'changeChart', {
	        mode: 'class',
	        data: {
	          groupId: this.groupId,
	          sprintId: sprintId
	        }
	      }).then(function (response) {
	        _this4.chart.data = response.data.chart;
	        _this4.currentSprint = response.data.sprint;
	        _this4.infoContainer.querySelector('.tasks-scrum-sprint-burn-down-info-name').textContent = main_core.Text.encode(_this4.currentSprint.name);
	      });
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
	      var _this5 = this;

	      this.chart.legend = new am4charts.Legend();
	      this.chart.legend.itemContainers.template.clickable = false;
	      this.chart.legend.position = 'bottom';
	      this.chart.legend.itemContainers.template.events.on('over', function (event) {
	        _this5.processOver(event.target.dataItem.dataContext);
	      });
	      this.chart.legend.itemContainers.template.events.on('out', function () {
	        return _this5.processOut();
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

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Event,BX.UI.EntitySelector,BX.UI));
//# sourceMappingURL=script.js.map
