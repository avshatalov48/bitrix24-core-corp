/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,ui_sidepanel_layout,main_core,ui_dialogs_messagebox) {
	'use strict';

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }
	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(controller, action) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      var analyticsLabel = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
	          data: data,
	          analyticsLabel: analyticsLabel
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "getDataForSprintStartForm",
	    value: function getDataForSprintStartForm(data) {
	      return this.sendRequest('sprint', 'getDataForSprintStartForm', data);
	    }
	  }, {
	    key: "startSprint",
	    value: function startSprint(data) {
	      return this.sendRequest('sprint', 'startSprint', data, {
	        scrum: 'Y',
	        action: 'sprint_start'
	      });
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
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;
	var SprintStartForm = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SprintStartForm, _EventEmitter);
	  function SprintStartForm(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, SprintStartForm);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SprintStartForm).call(this, params));
	    _this.setEventNamespace('BX.Tasks.Scrum.SprintStartForm');
	    _this.groupId = parseInt(params.groupId, 10);
	    _this.sprintId = parseInt(params.sprintId, 10);

	    /* eslint-disable */
	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.requestSender = new RequestSender();
	    _this.node = null;
	    _this.startButton = null;
	    return _this;
	  }
	  babelHelpers.createClass(SprintStartForm, [{
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      this.sidePanelManager.open('tasks-scrum-sprint-start-form-side-panel', {
	        cacheable: false,
	        width: 700,
	        label: {
	          text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_SIDE_PANEL_LABEL')
	        },
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['ui.dialogs.messagebox', 'tasks.scrum.sprint-start-form'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TITLE'),
	            content: _this2.createContent.bind(_this2),
	            design: {
	              section: false
	            },
	            buttons: function buttons(_ref) {
	              var cancelButton = _ref.cancelButton,
	                SaveButton = _ref.SaveButton;
	              return [_this2.startButton = new SaveButton({
	                text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_BUTTON'),
	                onclick: _this2.onStart.bind(_this2)
	              }), cancelButton];
	            }
	          });
	        }
	      });
	    }
	  }, {
	    key: "onStart",
	    value: function onStart() {
	      var _this3 = this;
	      this.startButton.setWaiting();
	      var baseContainer = this.node.querySelector('.tasks-scrum__side-panel-start--info-basic');
	      var timeContainer = this.node.querySelector('.tasks-scrum__side-panel-start--timing');
	      var dateInputs = timeContainer.querySelectorAll('.ui-ctl-date');
	      var dateStartValue = dateInputs.item(0).querySelector('input').value;
	      var dateEndValue = dateInputs.item(1).querySelector('input').value;
	      this.requestSender.startSprint({
	        groupId: this.groupId,
	        sprintId: this.sprintId,
	        name: baseContainer.querySelector('input').value,
	        sprintGoal: baseContainer.querySelector('textarea').value,
	        dateStart: Math.floor(BX.parseDate(dateStartValue).getTime() / 1000),
	        dateEnd: Math.floor(BX.parseDate(dateEndValue).getTime() / 1000)
	      }).then(function (response) {
	        _this3.sidePanelManager.close(false, function () {
	          _this3.emit('afterStart');
	        });
	      })["catch"](function (response) {
	        _this3.startButton.setWaiting(false);
	        _this3.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
	      });
	    }
	  }, {
	    key: "createContent",
	    value: function createContent() {
	      var _this4 = this;
	      return new Promise(function (resolve, reject) {
	        _this4.requestSender.getDataForSprintStartForm({
	          groupId: _this4.groupId,
	          sprintId: _this4.sprintId
	        }).then(function (response) {
	          resolve(_this4.render(response.data));
	        })["catch"](function (response) {
	          reject();
	          _this4.sidePanelManager.close(false, function () {
	            _this4.requestSender.showErrorAlert(response);
	          });
	        });
	      });
	    }
	  }, {
	    key: "render",
	    value: function render(sprintData) {
	      var _this5 = this;
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-start tasks-scrum__scope--side-panel-start\">\n\n\t\t\t\t<div class=\"tasks-scrum__side-panel-start--block\">\n\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-basic\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-basic-block\">\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"tasks-scrum__side-panel-start--info-basic-input\"\n\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-basic-block\">\n\t\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\t\tplaceholder=\"", "\"\n\t\t\t\t\t\t\t\trows=\"7\"\n\t\t\t\t\t\t\t\tclass=\"tasks-scrum__side-panel-start--info-basic-textarea\"\n\t\t\t\t\t\t\t></textarea>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-additional\">\n\n\t\t\t\t\t\t", "\n\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-row tasks-scrum__side-panel-start--timing\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-content\">\n\t\t\t\t\t\t\t\t<label class=\"tasks-scrum__side-panel-start--date\">\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--date-name\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-date\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\treadonly=\"readonly\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t\t<label class=\"tasks-scrum__side-panel-start--date\">\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--date-name\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-date\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\treadonly=\"readonly\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</label>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-row\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-content\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block\">\n\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--sprint-plans\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block-number\">\n\t\t\t\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"tasks-scrum__side-panel-start--plan-block-number-date\" \n\t\t\t\t\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block-name\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block-name-text\">\n\t\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--sprint-plans\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block-name\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-start--plan-block-name-text\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-hint-icon\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint-no-icon\n\t\t\t\t\t\t\t\t\t\t\t></span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_NAME_PLACEHOLDER'), main_core.Text.encode(sprintData.name), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DESC_PLACEHOLDER'), this.renderEpics(sprintData.epics), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TIME_ROW_LABEL'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DATE_START_LABEL'), sprintData.dateStart, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DATE_END_LABEL'), sprintData.dateEnd, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_PLAN_ROW_LABEL'), sprintData.numberTasks, sprintData.numberTasks, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TASK_COUNT_LABEL'), this.renderWheelStoryPoints(sprintData), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_STORY_POINTS_LABEL'), main_core.Loc.getMessage('TSS_START_STORY_POINTS_HINT'), this.renderWarning(sprintData.numberUnevaluatedTasks));
	      var timeContainer = this.node.querySelector('.tasks-scrum__side-panel-start--timing');
	      timeContainer.querySelectorAll('.ui-ctl-date').forEach(function (inputContainer) {
	        main_core.Event.bind(inputContainer, 'click', _this5.showCalendar.bind(_this5, inputContainer));
	      });
	      this.initHints(this.node);
	      return this.node;
	    }
	  }, {
	    key: "renderEpics",
	    value: function renderEpics(epics) {
	      var _this6 = this;
	      if (!epics.length) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-start--info-row\">\n\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__side-panel-start--info-content\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_EPICS_ROW_LABEL'), epics.map(function (epic) {
	        var colorBorder = _this6.convertHexToRGBA(epic.color, 0.7);
	        var colorBackground = _this6.convertHexToRGBA(epic.color, 0.3);
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tclass=\"tasks-scrum__epic-label\"\n\t\t\t\t\t\t\t\t\tstyle=\"background: ", "; border-color: ", ";\"\n\t\t\t\t\t\t\t\t>", "</span>"])), colorBackground, colorBorder, main_core.Text.encode(epic.name));
	      }));
	    }
	  }, {
	    key: "renderWarning",
	    value: function renderWarning(count) {
	      if (count === 0) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-alert ui-alert-icon-danger ui-alert-warning\">\n\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_WARN_TEXT').replace('#count#', count));
	    }
	  }, {
	    key: "renderWheelStoryPoints",
	    value: function renderWheelStoryPoints(sprintData) {
	      var numberClass = '';
	      if (sprintData.differenceMarker) {
	        var arrowClass = sprintData.storyPoints === '' ? '' : '--arrow-up';
	        numberClass = "tasks-scrum__side-panel-start--plan-block-number ".concat(arrowClass, " --success");
	      } else {
	        var _arrowClass = sprintData.storyPoints === '' ? '' : '--arrow-down';
	        numberClass = "tasks-scrum__side-panel-start--plan-block-number ".concat(_arrowClass, " --warning");
	      }
	      var renderProgress = function renderProgress(differenceStoryPoints) {
	        if (parseInt(differenceStoryPoints, 10) === 0) {
	          return '';
	        }
	        return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum__side-panel-start--progress\">\n\t\t\t\t\t<span class=\"tasks-scrum__side-panel-start--progress-number\">", "</span>\n\t\t\t\t\t<span class=\"tasks-scrum__side-panel-start--progress-percent\">%</span></div>\n\t\t\t\t</div>\n\t\t\t"])), differenceStoryPoints);
	      };
	      return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t<div \n\t\t\t\tclass=\"tasks-scrum__side-panel-start--plan-block-number-date\"\n\t\t\t\ttitle=\"", "\"\n\t\t\t>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t", "\n\t\t"])), numberClass, sprintData.storyPoints === '' ? 0 : sprintData.storyPoints, sprintData.storyPoints === '' ? 0 : sprintData.storyPoints, renderProgress(sprintData.differenceStoryPoints));
	    }
	  }, {
	    key: "showCalendar",
	    value: function showCalendar(inputContainer) {
	      /* eslint-disable */
	      new BX.JCCalendar().Show({
	        node: inputContainer,
	        field: inputContainer.querySelector('input'),
	        bTime: false,
	        bSetFocus: false,
	        bHideTime: false
	      });
	      /* eslint-enable */
	    }
	  }, {
	    key: "convertHexToRGBA",
	    value: function convertHexToRGBA(hexCode, opacity) {
	      var hex = hexCode.replace('#', '');
	      if (hex.length === 3) {
	        hex = "".concat(hex[0]).concat(hex[0]).concat(hex[1]).concat(hex[1]).concat(hex[2]).concat(hex[2]);
	      }
	      var r = parseInt(hex.substring(0, 2), 16);
	      var g = parseInt(hex.substring(2, 4), 16);
	      var b = parseInt(hex.substring(4, 6), 16);
	      return "rgba(".concat(r, ",").concat(g, ",").concat(b, ",").concat(opacity, ")");
	    }
	  }, {
	    key: "initHints",
	    value: function initHints(node) {
	      // todo wtf hint
	      BX.UI.Hint.popup = null;
	      BX.UI.Hint.id = 'ui-hint-popup-' + +new Date();
	      BX.UI.Hint.init(node);
	    }
	  }]);
	  return SprintStartForm;
	}(main_core_events.EventEmitter);

	exports.SprintStartForm = SprintStartForm;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX.UI.SidePanel,BX,BX.UI.Dialogs));
//# sourceMappingURL=sprint.start.form.bundle.js.map
