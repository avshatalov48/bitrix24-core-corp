this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_manual,ui_popupcomponentsmaker,main_core,ui_dialogs_messagebox,main_core_events) {
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
	        main_core.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
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
	    key: "getTutorInfo",
	    value: function getTutorInfo(data) {
	      return this.sendRequest('info', 'getTutorInfo', data);
	    }
	  }, {
	    key: "getBurnDownInfo",
	    value: function getBurnDownInfo(data) {
	      return this.sendRequest('sprint', 'getBurnDownInfo', data);
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        console.error(response);
	        return;
	      }

	      if (response.errors.length) {
	        var firstError = response.errors.shift();

	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSM_ERROR_POPUP_TITLE');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
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
	      return top.BX.Runtime.loadExtension(extensionName).then(function (exports) {
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

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	var Methodology = /*#__PURE__*/function () {
	  function Methodology(params) {
	    babelHelpers.classCallCheck(this, Methodology);
	    this.groupId = parseInt(params.groupId, 10);
	    this.teamSpeedPath = main_core.Type.isString(params.teamSpeedPath) ? params.teamSpeedPath : '';
	    this.burnDownPath = main_core.Type.isString(params.burnDownPath) ? params.burnDownPath : '';
	    this.pathToTask = main_core.Type.isString(params.pathToTask) ? params.pathToTask : '';
	    this.requestSender = new RequestSender();
	    this.sidePanel = new SidePanel();
	    this.menu = null;
	    this.hintManager = null;
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
	        id: 'tasks-scrum-methodology-widget',
	        target: targetNode,
	        cacheable: false,
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
	            html: this.renderBurnDown()
	          }]
	        }, {
	          html: [{
	            html: this.renderTutor(),
	            backgroundColor: '#fafafa'
	          }]
	        }, {
	          html: [{
	            html: this.renderMigration()
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
	        var baseClasses = 'tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope';
	        var node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"", " tasks-scrum__widget-methodology--bg ", "\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"ui-icon ", " tasks-scrum__widget-methodology--icon\"\n\t\t\t\t\t\t\t><i></i></div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t<i class=\"ui-hint-icon\" data-hint=\"", "\"></i>\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box\">\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-qr-popupcomponentmaker__btn ", "\"\n\t\t\t\t\t\t\t\t\t\tdata-role=\"open-epics\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), baseClasses, blockClass, iconClass, main_core.Loc.getMessage('TSF_EPIC_TITLE'), main_core.Loc.getMessage('TSF_EPIC_HINT'), buttonClass, buttonText);

	        _this.initHints(node);

	        main_core.Event.bind(node.querySelector('button'), 'click', function () {
	          if (existsEpic) {
	            _this.showEpics();
	          } else {
	            _this.createEpic();
	          }
	        });
	        return node;
	      })["catch"](function (response) {
	        _this.requestSender.showErrorAlert(response);
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
	        var node = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg ", "\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"ui-icon ", " tasks-scrum__widget-methodology--icon\"\n\t\t\t\t\t\t\t><i></i></div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t\t<span class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t<i class=\"ui-hint-icon\" data-hint=\"", "\"></i>\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box\">\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-qr-popupcomponentmaker__btn ", "\"\n\t\t\t\t\t\t\t\t\t\tdata-role=\"open-dod\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), blockClass, iconClass, main_core.Loc.getMessage('TSF_DOD_TITLE_NEW'), main_core.Loc.getMessage('TSF_DOD_HINT_NEW'), buttonClass, buttonText);

	        _this2.initHints(node);

	        main_core.Event.bind(node.querySelector('button'), 'click', _this2.showDodSettings.bind(_this2));
	        return node;
	      })["catch"](function (response) {
	        _this2.requestSender.showErrorAlert(response);
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
	        var node = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope\"\n\t\t\t\t\t\tdata-role=\"show-team-speed-chart\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box-center\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--image ", "\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<button class=\"", " ", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), disableClass, btnUiClasses, disableClass, main_core.Loc.getMessage('TSF_TEAM_SPEED_BUTTON'));

	        if (!isDisabled) {
	          main_core.Event.bind(node, 'click', _this3.showTeamSpeedChart.bind(_this3));
	        }

	        return node;
	      })["catch"](function (response) {
	        _this3.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderBurnDown",
	    value: function renderBurnDown() {
	      var _this4 = this;

	      return this.requestSender.getBurnDownInfo({
	        groupId: this.groupId
	      }).then(function (response) {
	        var existsChart = !main_core.Type.isNull(response.data.sprint);
	        var btnUiClasses = 'ui-qr-popupcomponentmaker__btn --border';
	        var disableClass = existsChart ? '' : '--disabled';
	        var node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope\"\n\t\t\t\t\t\tdata-role=\"show-burn-down-chart\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--btn-box-center\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--image-diagram ", "\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<button class=\"", " ", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), disableClass, btnUiClasses, disableClass, main_core.Loc.getMessage('TSF_TEAM_SPEED_DIAGRAM'));

	        if (existsChart) {
	          main_core.Event.bind(node, 'click', _this4.showBurnDownChart.bind(_this4, response.data.sprint.id));
	        }

	        return node;
	      })["catch"](function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderTutor",
	    value: function renderTutor() {
	      var _this5 = this;

	      return this.requestSender.getTutorInfo({
	        groupId: this.groupId
	      }).then(function (response) {
	        var baseClasses = 'tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope';
	        var tutorClasses = 'tasks-scrum__widget-methodology--training tasks-scrum__widget-methodology--bg';
	        var node = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"", " ", " --active\"\n\t\t\t\t\t\tdata-role=\"open-tutor\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon ui-icon-service-tutorial tasks-scrum__widget-methodology--icon\">\n\t\t\t\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--description\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--label --hidden\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), baseClasses, tutorClasses, main_core.Loc.getMessage('TSF_TUTORIAL_TITLE'), main_core.Loc.getMessage('TSF_TUTORIAL_TEXT'), main_core.Loc.getMessage('TSF_TEAM_SPEED_LABEL'));
	        main_core.Event.bind(node, 'click', function () {
	          ui_manual.Manual.show('scrum', response.data.urlParams, {
	            scrum: 'Y',
	            action: 'guide_open'
	          });
	        });
	        return node;
	      })["catch"](function (response) {
	        _this5.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderMigration",
	    value: function renderMigration() {
	      var _this6 = this;

	      var baseClasses = 'tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope';
	      var migrationClasses = 'tasks-scrum__widget-methodology--migration tasks-scrum__widget-methodology--bg';
	      var iconClass = 'ui-icon-service-tutorial tasks-scrum__widget-methodology--migration-btn';
	      var node = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", " ", "\" data-role=\"open-migration\">\n\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--conteiner\">\n\t\t\t\t\t\t<div class=\"ui-icon ", " tasks-scrum__widget-methodology--icon\">\n\t\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--content\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--name\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-methodology--label --migration\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), baseClasses, migrationClasses, iconClass, main_core.Loc.getMessage('TSF_MIGRATION_TITLE'), main_core.Loc.getMessage('TSF_MIGRATION_LABEL'));
	      main_core.Event.bind(node, 'click', function () {
	        var uri = new main_core.Uri('/marketplace/');
	        uri.setQueryParam('tag', ['migrator', 'tasks']);

	        _this6.sidePanel.openSidePanelByUrl(uri.toString());

	        _this6.menu.close();
	      });
	      return node;
	    }
	  }, {
	    key: "showEpics",
	    value: function showEpics() {
	      this.sidePanel.showByExtension('Epic', {
	        view: 'list',
	        groupId: this.groupId,
	        pathToTask: this.pathToTask
	      }).then(function (extension) {
	        BX.Tasks.Scrum.EpicInstance = extension;
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
	      if (this.teamSpeedPath) {
	        this.sidePanel.openSidePanel(this.teamSpeedPath);
	      } else {
	        throw new Error('Could not find a page to display the chart.');
	      }

	      this.menu.close();
	      main_core.ajax.runAction('bitrix:tasks.scrum.info.saveAnalyticsLabel', {
	        data: {},
	        analyticsLabel: {
	          scrum: 'Y',
	          action: 'open_team_speed_diag'
	        }
	      });
	    }
	  }, {
	    key: "showBurnDownChart",
	    value: function showBurnDownChart(sprintId) {
	      if (this.burnDownPath) {
	        this.sidePanel.openSidePanel(this.burnDownPath.replace('#sprint_id#', sprintId));
	      } else {
	        throw new Error('Could not find a page to display the chart.');
	      }

	      this.menu.close();
	      main_core.ajax.runAction('bitrix:tasks.scrum.info.saveAnalyticsLabel', {
	        data: {},
	        analyticsLabel: {
	          scrum: 'Y',
	          action: 'open_burn_diag'
	        }
	      });
	    }
	  }, {
	    key: "initHints",
	    value: function initHints(node) {
	      this.hintManager = BX.UI.Hint.createInstance({
	        popupParameters: {
	          closeByEsc: true,
	          autoHide: true
	        }
	      });
	      this.hintManager.init(node);
	    }
	  }]);
	  return Methodology;
	}();

	exports.Methodology = Methodology;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.UI.Manual,BX.UI,BX,BX.UI.Dialogs,BX.Event));
//# sourceMappingURL=methodology.bundle.js.map
