this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Report = this.BX.Crm.Report || {};
this.BX.Crm.Report.Tracking = this.BX.Crm.Report.Tracking || {};
(function (exports,sidepanel,ui_progressbar,main_core,main_core_events,main_popup) {
	'use strict';

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-report-tracking-panel\">\n\t\t\t\t<div class=\"crm-report-tracking-panel-title\">\n\t\t\t\t\t<div class=\"crm-report-tracking-panel-title-name\">\n\t\t\t\t\t\t<div data-role=\"title\"></div>\n\t\t\t\t\t\t<div data-role=\"selector\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-report-tracking-panel-body\">\n\t\t\t\t\t<div data-role=\"loader\" class=\"crm-report-tracking-panel-loader\">\n\t\t\t\t\t\t<div data-role=\"loader/text\" class=\"crm-report-tracking-panel-loader-text\"></div>\n\t\t\t\t\t\t<div data-role=\"loader/bar\" class=\"crm-report-tracking-panel-loader-bar\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div data-role=\"grid\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Report =
	/*#__PURE__*/
	function () {
	  function Report(options) {
	    babelHelpers.classCallCheck(this, Report);
	    babelHelpers.defineProperty(this, "ui", {
	      container: null,
	      loader: null,
	      loaderText: null,
	      loaderProgressBar: null,
	      loaderActive: false,
	      grid: null
	    });
	    this.load(options);
	  }

	  babelHelpers.createClass(Report, [{
	    key: "load",
	    value: function load(_ref) {
	      var _this = this;

	      var sourceId = _ref.sourceId,
	          from = _ref.from,
	          to = _ref.to,
	          _ref$parentId = _ref.parentId,
	          parentId = _ref$parentId === void 0 ? 0 : _ref$parentId,
	          _ref$level = _ref.level,
	          level = _ref$level === void 0 ? 0 : _ref$level,
	          gridId = _ref.gridId;
	      this.gridId = gridId = gridId || "crm-report-tracking-ad-l".concat(level);
	      this.filter = {
	        sourceId: sourceId,
	        from: from,
	        to: to,
	        parentId: parentId,
	        level: level,
	        gridId: gridId
	      };
	      BX.SidePanel.Instance.open('crm:api.tracking.ad.report' + "-".concat(sourceId, "-").concat(level), {
	        cacheable: false,
	        contentCallback: function contentCallback() {
	          var container = _this.createUiContainer();

	          _this.build();

	          return container;
	        }
	      });
	    }
	  }, {
	    key: "build",
	    value: function build() {
	      var _this2 = this;

	      this.showLoader();

	      if (this.filter.level) {
	        this.loadGrid();
	        return;
	      }

	      BX.ajax.runAction('crm.api.tracking.ad.report.build', {
	        json: babelHelpers.objectSpread({}, this.filter)
	      }).then(function (_ref2) {
	        var data = _ref2.data;

	        if (data.label) {
	          _this2.setLoaderText(data.label);
	        }

	        if (data.complete) {
	          _this2.loadGrid();
	        } else {
	          _this2.build();
	        }
	      });
	    }
	  }, {
	    key: "loadGrid",
	    value: function loadGrid() {
	      var _this3 = this;

	      BX.ajax.runAction('crm.api.tracking.ad.report.getGrid', {
	        data: babelHelpers.objectSpread({}, this.filter)
	      }).then(function (_ref3) {
	        var data = _ref3.data;
	        //container.innerHTML = data.html;
	        main_core_events.EventEmitter.subscribe(window, 'Grid::beforeRequest', _this3.onBeforeGridRequest.bind(_this3));
	        main_core.Runtime.html(_this3.getNode('grid'), data.html);

	        _this3.initActivators();

	        _this3.hideLoader();
	      });
	    }
	  }, {
	    key: "initActivators",
	    value: function initActivators() {
	      var _this4 = this;

	      this.getNodes('grid/activator').forEach(function (node) {
	        var options = JSON.parse(node.dataset.options);
	        main_core.Event.bind(node, 'click', function () {
	          new Report(babelHelpers.objectSpread({}, _this4.filter, {
	            level: options.level,
	            parentId: options.parentId,
	            gridId: _this4.filter.gridId + '-lvl' + options.level
	          }));
	        });
	      });
	      var selector = this.getNode('grid/selector');

	      if (selector) {
	        var container = this.getNode('selector');

	        if (container.children.length > 0) {
	          selector.parentElement.removeChild(selector);
	          return;
	        }

	        selector.dataset.role = '';
	        container.appendChild(selector);
	        main_core.Event.bind(selector, 'click', function () {
	          var options = JSON.parse(selector.dataset.options);
	          var popup = new main_popup.Menu({
	            bindElement: selector,
	            zIndex: 3010,
	            items: options.items.map(function (item) {
	              return {
	                text: main_core.Text.encode(item.title),
	                onclick: function onclick() {
	                  popup.close();
	                  selector.textContent = item.title;
	                  _this4.filter.parentId = item.parentId;
	                  _this4.filter.level = item.level;
	                  _this4.filter.sourceId = item.sourceId;

	                  _this4.build();
	                }
	              };
	            })
	          });
	          popup.show();
	        });
	      }
	    }
	  }, {
	    key: "createUiContainer",
	    value: function createUiContainer() {
	      var container = main_core.Tag.render(_templateObject());
	      this.ui.container = container;
	      this.ui.title = this.getNode('title');
	      this.ui.loader = this.getNode('loader');
	      this.ui.loaderText = this.getNode('loader/text');
	      this.ui.grid = this.getNode('grid');
	      var progressBar = new BX.UI.ProgressBar({
	        value: 0,
	        maxValue: 100,
	        statusType: 'none',
	        column: true
	      });
	      this.getNode('loader/bar').appendChild(progressBar.getContainer());
	      this.ui.loaderProgressBar = progressBar;
	      this.setLoaderText(main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_BUILD'));
	      this.setTitle(main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_TITLE_' + this.filter.level));
	      return container;
	    }
	  }, {
	    key: "setLoaderText",
	    value: function setLoaderText(text) {
	      this.ui.loaderProgressBar.setValue(0);
	      this.ui.loaderProgressBar.setTextBefore(text);
	    }
	  }, {
	    key: "animateLoader",
	    value: function animateLoader() {
	      var _this5 = this;

	      if (!this.ui.loaderActive) {
	        return;
	      }

	      var progressBar = this.ui.loaderProgressBar;
	      var val = parseInt(progressBar.getValue()) + 1;

	      if (val <= 95) {
	        progressBar.update(val);
	      }

	      setTimeout(function () {
	        return _this5.animateLoader();
	      }, 100);
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      var _this6 = this;

	      if (!this.ui.loaderActive) {
	        setTimeout(function () {
	          return _this6.animateLoader();
	        }, 100);
	      }

	      this.ui.loaderActive = true;
	      this.ui.loader.style.display = '';
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      var progressBar = this.ui.loaderProgressBar;

	      if (progressBar) {
	        progressBar.update(0);
	      }

	      this.ui.loaderActive = false;
	      this.ui.loader.style.display = 'none';
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      return this.ui.title.textContent = title;
	    }
	  }, {
	    key: "getNode",
	    value: function getNode(role) {
	      return this.ui.container.querySelector("[data-role=\"".concat(role, "\"]"));
	    }
	  }, {
	    key: "getNodes",
	    value: function getNodes(role) {
	      return Array.from(this.ui.container.querySelectorAll("[data-role=\"".concat(role, "\"]")));
	    }
	  }, {
	    key: "onBeforeGridRequest",
	    value: function onBeforeGridRequest(grid, eventArgs) {
	      var _this7 = this;

	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.method = 'POST';

	      if (!eventArgs.url) {
	        var parameters = Object.keys(this.filter).forEach(function (key) {
	          return key + '=' + _this7.filter[key];
	        });
	        eventArgs.url = '/bitrix/services/main/ajax.php?action=crm.api.tracking.ad.grid.report.get&' + parameters;
	      }

	      eventArgs.data = babelHelpers.objectSpread({}, eventArgs.data);
	    }
	  }], [{
	    key: "open",
	    value: function open(options) {
	      if (window === top) {
	        return Promise.resolve(new Report(options));
	      }

	      return top.BX.Runtime.loadExtension('crm.report.tracking.ad.report').then(function () {
	        return new top.BX.Crm.Report.Tracking.Ad.Report(options);
	      });
	    }
	  }]);
	  return Report;
	}();

	exports.Report = Report;

}((this.BX.Crm.Report.Tracking.Ad = this.BX.Crm.Report.Tracking.Ad || {}),BX,BX,BX,BX.Event,BX.Main));
//# sourceMappingURL=report.bundle.js.map
