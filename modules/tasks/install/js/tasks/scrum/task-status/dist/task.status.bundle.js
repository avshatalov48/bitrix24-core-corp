this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_notification,tasks_scrum_dod,main_core,ui_dialogs_messagebox) {
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
	    key: "needUpdateTask",
	    value: function needUpdateTask(data) {
	      return this.sendRequest('task', 'needUpdateTaskStatus', data);
	    }
	  }, {
	    key: "getTasks",
	    value: function getTasks(data) {
	      return this.sendRequest('task', 'getTasks', data);
	    }
	  }, {
	    key: "completeTask",
	    value: function completeTask(data) {
	      return this.sendRequest('task', 'completeTask', data);
	    }
	  }, {
	    key: "renewTask",
	    value: function renewTask(data) {
	      return this.sendRequest('task', 'renewTask', data);
	    }
	  }, {
	    key: "proceedParentTask",
	    value: function proceedParentTask(data) {
	      return this.sendRequest('task', 'proceedParentTask', data);
	    }
	  }, {
	    key: "isParentScrumTask",
	    value: function isParentScrumTask(data) {
	      return this.sendRequest('task', 'isParentScrumTask', data);
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
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TST_ERROR_POPUP_TITLE');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var TaskStatus = /*#__PURE__*/function () {
	  function TaskStatus(state) {
	    babelHelpers.classCallCheck(this, TaskStatus);
	    this.setState(state);
	    this.requestSender = new RequestSender();
	  }

	  babelHelpers.createClass(TaskStatus, [{
	    key: "setState",
	    value: function setState(state) {
	      this.groupId = parseInt(state.groupId, 10);
	      this.parentTaskId = parseInt(state.parentTaskId, 10);
	      this.taskId = parseInt(state.taskId, 10);
	      this.action = state.action === TaskStatus.actions.complete ? TaskStatus.actions.complete : TaskStatus.actions.renew;
	      this.performActionOnParentTask = main_core.Type.isUndefined(state.performActionOnParentTask) ? false : state.performActionOnParentTask;
	    }
	  }, {
	    key: "update",
	    value: function update() {
	      var _this = this;

	      return this.requestSender.needUpdateTask({
	        taskId: this.parentTaskId,
	        action: this.action
	      }).then(function (response) {
	        return response.data === true;
	      }).then(function (needUpdate) {
	        if (needUpdate) {
	          return _this.requestSender.getTasks({
	            groupId: _this.groupId,
	            taskIds: [_this.parentTaskId, _this.taskId]
	          }).then(function (response) {
	            var tasks = response.data;
	            return _this.showMessage(tasks[_this.parentTaskId], tasks[_this.taskId]);
	          });
	        } else {
	          return TaskStatus.actions.skip;
	        }
	      }).then(function (response) {
	        if (_this.performActionOnParentTask) {
	          switch (response) {
	            case TaskStatus.actions.complete:
	              return _this.completeTask(_this.parentTaskId).then(function () {
	                ui_notification.UI.Notification.Center.notify({
	                  content: main_core.Loc.getMessage('TST_PARENT_COMPLETE_NOTIFY')
	                });
	                return response;
	              });

	            case TaskStatus.actions.renew:
	              return _this.renewTask(_this.parentTaskId).then(function () {
	                ui_notification.UI.Notification.Center.notify({
	                  content: main_core.Loc.getMessage('TST_PARENT_RENEW_NOTIFY')
	                });
	                return response;
	              });

	            case TaskStatus.actions.proceed:
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('TST_PARENT_PROCEED_NOTIFY')
	              });
	              return response;

	            case TaskStatus.actions.skip:
	              return response;
	          }
	        } else {
	          return response;
	        }
	      })["catch"](function (response) {
	        return _this.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "isParentScrumTask",
	    value: function isParentScrumTask(taskId) {
	      return this.requestSender.isParentScrumTask({
	        groupId: this.groupId,
	        taskId: taskId
	      }).then(function (response) {
	        return response.data === true;
	      });
	    }
	  }, {
	    key: "showMessage",
	    value: function showMessage(parentTask, task) {
	      var _this2 = this;

	      return new Promise(function (resolve, reject) {
	        var isCompleteAction = _this2.action === TaskStatus.actions.complete;
	        new ui_dialogs_messagebox.MessageBox({
	          minWidth: 300,
	          message: isCompleteAction ? main_core.Loc.getMessage('TST_PARENT_COMPLETE_MESSAGE').replace(/#name#/g, main_core.Text.encode(parentTask.name)) : main_core.Loc.getMessage('TST_PARENT_RENEW_MESSAGE').replace("#name#", main_core.Text.encode(parentTask.name)).replace("#sub-name#", main_core.Text.encode(task.name)),
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	          okCaption: isCompleteAction ? main_core.Loc.getMessage('TST_PARENT_COMPLETE_OK_CAPTION') : main_core.Loc.getMessage('TST_PARENT_RENEW_OK_CAPTION'),
	          cancelCaption: isCompleteAction ? main_core.Loc.getMessage('TST_PARENT_PROCEED_CAPTION') : main_core.Loc.getMessage('TST_PARENT_RENEW_CANCEL_CAPTION'),
	          onOk: function onOk(messageBox) {
	            if (isCompleteAction) {
	              _this2.showDod(_this2.parentTaskId).then(function () {
	                messageBox.close();
	                resolve(TaskStatus.actions.complete);
	              })["catch"](function () {
	                messageBox.getOkButton().setDisabled(false);
	              });
	            } else {
	              messageBox.close();
	              resolve(TaskStatus.actions.renew);
	            }
	          },
	          onCancel: function onCancel(messageBox) {
	            messageBox.close();

	            if (isCompleteAction) {
	              _this2.proceedParentTask(_this2.parentTaskId).then(function () {
	                resolve(TaskStatus.actions.proceed);
	              });
	            } else {
	              resolve(TaskStatus.actions.skip);
	            }
	          }
	        }).show();
	      });
	    }
	  }, {
	    key: "showDod",
	    value: function showDod(taskId) {
	      var _this3 = this;

	      return new Promise(function (resolve, reject) {
	        var dod = new tasks_scrum_dod.Dod({
	          groupId: _this3.groupId,
	          taskId: taskId
	        });
	        dod.subscribe('resolve', function () {
	          return resolve();
	        });
	        dod.subscribe('reject', function () {
	          return reject();
	        });
	        dod.isNecessary().then(function (isNecessary) {
	          if (isNecessary) {
	            dod.showList();
	          } else {
	            resolve();
	          }
	        });
	      });
	    }
	  }, {
	    key: "completeTask",
	    value: function completeTask(taskId) {
	      return this.requestSender.completeTask({
	        groupId: this.groupId,
	        taskId: taskId
	      });
	    }
	  }, {
	    key: "renewTask",
	    value: function renewTask(taskId) {
	      return this.requestSender.renewTask({
	        groupId: this.groupId,
	        taskId: taskId
	      });
	    }
	  }, {
	    key: "proceedParentTask",
	    value: function proceedParentTask(taskId) {
	      return this.requestSender.proceedParentTask({
	        groupId: this.groupId,
	        taskId: taskId
	      });
	    }
	  }]);
	  return TaskStatus;
	}();
	babelHelpers.defineProperty(TaskStatus, "actions", {
	  complete: 'complete',
	  renew: 'renew',
	  proceed: 'proceed',
	  skip: 'skip'
	});

	exports.TaskStatus = TaskStatus;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Tasks.Scrum,BX,BX.UI.Dialogs));
//# sourceMappingURL=task.status.bundle.js.map
