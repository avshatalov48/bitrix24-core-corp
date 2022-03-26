this.BX = this.BX || {};
(function (exports,main_core,rest_client) {
	'use strict';

	var CommentActionController = /*#__PURE__*/function () {
	  function CommentActionController() {
	    babelHelpers.classCallCheck(this, CommentActionController);
	  }

	  babelHelpers.createClass(CommentActionController, null, [{
	    key: "init",
	    value: function init() {
	      var parameters = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new Promise(function (resolve) {
	        var promisesToResolve = [];

	        if (!CommentActionController.workHours) {
	          if (parameters.workHours) {
	            CommentActionController.workHours = parameters.workHours;
	          } else {
	            promisesToResolve.push(CommentActionController.loadWorkHours());
	          }
	        }

	        if (!CommentActionController.workSettings) {
	          if (parameters.workSettings) {
	            CommentActionController.workSettings = parameters.workSettings;
	          } else {
	            promisesToResolve.push(CommentActionController.loadWorkSettings());
	          }
	        }

	        if (!promisesToResolve.length) {
	          resolve();
	        }

	        Promise.all(promisesToResolve).then(function () {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "loadWorkHours",
	    value: function loadWorkHours() {
	      return new Promise(function (resolve) {
	        rest_client.rest.callMethod('calendar.settings.get').then(function (response) {
	          var result = response.answer.result;
	          var work_time_start = result.work_time_start,
	              work_time_end = result.work_time_end;

	          var _work_time_start$spli = work_time_start.split('.'),
	              _work_time_start$spli2 = babelHelpers.slicedToArray(_work_time_start$spli, 2),
	              startHours = _work_time_start$spli2[0],
	              startMinutes = _work_time_start$spli2[1];

	          var _work_time_end$split = work_time_end.split('.'),
	              _work_time_end$split2 = babelHelpers.slicedToArray(_work_time_end$split, 2),
	              endHours = _work_time_end$split2[0],
	              endMinutes = _work_time_end$split2[1];

	          CommentActionController.workHours = {
	            start: {
	              hours: startHours,
	              minutes: startMinutes
	            },
	            end: {
	              hours: endHours,
	              minutes: endMinutes
	            }
	          };
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "loadWorkSettings",
	    value: function loadWorkSettings() {
	      return new Promise(function (resolve) {
	        main_core.ajax.runAction('tasks.userOption.getCalendarTimeVisibilityOption').then(function (response) {
	          CommentActionController.workSettings = {
	            deadlineTimeVisibility: response.data.visibility || 'N'
	          };
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "isActionValid",
	    value: function isActionValid(action) {
	      return Object.keys(CommentActionController.possibleActions).includes(action);
	    }
	  }, {
	    key: "processLink",
	    value: function processLink(link) {
	      var _link$matches = babelHelpers.slicedToArray(link.matches, 5),
	          url = _link$matches[0],
	          userId = _link$matches[1],
	          taskId = _link$matches[2],
	          action = _link$matches[3],
	          deadline = _link$matches[4];

	      if (!CommentActionController.isActionValid(action)) {
	        return;
	      }

	      if (action === CommentActionController.possibleActions.deadlineChange) {
	        CommentActionController.init().then(function () {
	          CommentActionController.showDeadlinePicker(link.anchor, taskId, deadline);
	        });
	        return;
	      }

	      CommentActionController.checkCanRun(action, taskId).then(function (response) {
	        if (response) {
	          CommentActionController.runAjaxAction(action, taskId);
	        }
	      }, function (response) {
	        return console.error(response);
	      });
	    }
	  }, {
	    key: "showDeadlinePicker",
	    value: function showDeadlinePicker(target, taskId, deadline) {
	      var now = new Date();
	      var today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), CommentActionController.workHours.end.hours, CommentActionController.workHours.end.minutes));
	      var value = deadline ? new Date((Number(deadline) - new Date().getTimezoneOffset() * 60) * 1000) : today;
	      BX.calendar({
	        node: target,
	        value: value,
	        field: '',
	        form: '',
	        bTime: true,
	        currentTime: Math.round(new Date() / 1000) - new Date().getTimezoneOffset() * 60,
	        bHideTimebar: true,
	        bCompatibility: true,
	        bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
	        bTimeVisibility: CommentActionController.workSettings ? CommentActionController.workSettings.deadlineTimeVisibility === 'Y' : false,
	        callback_after: function callback_after(value) {
	          return CommentActionController.onDeadlinePicked(value, taskId);
	        }
	      });
	    }
	  }, {
	    key: "onDeadlinePicked",
	    value: function onDeadlinePicked(value, taskId) {
	      var action = CommentActionController.possibleActions.deadlineChange;
	      CommentActionController.checkCanRun(action, taskId).then(function (response) {
	        if (response) {
	          CommentActionController.runAjaxAction(action, taskId, {
	            fields: {
	              DEADLINE: value.toISOString()
	            }
	          });
	        }
	      }, function (response) {
	        return console.error(response);
	      });
	    }
	  }, {
	    key: "checkCanRun",
	    value: function checkCanRun(action, taskId) {
	      return new Promise(function (resolve, reject) {
	        if (CommentActionController.isAjaxRunning) {
	          resolve(false);
	        }

	        CommentActionController.isAjaxRunning = true;
	        main_core.ajax.runAction('tasks.task.getAccess', {
	          data: {
	            taskId: taskId
	          }
	        }).then(function (response) {
	          CommentActionController.isAjaxRunning = false;
	          var allowedActions = response.data.allowedActions;
	          var userId = Object.keys(allowedActions)[0];
	          var accessAction = CommentActionController.accessActions[action];
	          resolve(allowedActions && allowedActions[userId] && allowedActions[userId][accessAction]);
	        }, function (response) {
	          return reject(response);
	        });
	      });
	    }
	  }, {
	    key: "runAjaxAction",
	    value: function runAjaxAction(action, taskId) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

	      if (CommentActionController.isAjaxRunning) {
	        return;
	      }

	      CommentActionController.isAjaxRunning = true;

	      if (action !== 'taskComplete') {
	        CommentActionController.showNotification(action);
	      }

	      var defaultData = {
	        taskId: taskId
	      };
	      data = babelHelpers.objectSpread({}, data, defaultData);

	      if (!data.params) {
	        data.params = {};
	      }

	      data.params.PLATFORM = 'web';
	      main_core.ajax.runAction(CommentActionController.ajaxActions[action], {
	        data: data
	      }).then(function () {
	        if (action === 'taskComplete') {
	          CommentActionController.showNotification(action);
	        }

	        CommentActionController.isAjaxRunning = false;
	      }, function (response) {
	        if (response && response.errors) {
	          var errorMsg = {
	            MESSAGE: response.errors[0].message,
	            DATA: {
	              ui: 'notification'
	            }
	          };
	          BX.Tasks.alert([errorMsg]);
	        }

	        CommentActionController.isAjaxRunning = false;
	      });
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(action) {
	      main_core.Runtime.loadExtension('ui.notification').then(function () {
	        BX.UI.Notification.Center.notify({
	          content: CommentActionController.actionNotificationMessages[action]
	        });
	      });
	    }
	  }, {
	    key: "possibleActions",
	    get: function get() {
	      return {
	        deadlineChange: 'deadlineChange',
	        taskApprove: 'taskApprove',
	        taskDisapprove: 'taskDisapprove',
	        taskComplete: 'taskComplete'
	      };
	    }
	  }, {
	    key: "accessActions",
	    get: function get() {
	      return {
	        deadlineChange: 'CHANGE_DEADLINE',
	        taskApprove: 'APPROVE',
	        taskDisapprove: 'DISAPPROVE',
	        taskComplete: 'COMPLETE'
	      };
	    }
	  }, {
	    key: "ajaxActions",
	    get: function get() {
	      return {
	        deadlineChange: 'tasks.task.update',
	        taskApprove: 'tasks.task.approve',
	        taskDisapprove: 'tasks.task.disapprove',
	        taskComplete: 'tasks.task.complete'
	      };
	    }
	  }, {
	    key: "actionNotificationMessages",
	    get: function get() {
	      var prefix = 'TASKS_COMMENT_ACTION_CONTROLLER_NOTIFICATION';
	      return {
	        deadlineChange: main_core.Loc.getMessage("".concat(prefix, "_DEADLINE_CHANGE")),
	        taskApprove: main_core.Loc.getMessage("".concat(prefix, "_TASK_APPROVE")),
	        taskDisapprove: main_core.Loc.getMessage("".concat(prefix, "_TASK_DISAPPROVE")),
	        taskComplete: main_core.Loc.getMessage("".concat(prefix, "_TASK_COMPLETE"))
	      };
	    }
	  }]);
	  return CommentActionController;
	}();

	babelHelpers.defineProperty(CommentActionController, "workHours", null);
	babelHelpers.defineProperty(CommentActionController, "workSettings", null);
	babelHelpers.defineProperty(CommentActionController, "isAjaxRunning", false);

	exports.CommentActionController = CommentActionController;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX));
//# sourceMappingURL=comment-action-controller.bundle.js.map
