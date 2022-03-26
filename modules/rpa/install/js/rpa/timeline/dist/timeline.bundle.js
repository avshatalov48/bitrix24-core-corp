this.BX = this.BX || {};
(function (exports,main_core_events,rpa_manager,main_popup,main_core,ui_timeline) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18;
	/**
	 * @memberOf BX.Rpa.Timeline
	 * @mixes EventEmitter
	 */

	var Task = /*#__PURE__*/function (_Timeline$Item) {
	  babelHelpers.inherits(Task, _Timeline$Item);

	  function Task(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Task);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Task).call(this, props));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "statusWait", 0);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "statusYes", 1);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "statusNo", 2);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "statusOk", 3);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "statusCancel", 4);

	    _this.setEventNamespace('BX.Rpa.Timeline.Task');

	    return _this;
	  }

	  babelHelpers.createClass(Task, [{
	    key: "getId",
	    value: function getId() {
	      return 'task-' + this.id;
	    }
	  }, {
	    key: "getTaskUsers",
	    value: function getTaskUsers() {
	      return this.data.users;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.layout.container = this.renderContainer();
	      this.layout.container.appendChild(this.renderIcon());
	      this.layout.container.appendChild(this.renderContent());
	      return this.layout.container;
	    }
	  }, {
	    key: "renderContainer",
	    value: function renderContainer() {
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-stream-section ui-item-detail-stream-section-task ", "\"></div>"])), this.isLast ? 'ui-item-detail-stream-section-last' : '');
	    }
	  }, {
	    key: "renderHeader",
	    value: function renderHeader() {
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-item-detail-stream-content-header\">\n\t\t\t\t<div class=\"ui-item-detail-stream-content-title\">\n\t\t\t\t\t<span class=\"ui-item-detail-stream-content-title-text\">", "</span>\n\t\t\t\t</div>\n\t\t\t</div>"])), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_TITLE'));
	    }
	  }, {
	    key: "renderMain",
	    value: function renderMain() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-item-detail-stream-content-detail\">\n\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-subject\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-subject-inner\">\n\t\t\t\t\t\t<a class=\"ui-item-detail-stream-content-detail-subject-text\" href=\"", "\">", "</a>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-main\">\n\t\t\t\t\t<span class=\"ui-item-detail-stream-content-detail-main-text\">", "</span>\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"])), this.renderParticipants(), main_core.Text.encode(this.data.url), main_core.Text.encode(this.getTitle()), this.renderParticipantsLine(), main_core.Text.encode(this.description), this.renderTaskFields(), this.renderTaskButtons());
	    }
	  }, {
	    key: "renderParticipants",
	    value: function renderParticipants() {
	      var _this2 = this;

	      var photos = this.getTaskUsers().map(function (_ref) {
	        var id = _ref.id,
	            status = _ref.status;
	        return _this2.renderParticipantPhoto(id);
	      });

	      if (photos.length > 4) {
	        var counter = photos.length - 4;
	        photos = photos.slice(0, 4);
	        photos.push(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-other\">\n\t\t\t\t\t\t<span class=\"ui-item-detail-stream-content-other-text\">+", "</span>\n\t\t\t\t\t</span>"])), counter));
	      }

	      return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-stream-content-employee-wrap\" onclick=\"", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.showParticipants.bind(this), photos);
	    }
	  }, {
	    key: "renderParticipantsLine",
	    value: function renderParticipantsLine() {
	      var _this3 = this;

	      var elements = [];
	      var taskUsers = this.getTaskUsers();
	      taskUsers.forEach(function (_ref2, i) {
	        var id = _ref2.id,
	            status = _ref2.status;
	        var node = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-detail-subject-resp\">", "</span>"])), main_core.Text.encode(_this3.getTaskUserName(id)));

	        if (status > _this3.statusWait) {
	          node.classList.add('ui-item-detail-stream-content-detail-subject-resp-past');
	        } else if (id === _this3.getUserId()) {
	          node.classList.add('ui-item-detail-stream-content-detail-subject-resp-current');
	        }

	        elements.push(node);

	        if (_this3.data.participantJoint !== 'queue' && taskUsers.length - 1 !== i) {
	          var msg = _this3.data.participantJoint === 'and' ? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR';
	          elements.push(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-detail-subject-separator\">", "</span>"])), main_core.Loc.getMessage(msg)));
	        }
	      });
	      var queueCls = this.data.participantJoint === 'queue' ? 'ui-item-detail-stream-content-detail-subject-resp-wrap-queue' : '';
	      return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-item-detail-stream-content-detail-subject-resp-wrap ", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), queueCls, elements);
	    }
	  }, {
	    key: "showParticipants",
	    value: function showParticipants(event) {
	      var _this4 = this;

	      var taskUsers = this.getTaskUsers();
	      var users = taskUsers.map(function (_ref3, i) {
	        var id = _ref3.id,
	            status = _ref3.status;

	        var user = _this4.users.get(id);

	        var sep = taskUsers.length - 1 !== i ? main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-popup-item-separator\">\n\t\t\t\t\t", "\n\t\t\t\t\t</span>"])), main_core.Loc.getMessage(_this4.data.participantJoint === 'and' ? 'RPA_TIMELINE_TASKS_SEPARATOR_AND' : 'RPA_TIMELINE_TASKS_SEPARATOR_OR')) : '';
	        var node = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-popup-item\">\n\t\t\t\t\t<a class=\"ui-item-detail-stream-content-employee\"\n\t\t\t\t\t   ", "\n\t\t\t\t\t   target=\"_blank\"\n\t\t\t\t\t   title=\"", "\"\n\t\t\t\t\t   ", "></a>\n\t\t\t\t\t<div class=\"ui-item-detail-popup-item-inner\">\n\t\t\t\t\t\t<span class=\"ui-item-detail-popup-item-name\">", "</span>\n\t\t\t\t\t\t<span class=\"ui-item-detail-popup-item-position\">", "</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>"])), user.link ? "href=\"".concat(user.link, "\"") : '', main_core.Text.encode(user.fullName), user.photo ? "style=\"background-image: url('".concat(user.photo, "'); background-size: 100%;\"") : '', main_core.Text.encode(user.fullName), user.workPosition, sep);

	        if (status > _this4.statusWait) {
	          node.classList.add('ui-item-detail-popup-item-' + (status === _this4.statusOk || status === _this4.statusYes ? 'success' : 'fail'));
	        } else {
	          node.classList.add('ui-item-detail-popup-item-' + (id === _this4.getUserId() ? 'current' : 'wait'));
	        }

	        return node;
	      });
	      var content = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-item-detail-popup\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>"])), users);

	      if (this.data.participantJoint !== 'queue') {
	        content.classList.add('ui-item-detail-popup-option');
	      }

	      var popup = new main_popup.Popup('rpa-detail-task-participant-' + this.getId(), event.target, {
	        autoHide: true,
	        draggable: false,
	        bindOptions: {
	          forceBindPosition: true
	        },
	        noAllPaddings: true,
	        closeByEsc: true,
	        cacheable: false,
	        width: 280,
	        angle: {
	          position: 'top'
	        },
	        overlay: {
	          backgroundColor: 'transparent'
	        },
	        content: content
	      });
	      popup.show();
	    }
	  }, {
	    key: "renderTaskButtons",
	    value: function renderTaskButtons() {
	      var controls = this.data.controls;

	      if (!controls) {
	        return '';
	      }

	      var elements = this.data.type === 'RpaRequestActivity' ? this.getLinkButtonElements(controls.BUTTONS) : this.getActionButtonElements(controls.BUTTONS);
	      return main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-item-detail-stream-content-detail-status-block\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), elements);
	    }
	  }, {
	    key: "getActionButtonElements",
	    value: function getActionButtonElements(buttons) {
	      var _this5 = this;

	      return buttons.map(function (button) {
	        var bgColor = button.COLOR;
	        var fgColor = rpa_manager.Manager.calculateTextColor(button.COLOR);
	        return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["<button class=\"ui-btn ui-btn-sm ui-btn-default\" \n\t\t\t\t\tname=\"", "\"\n\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\tstyle=\"background-color: #", ";border-color: #", ";color:", "\"\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t>", "</button>\n\t\t\t\t"])), button.NAME, button.VALUE, bgColor, bgColor, fgColor, _this5.doTaskHandler.bind(_this5, button), main_core.Text.encode(button.TEXT));
	      });
	    }
	  }, {
	    key: "getLinkButtonElements",
	    value: function getLinkButtonElements(buttons) {
	      return [main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["<a class=\"ui-btn ui-btn-sm ui-btn-default ui-btn-primary\" \n\t\t\t\t\thref=\"", "\"\n\t\t\t\t\t>", "</a>\n\t\t\t"])), main_core.Text.encode(this.data.url), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_OPEN_TASK'))];
	    }
	  }, {
	    key: "renderTaskFields",
	    value: function renderTaskFields() {
	      if (!this.data.fieldsToSet) {
	        return '';
	      }

	      var elements = this.data.fieldsToSet.map(function (field) {
	        return main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-main-field-value\">&ndash; ", "</div>\n\t\t\t\t"])), main_core.Text.encode(field));
	      });
	      return main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-item-detail-stream-content-detail-main-field\">\n\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-main-field-title\">", "</div>\n\t\t\t\t<div class=\"ui-item-detail-stream-content-detail-main-field-value-block\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\t\t\n\t\t"])), main_core.Loc.getMessage('RPA_TIMELINE_TASKS_FIELDS_TO_SET'), elements);
	    }
	  }, {
	    key: "getTaskUserName",
	    value: function getTaskUserName(id) {
	      if (!id) {
	        id = this.getUserId();
	      }

	      var userData = this.users.get(main_core.Text.toInteger(id));
	      return userData ? userData.fullName : '-?-';
	    }
	  }, {
	    key: "renderParticipantPhoto",
	    value: function renderParticipantPhoto(userId) {
	      userId = main_core.Text.toInteger(userId);
	      var userData = {
	        fullName: '',
	        photo: null
	      };

	      if (userId > 0) {
	        userData = this.users.get(userId);
	      }

	      if (!userData) {
	        return main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	      }

	      var safeFullName = main_core.Text.encode(userData.fullName);
	      return main_core.Tag.render(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-employee\" title=\"", "\" ", "></span>"])), safeFullName, userData.photo ? "style=\"background-image: url('".concat(userData.photo, "'); background-size: 100%;\"") : '');
	    }
	  }, {
	    key: "doTaskHandler",
	    value: function doTaskHandler(button) {
	      var _this6 = this;

	      var ajaxData = {};
	      ajaxData[button.NAME] = button.VALUE;
	      ajaxData['taskId'] = this.id;
	      this.emit('onBeforeCompleteTask', {
	        taskId: this.id
	      });
	      main_core.ajax.runAction('rpa.task.do', {
	        analyticsLabel: 'rpaTaskDo',
	        data: ajaxData
	      }).then(function (response) {
	        if (response.data.completed) {
	          if (response.data.timeline) {
	            _this6.completedData = response.data.timeline;
	          }

	          _this6.onDelete();

	          _this6.emit('onCompleteTask', {
	            taskId: _this6.id
	          });
	        }
	      });
	    }
	  }]);
	  return Task;
	}(ui_timeline.Timeline.Item);

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1;
	var TaskComplete = /*#__PURE__*/function (_Timeline$History) {
	  babelHelpers.inherits(TaskComplete, _Timeline$History);

	  function TaskComplete() {
	    babelHelpers.classCallCheck(this, TaskComplete);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TaskComplete).apply(this, arguments));
	  }

	  babelHelpers.createClass(TaskComplete, [{
	    key: "renderContainer",
	    value: function renderContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(TaskComplete.prototype), "renderContainer", this).call(this);
	      container.classList.add('ui-item-detail-stream-section-history');
	      return container;
	    }
	  }, {
	    key: "renderTaskInfo",
	    value: function renderTaskInfo() {
	      var taskName = this.renderTaskName();

	      if (!taskName) {
	        taskName = '';
	      }

	      var taskResponsible = this.renderTaskResponsible();

	      if (!taskResponsible) {
	        taskResponsible = '';
	      }

	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-stream-content-detail-subject\">\n\t\t\t", "\n\t\t\t<div class=\"ui-item-detail-stream-content-detail-subject-inner\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t</div>"])), this.renderHeaderUser(this.getUserId(), 30), taskName, taskResponsible);
	    }
	  }, {
	    key: "renderTaskName",
	    value: function renderTaskName() {
	      var task = this.getTask();

	      if (task) {
	        return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<a class=\"ui-item-detail-stream-content-detail-subject-text\">", "</a>"])), main_core.Text.encode(task.NAME));
	      }

	      return null;
	    }
	  }, {
	    key: "renderTaskResponsible",
	    value: function renderTaskResponsible() {
	      var user = this.users.get(main_core.Text.toInteger(this.getUserId()));

	      if (user) {
	        return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-detail-subject-resp\">", "</span>"])), main_core.Text.encode(user.fullName));
	      }

	      return null;
	    }
	  }, {
	    key: "renderMain",
	    value: function renderMain() {
	      var taskInfo = this.renderTaskInfo();
	      var detailMain = this.renderDetailMain();

	      if (!detailMain) {
	        taskInfo.classList.add('rpa-item-detail-stream-content-detail-no-main');
	      }

	      return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-stream-content-detail\">\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"])), taskInfo, detailMain || '');
	    }
	  }, {
	    key: "getTask",
	    value: function getTask() {
	      if (main_core.Type.isPlainObject(this.data.task)) {
	        return this.data.task;
	      }

	      return null;
	    }
	  }, {
	    key: "renderDetailMain",
	    value: function renderDetailMain() {
	      var task = this.getTask();
	      var taskDescription = '';

	      if (task && task.DESCRIPTION) {
	        taskDescription = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-item-detail-stream-content-detail-main-text\">", "</span>"])), main_core.Text.encode(task.DESCRIPTION));
	      }

	      var stageChange = this.renderStageChange();
	      var fieldsChange = this.renderFieldsChange();

	      if (taskDescription || stageChange || fieldsChange) {
	        return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-item-detail-stream-content-detail-main\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>"])), taskDescription, fieldsChange ? [this.renderFieldsChangeTitle(), fieldsChange] : '', stageChange ? [this.renderStageChangeTitle(), stageChange] : '');
	      }

	      return null;
	    }
	  }]);
	  return TaskComplete;
	}(ui_timeline.Timeline.History);

	/**
	 * @memberOf BX.Rpa
	 */

	var Timeline = {
	  Task: Task,
	  TaskComplete: TaskComplete
	};

	exports.Timeline = Timeline;

}((this.BX.Rpa = this.BX.Rpa || {}),BX.Event,BX.Rpa,BX.Main,BX,BX.UI));
//# sourceMappingURL=timeline.bundle.js.map
