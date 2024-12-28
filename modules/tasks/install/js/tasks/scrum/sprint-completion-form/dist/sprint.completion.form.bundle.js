/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,ui_sidepanel_layout,ui_confetti,main_core,ui_dialogs_messagebox) {
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
	    key: "getDataForSprintCompletionForm",
	    value: function getDataForSprintCompletionForm(data) {
	      return this.sendRequest('sprint', 'getDataForSprintCompletionForm', data);
	    }
	  }, {
	    key: "completeSprint",
	    value: function completeSprint(data) {
	      return this.sendRequest('sprint', 'completeSprint', data, {
	        scrum: 'Y',
	        action: 'finish_sprint'
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

	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var Culture = /*#__PURE__*/function () {
	  function Culture() {
	    babelHelpers.classCallCheck(this, Culture);
	  }
	  babelHelpers.createClass(Culture, [{
	    key: "setData",
	    value: function setData(data) {
	      this.data = data;
	    }
	  }, {
	    key: "getDayMonthFormat",
	    value: function getDayMonthFormat() {
	      return this.data.dayMonthFormat;
	    }
	  }, {
	    key: "getLongDateFormat",
	    value: function getLongDateFormat() {
	      return this.data.longDateFormat;
	    }
	  }, {
	    key: "getShortTimeFormat",
	    value: function getShortTimeFormat() {
	      return this.data.shortTimeFormat;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!_classStaticPrivateFieldSpecGet(Culture, Culture, _instance)) {
	        _classStaticPrivateFieldSpecSet(Culture, Culture, _instance, new Culture());
	      }
	      return _classStaticPrivateFieldSpecGet(Culture, Culture, _instance);
	    }
	  }]);
	  return Culture;
	}();
	var _instance = {
	  writable: true,
	  value: void 0
	};

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14;
	var SprintCompletionForm = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SprintCompletionForm, _EventEmitter);
	  function SprintCompletionForm(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, SprintCompletionForm);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SprintCompletionForm).call(this, params));
	    _this.setEventNamespace('BX.Tasks.Scrum.SprintCompletionForm');
	    _this.groupId = parseInt(params.groupId, 10);

	    /* eslint-disable */
	    _this.sidePanelManager = BX.SidePanel.Instance;
	    /* eslint-enable */

	    _this.sidePanelId = 'tasks-scrum-sprint-completion-form-side-panel';
	    _this.requestSender = new RequestSender();
	    _this.node = null;
	    _this.completeButton = null;
	    return _this;
	  }
	  babelHelpers.createClass(SprintCompletionForm, [{
	    key: "show",
	    value: function show() {
	      var _this2 = this;
	      this.sidePanelManager.open(this.sidePanelId, {
	        cacheable: false,
	        width: 700,
	        contentCallback: function contentCallback() {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['tasks.scrum.sprint-completion-form'],
	            title: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_TITLE'),
	            content: _this2.createContent.bind(_this2),
	            design: {
	              section: false
	            },
	            buttons: function buttons(_ref) {
	              var cancelButton = _ref.cancelButton,
	                SaveButton = _ref.SaveButton;
	              return [_this2.completeButton = new SaveButton({
	                text: main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_BUTTON'),
	                onclick: _this2.onComplete.bind(_this2)
	              }), cancelButton];
	            }
	          });
	        }
	      });
	    }
	  }, {
	    key: "onComplete",
	    value: function onComplete() {
	      var _this3 = this;
	      var direction = 'backlog';
	      var directionSelector = this.node.querySelector('.tasks-scrum__side-panel-completion--info-select');
	      if (directionSelector) {
	        directionSelector = directionSelector.querySelector('select');
	        direction = directionSelector.value;
	      }
	      this.completeButton.setWaiting();
	      this.requestSender.completeSprint({
	        groupId: this.groupId,
	        direction: direction
	      }).then(function (response) {
	        if (ui_confetti.Confetti) {
	          ui_confetti.Confetti.fire({
	            particleCount: 400,
	            spread: 80,
	            origin: {
	              x: 0.7,
	              y: 0.2
	            },
	            zIndex: _this3.sidePanelManager.getTopSlider().getZindex() + 1
	          }).then(function () {
	            _this3.closeSidePanel();
	            _this3.emit('afterComplete');
	          });
	        } else {
	          _this3.closeSidePanel();
	          _this3.emit('afterComplete');
	        }
	      })["catch"](function (response) {
	        _this3.completeButton.setWaiting(false);
	        _this3.requestSender.showErrorAlert(response, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_ERROR_TITLE_POPUP'));
	      });
	    }
	  }, {
	    key: "closeSidePanel",
	    value: function closeSidePanel() {
	      var _this4 = this;
	      var openSliders = this.sidePanelManager.getOpenSliders();
	      if (openSliders.length > 0) {
	        openSliders.forEach(function (slider) {
	          if (slider.getUrl() === _this4.sidePanelId) {
	            slider.close(false);
	          }
	        });
	      }
	    }
	  }, {
	    key: "createContent",
	    value: function createContent() {
	      var _this5 = this;
	      return new Promise(function (resolve, reject) {
	        _this5.requestSender.getDataForSprintCompletionForm({
	          groupId: _this5.groupId
	        }).then(function (response) {
	          Culture.getInstance().setData(response.data.culture);
	          resolve(_this5.render(response.data));
	        })["catch"](function (response) {
	          reject();
	          _this5.sidePanelManager.close(false, function () {
	            _this5.requestSender.showErrorAlert(response);
	          });
	        });
	      });
	    }
	  }, {
	    key: "render",
	    value: function render(sprintData) {
	      var storyPoints = sprintData.storyPoints === '' ? 0 : sprintData.storyPoints;
	      var periodDays = this.getPeriodDays(sprintData.dateStart);
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"tasks-scrum__scope--side-panel-completion\">\n\n\t\t\t<div class=\"tasks-scrum__side-panel-completion--block\">\n\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic\">\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-block\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-additional\">\n\n\t\t\t\t\t", "\n\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-row\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-content tasks-scrum__side-panel-completion--sprint-timing\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--date-name-block\">\n\t\t\t\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--date-result-block\">\n\t\t\t\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t\t\t\t<div>", "</div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-row\">\n\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-content\">\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block\">\n\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--sprint-plans\">\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block-number --percent\">\n\t\t\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\t\t\tclass=\"tasks-scrum__side-panel-completion--plan-block-number-date\"\n\t\t\t\t\t\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block-name\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block-name-text\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-hint-icon\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint-no-icon\n\t\t\t\t\t\t\t\t\t\t\t></span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--sprint-plans\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block-name\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--plan-block-name-text\">\n\t\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-hint\">\n\t\t\t\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\t\t\t\tclass=\"ui-hint-icon\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t\t\t\t\t\t\t\t\tdata-hint-no-icon\n\t\t\t\t\t\t\t\t\t\t\t></span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t", "\n\n\t\t\t</div>\n\t\t"])), main_core.Text.getRandom(), main_core.Text.encode(sprintData.name), this.renderGoal(sprintData.info.sprintGoal), this.renderEpics(sprintData.epics), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_TIME_ROW_LABEL'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DATE_START_LABEL_MSGVER_1'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DATE_END_LABEL'), this.renderLabelPeriodDays(periodDays), this.getFormattedDateStart(sprintData.dateStart), this.getFormattedDateStart(sprintData.dateEnd), this.renderPeriodDays(periodDays), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PLAN_ROW_LABEL'), main_core.Text.encode(storyPoints), main_core.Text.encode(storyPoints), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PLAN_SP'), main_core.Loc.getMessage('TSS_START_STORY_POINTS_HINT'), this.renderWheelCompletedStoryPoints(sprintData), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DONE_SP'), main_core.Loc.getMessage('TSS_START_STORY_POINTS_HINT'), this.renderUncompletedTasks(sprintData));
	      this.initHints(this.node);
	      return this.node;
	    }
	  }, {
	    key: "renderLabelPeriodDays",
	    value: function renderLabelPeriodDays(periodDays) {
	      if (main_core.Type.isNull(periodDays)) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PERIOD_LABEL'));
	    }
	  }, {
	    key: "renderPeriodDays",
	    value: function renderPeriodDays(periodDays) {
	      if (main_core.Type.isNull(periodDays)) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Text.encode(periodDays));
	    }
	  }, {
	    key: "renderGoal",
	    value: function renderGoal(goal) {
	      if (goal === '') {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-block\">\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-description\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(goal));
	    }
	  }, {
	    key: "renderEpics",
	    value: function renderEpics(epics) {
	      var _this6 = this;
	      if (!epics.length) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-row\">\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-content\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_EPICS_ROW_LABEL'), epics.map(function (epic) {
	        var colorBorder = _this6.convertHexToRGBA(epic.color, 0.7);
	        var colorBackground = _this6.convertHexToRGBA(epic.color, 0.3);
	        return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tclass=\"tasks-scrum__epic-label\"\n\t\t\t\t\t\t\t\t\tstyle=\"background: ", "; border-color: ", ";\"\n\t\t\t\t\t\t\t\t>", "</span>"])), colorBackground, colorBorder, main_core.Text.encode(epic.name));
	      }));
	    }
	  }, {
	    key: "renderWheelCompletedStoryPoints",
	    value: function renderWheelCompletedStoryPoints(sprintData) {
	      var differencePercentage = 0;
	      var currentPercentage = this.calculatePercentage(sprintData.storyPoints, sprintData.completedStoryPoints);
	      if (sprintData.existsLastSprint) {
	        var lastPercentage = this.calculatePercentage(sprintData.lastStoryPoints, sprintData.lastCompletedStoryPoints);
	        differencePercentage = parseFloat(currentPercentage) - parseFloat(lastPercentage);
	      } else {
	        differencePercentage = currentPercentage;
	      }
	      var wheelClass = '';
	      if (differencePercentage > 0) {
	        wheelClass = "tasks-scrum__side-panel-completion--plan-block-number --arrow-up --percent --success";
	      } else {
	        wheelClass = "tasks-scrum__side-panel-completion--plan-block-number --arrow-down --percent --warning";
	      }
	      var absoluteValue = Math.abs(differencePercentage);
	      var renderProgress = function renderProgress(percent) {
	        if (percent === 0) {
	          return '';
	        }
	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--progress\">\n\t\t\t\t\t<span class=\"tasks-scrum__side-panel-completion--progress-number\">", "</span>\n\t\t\t\t\t<span class=\"tasks-scrum__side-panel-completion--progress-percent\">%</span></div>\n\t\t\t\t</div>\n\t\t\t"])), percent);
	      };
	      return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"", "\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"tasks-scrum__side-panel-completion--plan-block-number-date\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), wheelClass, main_core.Text.encode(sprintData.completedStoryPoints), main_core.Text.encode(sprintData.completedStoryPoints), renderProgress(absoluteValue));
	    }
	  }, {
	    key: "renderUncompletedTasks",
	    value: function renderUncompletedTasks(sprintData) {
	      var _this7 = this;
	      if (sprintData.uncompletedTasks.length === 0) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-completion--block\">\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic\">\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-block\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-title-icon\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-basic-description\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-items\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_ACTION_ROW_LABEL'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_ACTION_MOVE_LABEL'), this.renderMoveSelect(sprintData.plannedSprints), sprintData.uncompletedTasks.map(function (item) {
	        return _this7.renderItem(item);
	      }));
	    }
	  }, {
	    key: "renderMoveSelect",
	    value: function renderMoveSelect(plannedSprints) {
	      var uiClasses = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';
	      var sprintsOptions = '';
	      plannedSprints.forEach(function (sprint) {
	        sprintsOptions += "<option value=\"".concat(sprint.id, "\">").concat(main_core.Text.encode(sprint.name), "</option>");
	      });
	      return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__side-panel-completion--info-select ", "\">\n\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t<select class=\"ui-ctl-element\">\n\t\t\t\t\t<option value=\"0\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</option>\n\t\t\t\t\t<option value=\"backlog\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</option>\n\t\t\t\t\t", "\n\t\t\t\t</select>\n\t\t\t</div>\n\t\t"])), uiClasses, main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_SELECTOR_SPRINT'), main_core.Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_SELECTOR_BACKLOG'), sprintsOptions);
	    }
	  }, {
	    key: "renderItem",
	    value: function renderItem(item) {
	      var _this8 = this;
	      var src = item.responsible.photo ? main_core.Text.encode(item.responsible.photo.src) : null;
	      var photoStyle = src ? "background-image: url('".concat(encodeURI(src), "');") : '';
	      var storyPointsClass = item.storyPoints === '' ? '--empty' : '';
	      var node = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item-side-panel\">\n\t\t\t\t<div class=\"tasks-scrum__item-side-panel--info\">\n\t\t\t\t\t<div class=\"tasks-scrum__item-side-panel--main-info\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__item-side-panel--title\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"tasks-scrum__item-side-panel--tags\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__item-side-panel--responsible\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"tasks-scrum__item-side-panel--responsible-photo ui-icon ui-icon-common-user\"\n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t><i style=\"", "\"></i>\n\t\t\t\t\t</div>\n\t\t\t\t\t<span>", "</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__item-side-panel--story-points ", "\">\n\t\t\t\t\t<div class=\"tasks-scrum__item-side-panel--story-points-content\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__item-side-panel--story-points-element\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(item.name), this.renderEpic(item.epic), this.renderTags(item.tags), main_core.Text.encode(item.responsible.name), photoStyle, main_core.Text.encode(item.responsible.name), storyPointsClass, item.storyPoints === '' ? '-' : main_core.Text.encode(item.storyPoints));
	      main_core.Event.bind(node, 'click', function () {
	        return _this8.emit('taskClick', item.sourceId);
	      });
	      return node;
	    }
	  }, {
	    key: "renderEpic",
	    value: function renderEpic(epic) {
	      if (main_core.Type.isArray(epic) || main_core.Type.isUndefined(epic)) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__item-side-panel--epic --visible\">\n\t\t\t\t<i\n\t\t\t\t\tclass=\"tasks-scrum__item-side-panel--epic-point\"\n\t\t\t\t\tstyle=\"", "\"\n\t\t\t\t></i>\n\t\t\t\t<span>", "</span>\n\t\t\t</div>\n\t\t"])), "background-color: ".concat(epic.color), main_core.Text.encode(epic.name));
	    }
	  }, {
	    key: "renderTags",
	    value: function renderTags(tags) {
	      if (tags.length === 0) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t"])), tags.map(function (tag) {
	        return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tasks-scrum__item-side-panel--hashtag --visible\">#", "</div>\n\t\t\t"])), main_core.Text.encode(tag));
	      }));
	    }
	  }, {
	    key: "getFormattedDateStart",
	    value: function getFormattedDateStart(dateStart) {
	      /* eslint-disable */
	      return BX.date.format(Culture.getInstance().getLongDateFormat(), dateStart);
	      /* eslint-enable */
	    }
	  }, {
	    key: "getFormattedDateEnd",
	    value: function getFormattedDateEnd(dateEnd) {
	      /* eslint-disable */
	      return BX.date.format(Culture.getInstance().getLongDateFormat(), dateEnd);
	      /* eslint-enable */
	    }
	  }, {
	    key: "getPeriodDays",
	    value: function getPeriodDays(dateStartTime) {
	      var dateWithWeekendOffset = new Date();
	      dateWithWeekendOffset.setSeconds(dateWithWeekendOffset.getSeconds());
	      dateWithWeekendOffset.setHours(0, 0, 0, 0);
	      var dateStart = new Date(dateStartTime * 1000);
	      var date = new Date();
	      if (dateStart >= date) {
	        return null;
	      }
	      return BX.date.format('ddiff', dateStart, dateWithWeekendOffset);
	    }
	  }, {
	    key: "calculatePercentage",
	    value: function calculatePercentage(first, second) {
	      if (first === 0) {
	        return 0;
	      }
	      var result = Math.round(second * 100 / first);
	      return isNaN(result) ? 0 : result;
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
	  return SprintCompletionForm;
	}(main_core_events.EventEmitter);

	exports.SprintCompletionForm = SprintCompletionForm;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX.Event,BX.UI.SidePanel,BX.UI,BX,BX.UI.Dialogs));
//# sourceMappingURL=sprint.completion.form.bundle.js.map
