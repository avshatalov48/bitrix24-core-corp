this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_popupcomponentsmaker,main_core,main_core_events) {
	'use strict';

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }

	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(controller, action) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      return new Promise(function (resolve, reject) {
	        top.BX.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "getEpicInfo",
	    value: function getEpicInfo(data) {
	      return this.sendRequest('epic', 'getEpicInfo', data);
	    }
	  }, {
	    key: "getDodInfo",
	    value: function getDodInfo(data) {
	      return this.sendRequest('dod', 'getDodInfo', data);
	    }
	  }, {
	    key: "getTeamSpeedInfo",
	    value: function getTeamSpeedInfo(data) {
	      return this.sendRequest('sprint', 'getTeamSpeedInfo', data);
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
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSM_ERROR_POPUP_TITLE');
	          top.BX.UI.Dialogs.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var SidePanel = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SidePanel, _EventEmitter);

	  function SidePanel() {
	    var _this;

	    babelHelpers.classCallCheck(this, SidePanel);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SidePanel).call(this));

	    _this.setEventNamespace('BX.Tasks.Scrum.Methodology.SidePanel');
	    /* eslint-disable */


	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    return _this;
	  }

	  babelHelpers.createClass(SidePanel, [{
	    key: "openSidePanelByUrl",
	    value: function openSidePanelByUrl(url) {
	      this.sidePanelManager.open(url);
	    }
	  }, {
	    key: "openSidePanel",
	    value: function openSidePanel(id, options) {
	      this.sidePanelManager.open(id, options);
	    }
	  }, {
	    key: "showByExtension",
	    value: function showByExtension(name, params) {
	      var extensionName = 'tasks.scrum.' + name.toLowerCase();
	      return main_core.Runtime.loadExtension(extensionName).then(function (exports) {
	        name = name.replaceAll('-', '');

	        if (exports && exports[name]) {
	          var extension = new exports[name](params);
	          extension.show();
	          return extension;
	        } else {
	          return null;
	        }
	      });
	    }
	  }]);
	  return SidePanel;
	}(main_core_events.EventEmitter);

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	var Methodology = /*#__PURE__*/function () {
	  function Methodology(params) {
	    babelHelpers.classCallCheck(this, Methodology);
	    this.groupId = parseInt(params.groupId, 10);
	    this.requestSender = new RequestSender();
	    this.sidePanel = new SidePanel();
	    this.menu = null;
	  }

	  babelHelpers.createClass(Methodology, [{
	    key: "showMenu",
	    value: function showMenu(targetNode) {
	      if (this.menu) {
	        if (this.menu.isShown()) {
	          this.menu.close();
	          return;
	        }
	      }

	      this.menu = new ui_popupcomponentsmaker.PopupComponentsMaker({
	        target: targetNode,
	        content: [{
	          html: [{
	            html: this.renderEpics(),
	            backgroundColor: '#fafafa'
	          }, {
	            html: this.renderDod(),
	            backgroundColor: '#fafafa'
	          }]
	        }, {
	          html: [{
	            html: this.renderTeamSpeed()
	          }]
	        }, {
	          html: [{
	            html: this.renderTutor(),
	            backgroundColor: '#fafafa'
	          }]
	        }]
	      });
	      this.menu.show();
	    }
	  }, {
	    key: "renderEpics",
	    value: function renderEpics() {
	      var _this = this;

	      return this.requestSender.getEpicInfo({
	        groupId: this.groupId
	      }).then(function (response) {
	        var existsEpic = response.data.existsEpic;
	        var buttonText = existsEpic ? main_core.Loc.getMessage('TSF_EPIC_OPEN_BUTTON') : main_core.Loc.getMessage('TSF_EPIC_CREATE_BUTTON');
	        var buttonClass = existsEpic ? '--border' : '';
	        var iconClass = existsEpic ? 'ui-icon-service-epics' : 'ui-icon-service-light-epics';
	        var blockClass = existsEpic ? '--active' : '';
	        var node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg  ", "\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"ui-icon ", " tasks-scrum__widget-methodology--icon\"\n\t\t\t\t\t\t\t><i></i></div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t<i class=\"ui-hint-icon\" data-hint=\"", "\"></i>\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box\">\n\t\t\t\t\t\t\t\t\t<button class=\"ui-qr-popupcomponentmaker__btn ", "\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), blockClass, iconClass, main_core.Loc.getMessage('TSF_EPIC_TITLE'), main_core.Loc.getMessage('TSF_EPIC_HINT'), buttonClass, buttonText);

	        _this.initHints(node);

	        main_core.Event.bind(node.querySelector('button'), 'click', function () {
	          if (existsEpic) {
	            _this.showEpics();
	          } else {
	            _this.createEpic();
	          }
	        });
	        return node;
	      });
	    }
	  }, {
	    key: "renderDod",
	    value: function renderDod() {
	      var _this2 = this;

	      return this.requestSender.getDodInfo({
	        groupId: this.groupId
	      }).then(function (response) {
	        var existsDod = response.data.existsDod;
	        var buttonText = existsDod ? main_core.Loc.getMessage('TSF_DOD_OPEN_BUTTON') : main_core.Loc.getMessage('TSF_DOD_CREATE_BUTTON');
	        var buttonClass = existsDod ? '--border' : '';
	        var iconClass = existsDod ? 'ui-icon-service-dod' : 'ui-icon-service-light-dod';
	        var blockClass = existsDod ? '--active' : '';
	        var node = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg ", "\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"ui-icon ", " tasks-scrum__widget-methodology--icon\"\n\t\t\t\t\t\t\t><i></i></div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t<i class=\"ui-hint-icon\" data-hint=\"", "\"></i>\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box\">\n\t\t\t\t\t\t\t\t\t<button class=\"ui-qr-popupcomponentmaker__btn ", "\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), blockClass, iconClass, main_core.Loc.getMessage('TSF_DOD_TITLE'), main_core.Loc.getMessage('TSF_DOD_HINT'), buttonClass, buttonText);

	        _this2.initHints(node);

	        main_core.Event.bind(node.querySelector('button'), 'click', _this2.showDodSettings.bind(_this2));
	        return node;
	      });
	    }
	  }, {
	    key: "renderTeamSpeed",
	    value: function renderTeamSpeed() {
	      var _this3 = this;

	      return this.requestSender.getTeamSpeedInfo({
	        groupId: this.groupId
	      }).then(function (response) {
	        var isDisabled = !response.data.existsCompletedSprint;
	        var btnUiClasses = 'ui-qr-popupcomponentmaker__btn --border';
	        var disableClass = isDisabled ? '--disabled' : '';
	        var node = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box-center\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--image ", "\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<button class=\"", " ", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), disableClass, btnUiClasses, disableClass, main_core.Loc.getMessage('TSF_TEAM_SPEED_BUTTON'));

	        if (!isDisabled) {
	          main_core.Event.bind(node, 'click', _this3.showTeamSpeedChart.bind(_this3));
	        }

	        return node;
	      });
	    }
	  }, {
	    key: "renderTutor",
	    value: function renderTutor() {
	      var node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg\"\n\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t<div class=\"ui-icon ui-icon-service-light-tutorial tasks-scrum__widget-methodology--icon\"><i></i></div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--description\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TSF_TUTORIAL_TITLE'), main_core.Loc.getMessage('TSF_TUTORIAL_TEXT'));
	      return node;
	    }
	  }, {
	    key: "showEpics",
	    value: function showEpics() {
	      this.sidePanel.showByExtension('Epic', {
	        view: 'list',
	        groupId: this.groupId
	      });
	      this.menu.close();
	    }
	  }, {
	    key: "createEpic",
	    value: function createEpic() {
	      this.sidePanel.showByExtension('Epic', {
	        view: 'add',
	        groupId: this.groupId
	      });
	      this.menu.close();
	    }
	  }, {
	    key: "showDodSettings",
	    value: function showDodSettings() {
	      this.sidePanel.showByExtension('Dod', {
	        view: 'settings',
	        groupId: this.groupId
	      });
	      this.menu.close();
	    }
	  }, {
	    key: "showTeamSpeedChart",
	    value: function showTeamSpeedChart() {
	      this.sidePanel.showByExtension('Team-Speed-Chart', {
	        groupId: this.groupId
	      });
	      this.menu.close();
	    }
	  }, {
	    key: "initHints",
	    value: function initHints(node) {
	      BX.UI.Hint.init(node);
	    }
	  }]);
	  return Methodology;
	}();

	exports.Methodology = Methodology;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI,BX,BX.Event));
//# sourceMappingURL=methodology.bundle.js.map
