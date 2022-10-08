this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Report = this.BX.Crm.Report || {};
this.BX.Crm.Report.Tracking = this.BX.Crm.Report.Tracking || {};
(function (exports,sidepanel,ui_progressbar,ui_fonts_opensans,main_core,main_core_events,main_popup) {
	'use strict';

	var _templateObject;

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	var Report = /*#__PURE__*/function () {
	  function Report(options) {
	    babelHelpers.classCallCheck(this, Report);
	    babelHelpers.defineProperty(this, "ui", {
	      container: null,
	      loader: null,
	      loaderText: null,
	      loaderProgressBar: null,
	      loaderActive: false,
	      error: null,
	      errorText: null,
	      errorClose: null,
	      grid: null
	    });
	    babelHelpers.defineProperty(this, "loaded", false);
	    babelHelpers.defineProperty(this, "statusButtonClassName", 'crm-tracking-report-source-status-disabled');
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
	          var container = _this.createUiContainer(level);

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
	        json: _objectSpread({}, this.filter)
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
	      })["catch"](function (_ref3) {
	        var errors = _ref3.errors;

	        _this2.showError(errors[0]);
	      });
	    }
	  }, {
	    key: "changeStatus",
	    value: function changeStatus(id, status) {
	      var _this3 = this;

	      this.showLoader();
	      BX.ajax.runAction('crm.api.tracking.ad.report.changeStatus', {
	        json: {
	          id: id,
	          status: status
	        }
	      }).then(function () {
	        _this3.loadGrid();
	      })["catch"](function (_ref4) {
	        var errors = _ref4.errors;

	        _this3.showError(errors[0]);
	      });
	    }
	  }, {
	    key: "loadGrid",
	    value: function loadGrid() {
	      var _this4 = this;

	      BX.ajax.runAction('crm.api.tracking.ad.report.getGrid', {
	        data: _objectSpread({}, this.filter)
	      }).then(function (_ref5) {
	        var data = _ref5.data;
	        //container.innerHTML = data.html;
	        main_core_events.EventEmitter.subscribe(window, 'Grid::beforeRequest', _this4.onBeforeGridRequest.bind(_this4));
	        main_core.Runtime.html(_this4.getNode('grid'), data.html);

	        _this4.initActivators();

	        _this4.hideLoader();

	        _this4.loaded = true;

	        if (!_this4.filter.level) {
	          var popupOptions = {
	            content: main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_SETTINGS_HINT'),
	            zIndex: 5000,
	            maxWidth: 300,
	            offsetLeft: -315,
	            offsetTop: -30,
	            animation: 'fading',
	            darkMode: true,
	            bindElement: _this4.ui.hint
	          };
	          var popup = new main_popup.Popup(popupOptions);
	          popup.show();
	          setTimeout(function () {
	            return popup.destroy();
	          }, 10000);
	        }
	      });
	    }
	  }, {
	    key: "initActivators",
	    value: function initActivators() {
	      var _this5 = this;

	      this.getNodes('grid/activator').forEach(function (node) {
	        var options = JSON.parse(node.dataset.options);
	        var statusBtn = node.previousElementSibling;

	        if (statusBtn) {
	          options.enabled ? statusBtn.classList.remove(_this5.statusButtonClassName) : statusBtn.classList.add(_this5.statusButtonClassName);
	          main_core.Event.bind(statusBtn, 'click', function () {
	            var popup = new main_popup.Menu({
	              bindElement: statusBtn,
	              zIndex: 3010,
	              items: [{
	                text: main_core.Text.encode(main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_STATUS_ENABLED')),
	                onclick: function onclick() {
	                  _this5.changeStatus(options.parentId, true);

	                  popup.close();
	                }
	              }, {
	                text: main_core.Text.encode(main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_STATUS_PAUSE')),
	                onclick: function onclick() {
	                  _this5.changeStatus(options.parentId, false);

	                  popup.close();
	                }
	              }]
	            });
	            popup.show();
	          });
	        }

	        if (options.level === null || options.level === undefined) {
	          return;
	        }

	        main_core.Event.bind(node, 'click', function () {
	          new Report(_objectSpread(_objectSpread({}, _this5.filter), {}, {
	            level: options.level,
	            parentId: options.parentId,
	            gridId: _this5.filter.gridId + '-lvl' + options.level
	          }));
	        });
	      });
	      var selectorTitle = this.getNode('grid/selector/title');
	      var selector = this.getNode('grid/selector');

	      if (selector) {
	        var container = this.getNode('selector');

	        if (container.children.length > 0) {
	          selector.parentElement.removeChild(selector);
	          selectorTitle.parentElement.removeChild(selectorTitle);
	          return;
	        }

	        selector.dataset.role = '';
	        selectorTitle.dataset.role = '';
	        container.appendChild(selectorTitle);
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
	                  _this5.filter.parentId = item.parentId;
	                  _this5.filter.level = item.level;
	                  _this5.filter.sourceId = item.sourceId;

	                  _this5.build();
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
	    value: function createUiContainer(level) {
	      var _this6 = this;

	      var container = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-report-tracking-panel\">\n\t\t\t\t<div class=\"crm-report-tracking-panel-title\">\n\t\t\t\t\t<div class=\"crm-report-tracking-panel-title-name\">\n\t\t\t\t\t\t<div class=\"crm-report-tracking-panel-title-line\">\n\t\t\t\t\t\t\t<div data-role=\"title\"></div>\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div data-role=\"selector\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-report-tracking-panel-body\">\n\t\t\t\t\t<div data-role=\"loader\" class=\"crm-report-tracking-panel-loader\">\n\t\t\t\t\t\t<div data-role=\"loader/text\" class=\"crm-report-tracking-panel-loader-text\"></div>\n\t\t\t\t\t\t<div data-role=\"loader/bar\" class=\"crm-report-tracking-panel-loader-bar\">\n\t\t\t\t\t\t\t<div data-role=\"error\" class=\"ui-alert ui-alert-danger\" style=\"display: none;\">\n\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t\t\t\t\t<strong>", ":</strong>\n\t\t\t\t\t\t\t\t\t<span data-role=\"error/text\"></span>\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div style=\"text-align: center;\">\n\t\t\t\t\t\t\t\t<button \n\t\t\t\t\t\t\t\t\tdata-role=\"error/close\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-light-border\"\n\t\t\t\t\t\t\t\t\tstyle=\"display: none;\"\n\t\t\t\t\t\t\t\t>", "</button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div data-role=\"grid\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), level ? '' : '<div data-role="hint" class="ui-hint-icon crm-report-tracking-panel-hint"></div>', main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_ERROR_TITLE'), main_core.Loc.getMessage('CRM_REPORT_TRACKING_AD_REPORT_CLOSE'));
	      this.ui.container = container;
	      this.ui.title = this.getNode('title');
	      this.ui.hint = this.getNode('hint');
	      this.ui.loader = this.getNode('loader');
	      this.ui.loaderText = this.getNode('loader/text');
	      this.ui.error = this.getNode('error');
	      this.ui.errorText = this.getNode('error/text');
	      this.ui.errorClose = this.getNode('error/close');
	      this.ui.grid = this.getNode('grid');
	      var progressBar = new BX.UI.ProgressBar({
	        value: 0,
	        maxValue: 100,
	        statusType: 'none',
	        column: true
	      });

	      if (this.ui.hint) {
	        this.ui.hint.addEventListener('click', function () {
	          return BX.Helper.show("redirect=detail&code=12526974");
	        });
	      }

	      if (this.ui.errorClose) {
	        this.ui.errorClose.addEventListener('click', function () {
	          if (_this6.loaded) {
	            _this6.hideError();
	          } else {
	            BX.SidePanel.Instance.close();
	          }
	        });
	      }

	      this.getNode('loader/bar').insertBefore(progressBar.getContainer(), this.getNode('loader/bar').children[0]);
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
	      var _this7 = this;

	      if (!this.ui.loaderActive) {
	        return;
	      }

	      var progressBar = this.ui.loaderProgressBar;
	      var val = parseInt(progressBar.getValue()) + 1;

	      if (val <= 95) {
	        progressBar.update(val);
	      }

	      setTimeout(function () {
	        return _this7.animateLoader();
	      }, 100);
	    }
	  }, {
	    key: "showError",
	    value: function showError(error) {
	      this.ui.errorClose.style.display = '';
	      this.ui.error.style.display = '';
	      this.ui.errorText.textContent = error.message;
	      this.ui.loaderProgressBar.getContainer().style.display = 'none';
	    }
	  }, {
	    key: "hideError",
	    value: function hideError() {
	      this.ui.errorClose.style.display = 'none';
	      this.ui.error.style.display = 'none';
	      this.ui.errorText.textContent = '';
	      this.ui.loaderProgressBar.getContainer().style.display = '';
	      this.hideLoader();
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      var _this8 = this;

	      if (!this.ui.loaderActive) {
	        setTimeout(function () {
	          return _this8.animateLoader();
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
	      var _this9 = this;

	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.method = 'POST';

	      if (!eventArgs.url) {
	        var parameters = Object.keys(this.filter).forEach(function (key) {
	          return key + '=' + _this9.filter[key];
	        });
	        eventArgs.url = '/bitrix/services/main/ajax.php?action=crm.api.tracking.ad.grid.report.get&' + parameters;
	      }

	      eventArgs.data = _objectSpread({}, eventArgs.data);
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

}((this.BX.Crm.Report.Tracking.Ad = this.BX.Crm.Report.Tracking.Ad || {}),BX,BX.UI,BX,BX,BX.Event,BX.Main));
//# sourceMappingURL=report.bundle.js.map
