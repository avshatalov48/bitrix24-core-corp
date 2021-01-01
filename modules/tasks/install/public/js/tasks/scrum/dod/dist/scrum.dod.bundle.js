this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_core,ui_dialogs_messagebox) {
	'use strict';

	var SidePanel = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SidePanel, _EventEmitter);

	  function SidePanel() {
	    var _this;

	    babelHelpers.classCallCheck(this, SidePanel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SidePanel).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.DodSidePanel');
	    /* eslint-disable */


	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.BX = window.top.BX;

	    _this.bindEvents();

	    return _this;
	  }

	  babelHelpers.createClass(SidePanel, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      /* eslint-disable */
	      this.BX.addCustomEvent(window.top, 'SidePanel.Slider:onLoad', function (event) {
	        var sidePanel = event.getSlider();
	        sidePanel.setCacheable(false);

	        _this2.emit('onLoadSidePanel', sidePanel);
	      });
	      this.BX.addCustomEvent(window.top, 'SidePanel.Slider:onClose', function (event) {
	        var sidePanel = event.getSlider();

	        _this2.emit('onCloseSidePanel', sidePanel);
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "isPreviousSidePanelExist",
	    value: function isPreviousSidePanelExist(currentSidePanel) {
	      return Boolean(this.sidePanelManager.getPreviousSlider(currentSidePanel));
	    }
	  }, {
	    key: "reloadTopSidePanel",
	    value: function reloadTopSidePanel() {
	      this.sidePanelManager.getTopSlider().reload();
	    }
	  }, {
	    key: "closeTopSidePanel",
	    value: function closeTopSidePanel() {
	      this.sidePanelManager.getTopSlider().close();
	    }
	  }, {
	    key: "reloadPreviousSidePanel",
	    value: function reloadPreviousSidePanel(currentSidePanel) {
	      var previousSidePanel = this.sidePanelManager.getPreviousSlider(currentSidePanel);
	      previousSidePanel.reload();
	    }
	  }, {
	    key: "openSidePanelByUrl",
	    value: function openSidePanelByUrl(url) {
	      this.sidePanelManager.open(url);
	    }
	  }, {
	    key: "openSidePanel",
	    value: function openSidePanel(id, options) {
	      this.sidePanelManager.open(id, options);
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	    this.BX = window.top.BX;
	  }

	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(action) {
	      var _this = this;

	      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      return new Promise(function (resolve, reject) {
	        _this.BX.ajax.runAction('bitrix:tasks.scrum.service.definitionOfDoneService.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "getListComponent",
	    value: function getListComponent(data) {
	      return this.sendRequest('getItemComponent', data);
	    }
	  }, {
	    key: "getListOptions",
	    value: function getListOptions(data) {
	      return this.sendRequest('getListOptions', data);
	    }
	  }, {
	    key: "getListButtons",
	    value: function getListButtons() {
	      return this.sendRequest('getTaskCompleteButtons');
	    }
	  }, {
	    key: "saveList",
	    value: function saveList(data) {
	      return this.sendRequest('saveList', data);
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        console.log(response);
	        return;
	      }

	      if (response.errors.length) {
	        var firstError = response.errors.shift();

	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-alert ui-alert-danger\">\n\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum-project-side-panel\">\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-header\">\n\t\t\t\t\t<span class=\"tasks-scrum-project-side-panel-header-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum-project-dod-error\"></div>\n\t\t\t\t<div class=\"tasks-scrum-project-dod-list\"></div>\n\t\t\t\t<div class=\"tasks-scrum-project-side-panel-buttons\"></div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var ScrumDod = /*#__PURE__*/function () {
	  function ScrumDod(params) {
	    babelHelpers.classCallCheck(this, ScrumDod);
	    this.groupId = parseInt(params.groupId, 10);
	    this.sidePanel = new SidePanel();
	    this.requestSender = new RequestSender();
	  }

	  babelHelpers.createClass(ScrumDod, [{
	    key: "showList",
	    value: function showList(taskId) {
	      var _this = this;

	      return this.getListOptions().then(function (data) {
	        if (_this.isRequiredToggle(data)) {
	          _this.sidePanelId = 'tasks-scrum-dod-' + main_core.Text.getRandom();
	          _this.taskId = taskId;

	          _this.sidePanel.subscribeOnce('onLoadSidePanel', _this.onLoadList.bind(_this));

	          _this.sidePanel.openSidePanel(_this.sidePanelId, {
	            contentCallback: function contentCallback() {
	              return new Promise(function (resolve, reject) {
	                resolve(_this.buildList());
	              });
	            },
	            zIndex: 1000
	          });

	          return new Promise(function (resolve, reject) {
	            _this.resolver = resolve;
	            _this.rejecter = reject;
	          });
	        } else {
	          return new Promise(function (resolve) {
	            resolve();
	          });
	        }
	      });
	    }
	  }, {
	    key: "buildList",
	    value: function buildList() {
	      return main_core.Tag.render(_templateObject(), main_core.Loc.getMessage('TASKS_SCRUM_DOD_HEADER'));
	    }
	  }, {
	    key: "onLoadList",
	    value: function onLoadList(baseEvent) {
	      var _this2 = this;

	      var sidePanel = baseEvent.getData();
	      this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');
	      this.getListComponent().then(function (data) {
	        var listContainer = _this2.form.querySelector('.tasks-scrum-project-dod-list');

	        main_core.Runtime.html(listContainer, data.html);
	      }).then(function () {
	        _this2.getListButtons().then(function (response) {
	          var buttonsContainer = _this2.form.querySelector('.tasks-scrum-project-side-panel-buttons');

	          main_core.Runtime.html(buttonsContainer, response.html).then(function () {
	            main_core.Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', _this2.onCompleteClick.bind(_this2, sidePanel));
	            main_core.Event.bind(buttonsContainer.querySelector('[name=cancel]'), 'click', _this2.onCancelClick.bind(_this2, sidePanel));
	          });
	        });
	      });
	    }
	  }, {
	    key: "getListComponent",
	    value: function getListComponent() {
	      var _this3 = this;

	      return new Promise(function (resolve, reject) {
	        _this3.requestSender.getListComponent({
	          groupId: _this3.groupId,
	          taskId: _this3.taskId
	        }).then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "getListOptions",
	    value: function getListOptions() {
	      var _this4 = this;

	      return new Promise(function (resolve, reject) {
	        _this4.requestSender.getListOptions({
	          groupId: _this4.groupId
	        }).then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "getListButtons",
	    value: function getListButtons() {
	      var _this5 = this;

	      return new Promise(function (resolve, reject) {
	        _this5.requestSender.getListButtons().then(function (response) {
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "saveList",
	    value: function saveList() {
	      var _this6 = this;

	      return new Promise(function (resolve, reject) {
	        _this6.requestSender.saveList(_this6.getRequestDataForSaveList()).then(function (response) {
	          resolve(response.data);
	        }).catch(function (response) {
	          reject(response);
	        });
	      });
	    }
	  }, {
	    key: "getRequestDataForSaveList",
	    value: function getRequestDataForSaveList() {
	      var requestData = {};
	      requestData.taskId = this.taskId;
	      requestData.items = this.getListItems();
	      return requestData;
	    }
	  }, {
	    key: "getListItems",
	    value: function getListItems() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return [];
	      }

	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
	      return treeStructure.getRequestData();
	      /* eslint-enable */
	    }
	  }, {
	    key: "onCompleteClick",
	    value: function onCompleteClick(sidePanel) {
	      var _this7 = this;

	      if (this.isAllToggled()) {
	        this.saveList().then(function (response) {
	          sidePanel.close();

	          _this7.resolver();
	        });
	      } else {
	        this.removeClockIconFromButton();
	        this.showError(this.getErrorContainer());
	      }
	    }
	  }, {
	    key: "onCancelClick",
	    value: function onCancelClick(sidePanel) {
	      sidePanel.close();
	    }
	  }, {
	    key: "showError",
	    value: function showError(node) {
	      main_core.Dom.clean(this.form.querySelector('.tasks-scrum-project-dod-error'));
	      main_core.Dom.append(node, this.form.querySelector('.tasks-scrum-project-dod-error'));
	      this.form.querySelector('.tasks-scrum-project-side-panel-header').scrollIntoView(true);
	    }
	  }, {
	    key: "getErrorContainer",
	    value: function getErrorContainer() {
	      return main_core.Tag.render(_templateObject2(), main_core.Loc.getMessage('TASKS_SCRUM_ERROR_ALL_TOGGLED'));
	    }
	  }, {
	    key: "isAllToggled",
	    value: function isAllToggled() {
	      /* eslint-disable */
	      if (typeof BX.Tasks.CheckListInstance === 'undefined') {
	        return false;
	      }

	      var isAllToggled = true;
	      var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
	      treeStructure.getDescendants().forEach(function (checkList) {
	        if (!checkList.checkIsComplete()) {
	          isAllToggled = false;
	        }
	      });
	      return isAllToggled;
	      /* eslint-enable */
	    }
	  }, {
	    key: "isRequiredToggle",
	    value: function isRequiredToggle(data) {
	      return data.requiredOption === 'Y';
	    }
	  }, {
	    key: "removeClockIconFromButton",
	    value: function removeClockIconFromButton() {
	      var buttonsContainer = this.form.querySelector('.tasks-scrum-project-side-panel-buttons');

	      if (buttonsContainer) {
	        main_core.Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
	      }
	    }
	  }]);
	  return ScrumDod;
	}();

	exports.ScrumDod = ScrumDod;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX,BX.UI.Dialogs));
//# sourceMappingURL=scrum.dod.bundle.js.map
