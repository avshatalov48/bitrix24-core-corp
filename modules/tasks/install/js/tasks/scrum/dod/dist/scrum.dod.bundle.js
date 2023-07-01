this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_popup,main_loader,ui_dialogs_messagebox,ui_buttons,main_core_events,main_core) {
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
	    _this.contentSidePanelManager = new BX.SidePanel.Manager({});
	    /* eslint-enable */

	    _this.contentSidePanels = new Set();

	    _this.bindEvents();

	    return _this;
	  }

	  babelHelpers.createClass(SidePanel, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this2 = this;

	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onLoad', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	            _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	            sliderEvent = _event$getCompatData2[0];

	        var sidePanel = sliderEvent.getSlider();
	        sidePanel.setCacheable(false);

	        _this2.emit('onLoadSidePanel', sidePanel);
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', function (event) {
	        var _event$getCompatData3 = event.getCompatData(),
	            _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	            sliderEvent = _event$getCompatData4[0];

	        var sidePanel = sliderEvent.getSlider();

	        _this2.emit('onCloseSidePanel', sidePanel);
	      });
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', function (event) {
	        var _event$getCompatData5 = event.getCompatData(),
	            _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 1),
	            sliderEvent = _event$getCompatData6[0];

	        var sidePanel = sliderEvent.getSlider();

	        if (_this2.contentSidePanels.has(sidePanel.getUrl())) {
	          _this2.contentSidePanels.delete(sidePanel.getUrl());

	          if (!_this2.contentSidePanels.size) {
	            _this2.resetBodyWidthHack();

	            _this2.addEscapePressHandler();
	          }
	        }
	      });
	    }
	  }, {
	    key: "openSidePanel",
	    value: function openSidePanel(id, options) {
	      this.applyBodyWidthHack();
	      this.removeEscapePressHandler();
	      this.contentSidePanelManager.open(id, options);
	      this.contentSidePanels.add(id);
	    }
	  }, {
	    key: "existFrameTopSlider",
	    value: function existFrameTopSlider() {
	      return Boolean(this.sidePanelManager.getTopSlider());
	    }
	  }, {
	    key: "addEscapePressHandler",
	    value: function addEscapePressHandler() {
	      var sidePanel = this.sidePanelManager.getTopSlider();

	      if (sidePanel) {
	        var frameWindow = sidePanel.getFrameWindow();
	        frameWindow.addEventListener('keydown', sidePanel.handleFrameKeyDown);
	      }
	    }
	  }, {
	    key: "removeEscapePressHandler",
	    value: function removeEscapePressHandler() {
	      var sidePanel = this.sidePanelManager.getTopSlider();

	      if (sidePanel) {
	        var frameWindow = sidePanel.getFrameWindow();
	        frameWindow.removeEventListener('keydown', sidePanel.handleFrameKeyDown);
	      }
	    }
	  }, {
	    key: "applyBodyWidthHack",
	    value: function applyBodyWidthHack() {
	      if (this.existFrameTopSlider()) {
	        main_core.Dom.addClass(document.body, 'tasks-scrum-dod-panel-padding');
	      }
	    }
	  }, {
	    key: "resetBodyWidthHack",
	    value: function resetBodyWidthHack() {
	      if (this.existFrameTopSlider()) {
	        main_core.Dom.removeClass(document.body, 'tasks-scrum-dod-panel-padding');
	      }
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }

	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(action) {
	      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.service.definitionOfDoneService.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings(data) {
	      return this.sendRequest('getSettings', data);
	    }
	  }, {
	    key: "getList",
	    value: function getList(data) {
	      return this.sendRequest('getList', data);
	    }
	  }, {
	    key: "saveList",
	    value: function saveList(data) {
	      return this.sendRequest('saveList', data);
	    }
	  }]);
	  return RequestSender;
	}();

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"ui-form-content\">\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element tasks-scrum-dod-types\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t<div class=\"ui-form-content tasks-scrum-dod-checklist\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-form ui-form-line tasks-scrum-dod-form\">\n\t\t\t\t\t<div class=\"ui-form-row\">\n\t\t\t\t\t\t<div class=\"ui-form-label\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-label-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

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
	    this.emptyDod = true;
	    this.skipNotifications = false;
	  }

	  babelHelpers.createClass(ScrumDod, [{
	    key: "showList",
	    value: function showList(taskId) {
	      var _this = this;

	      this.taskId = parseInt(taskId, 10);
	      return this.requestSender.getSettings({
	        groupId: this.groupId,
	        taskId: this.taskId
	      }).then(function (response) {
	        var settings = response.data;
	        var types = settings.types;
	        _this.emptyDod = types.length === 0;
	        var activeTypeId = settings.activeTypeId;

	        if (_this.isEmptyDod()) {
	          if (!_this.skipNotifications) {
	            return Promise.resolve();
	          }
	        }

	        _this.setActiveTypeData(activeTypeId, types);

	        var popup = _this.createPopup(types);

	        popup.subscribe('onAfterShow', function (baseEvent) {
	          if (_this.isEmptyDod()) {
	            return;
	          }

	          var contentContainer = popup.getContentContainer();
	          var typesNode = contentContainer.querySelector('.tasks-scrum-dod-types');
	          var listNode = contentContainer.querySelector('.tasks-scrum-dod-checklist');
	          main_core.Event.bind(typesNode, 'change', function () {
	            var typeId = parseInt(typesNode.value, 10);

	            _this.setActiveTypeData(typeId, types);

	            _this.renderListTo(listNode, typeId).then(function () {
	              popup.adjustPosition();
	            });
	          });

	          _this.renderListTo(listNode, typesNode.value).then(function () {
	            popup.adjustPosition();
	          });
	        });
	        popup.subscribe('onClose', function () {
	          return _this.onClose();
	        });
	        popup.show();
	        return new Promise(function (resolve, reject) {
	          _this.resolver = resolve;
	          _this.rejecter = reject;
	        });
	      });
	    }
	  }, {
	    key: "skipNotificationPopups",
	    value: function skipNotificationPopups() {
	      this.skipNotifications = true;
	    }
	  }, {
	    key: "onClose",
	    value: function onClose() {
	      var _this2 = this;

	      if (this.isEmptyDod()) {
	        return;
	      }

	      var activeTypeData = this.getActiveTypeData();
	      this.requestSender.saveList({
	        typeId: activeTypeData.id,
	        taskId: this.taskId,
	        items: this.getListItems()
	      }).then(function () {
	        if (_this2.skipNotifications) {
	          _this2.solve();
	        } else {
	          if (_this2.isListRequired(_this2.getActiveTypeData())) {
	            if (_this2.isAllToggled()) {
	              _this2.resolver();
	            } else {
	              _this2.rejecter();

	              _this2.showInfoPopup();
	            }
	          } else {
	            if (_this2.isAllToggled()) {
	              _this2.resolver();
	            } else {
	              _this2.showConfirmPopup();
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "showInfoPopup",
	    value: function showInfoPopup() {
	      ui_dialogs_messagebox.MessageBox.show({
	        message: main_core.Loc.getMessage('TASKS_SCRUM_DOD_INFO_TEXT'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK
	      });
	    }
	  }, {
	    key: "showConfirmPopup",
	    value: function showConfirmPopup() {
	      var _this3 = this;

	      var messageBox = new ui_dialogs_messagebox.MessageBox({
	        message: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_TEXT_COMPLETE'),
	        modal: true,
	        buttons: [new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.SUCCESS,
	          events: {
	            click: function click() {
	              _this3.resolver();

	              messageBox.close();
	            }
	          }
	        }), new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.LINK,
	          events: {
	            click: function click() {
	              _this3.rejecter();

	              messageBox.close();
	            }
	          }
	        })]
	      });
	      messageBox.show();
	    }
	  }, {
	    key: "solve",
	    value: function solve() {
	      if (this.isListRequired(this.getActiveTypeData())) {
	        if (this.isAllToggled()) {
	          this.resolver();
	        } else {
	          this.rejecter();
	        }
	      } else {
	        this.resolver();
	      }
	    }
	  }, {
	    key: "createPopup",
	    value: function createPopup(types) {
	      var buttons = [];

	      if (this.isEmptyDod()) {
	        buttons.push(new ui_buttons.Button({
	          text: main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_CLOSE_BUTTON_TEXT'),
	          color: ui_buttons.Button.Color.LINK,
	          events: {
	            click: function click() {
	              return popup.close();
	            }
	          }
	        }));
	      } else {
	        buttons.push(new ui_buttons.Button({
	          text: this.getPopupButtonText(),
	          color: ui_buttons.Button.Color.SUCCESS,
	          events: {
	            click: function click() {
	              return popup.close();
	            }
	          }
	        }));
	      }

	      var popup = new main_popup.Popup(main_core.Text.getRandom(), null, {
	        titleBar: main_core.Loc.getMessage('TASKS_SCRUM_DOD_HEADER'),
	        content: this.renderContent(types),
	        contentPadding: 10,
	        contentBackground: '#f8f9fa',
	        autoHide: true,
	        closeByEsc: true,
	        closeIcon: true,
	        overlay: true,
	        buttons: buttons
	      });
	      return popup;
	    }
	  }, {
	    key: "renderContent",
	    value: function renderContent(types) {
	      if (this.isEmptyDod()) {
	        return main_core.Tag.render(_templateObject(), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_EMPTY'));
	      }

	      var activeTypeData = this.getActiveTypeData();

	      var renderOption = function renderOption(typeData) {
	        var selected = activeTypeData.id === typeData.id ? 'selected' : '';
	        return "<option value=\"".concat(parseInt(typeData.id, 10), "\" ").concat(selected, ">").concat(main_core.Text.encode(typeData.name), "</option>");
	      };

	      return main_core.Tag.render(_templateObject2(), main_core.Loc.getMessage('TASKS_SCRUM_DOD_LABEL_TYPES'), types.map(function (typeData) {
	        return renderOption(typeData);
	      }).join(''));
	    }
	  }, {
	    key: "renderListTo",
	    value: function renderListTo(container, typeId) {
	      main_core.Dom.clean(container);
	      var loader = this.showLoader(container);
	      return this.requestSender.getList({
	        groupId: this.groupId,
	        taskId: this.taskId,
	        typeId: typeId
	      }).then(function (response) {
	        loader.hide();
	        return main_core.Runtime.html(container, response.data.html);
	      });
	    }
	  }, {
	    key: "setActiveTypeData",
	    value: function setActiveTypeData(activeTypeId, types) {
	      var activeTypeData = types.find(function (typeData) {
	        return typeData.id === activeTypeId;
	      });

	      if (activeTypeData) {
	        this.activeTypeData = activeTypeData;
	      } else {
	        this.activeTypeData = types[0];
	      }
	    }
	  }, {
	    key: "getActiveTypeData",
	    value: function getActiveTypeData() {
	      return this.activeTypeData;
	    }
	  }, {
	    key: "isEmptyDod",
	    value: function isEmptyDod() {
	      return this.emptyDod;
	    }
	  }, {
	    key: "isListRequired",
	    value: function isListRequired(typeData) {
	      return typeData.dodRequired === 'Y';
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(container) {
	      var listPosition = main_core.Dom.getPosition(container);
	      var loader = new main_loader.Loader({
	        target: container,
	        size: 60,
	        mode: 'inline',
	        color: 'rgba(82, 92, 105, 0.9)',
	        offset: {
	          left: "".concat(listPosition.width / 2 - 30, "px")
	        }
	      });
	      loader.show();
	      return loader;
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
	    key: "getPopupButtonText",
	    value: function getPopupButtonText() {
	      if (this.skipNotifications) {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT');
	      } else {
	        return main_core.Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT');
	      }
	    }
	  }]);
	  return ScrumDod;
	}();

	exports.ScrumDod = ScrumDod;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Main,BX,BX.UI.Dialogs,BX.UI,BX.Event,BX));
//# sourceMappingURL=scrum.dod.bundle.js.map
