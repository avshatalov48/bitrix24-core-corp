/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,rest_client,ui_entitySelector) {
	'use strict';

	class CommentActionController {
	  static get possibleActions() {
	    return {
	      deadlineChange: 'deadlineChange',
	      taskApprove: 'taskApprove',
	      taskDisapprove: 'taskDisapprove',
	      taskComplete: 'taskComplete',
	      taskChangeResponsible: 'taskChangeResponsible',
	      showFlowAttendees: 'showFlowAttendees'
	    };
	  }
	  static get accessActions() {
	    return {
	      deadlineChange: 'CHANGE_DEADLINE',
	      taskApprove: 'APPROVE',
	      taskDisapprove: 'DISAPPROVE',
	      taskComplete: 'COMPLETE',
	      taskChangeResponsible: 'CHANGE_RESPONSIBLE'
	    };
	  }
	  static get ajaxActions() {
	    return {
	      deadlineChange: 'tasks.task.update',
	      taskApprove: 'tasks.task.approve',
	      taskDisapprove: 'tasks.task.disapprove',
	      taskComplete: 'tasks.task.complete',
	      taskChangeResponsible: 'tasks.task.update'
	    };
	  }
	  static get actionNotificationMessages() {
	    const prefix = 'TASKS_COMMENT_ACTION_CONTROLLER_NOTIFICATION';
	    return {
	      deadlineChange: main_core.Loc.getMessage(`${prefix}_DEADLINE_CHANGE`),
	      taskApprove: main_core.Loc.getMessage(`${prefix}_TASK_APPROVE`),
	      taskDisapprove: main_core.Loc.getMessage(`${prefix}_TASK_DISAPPROVE`),
	      taskComplete: main_core.Loc.getMessage(`${prefix}_TASK_COMPLETE`),
	      taskChangeResponsible: main_core.Loc.getMessage(`${prefix}_TASK_CHANGE_RESPONSIBLE`)
	    };
	  }
	  static init(parameters = {}) {
	    return new Promise(resolve => {
	      const promisesToResolve = [];
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
	      Promise.all(promisesToResolve).then(() => resolve());
	    });
	  }
	  static loadWorkHours() {
	    return new Promise(resolve => {
	      rest_client.rest.callMethod('calendar.settings.get').then(response => {
	        const {
	          result
	        } = response.answer;
	        const [startHours, startMinutes] = String(result.work_time_start).split('.');
	        const [endHours, endMinutes] = String(result.work_time_end).split('.');
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
	  static loadWorkSettings() {
	    return new Promise(resolve => {
	      main_core.ajax.runAction('tasks.userOption.getCalendarTimeVisibilityOption').then(response => {
	        CommentActionController.workSettings = {
	          deadlineTimeVisibility: response.data.visibility || 'N'
	        };
	        resolve();
	      });
	    });
	  }
	  static isActionValid(action) {
	    return Object.keys(CommentActionController.possibleActions).includes(action);
	  }
	  static processLink(link) {
	    var _urlParams$get;
	    const [url, userId, taskId, action] = link.matches;
	    const urlParams = new URLSearchParams(link.url);
	    const [deadline, flowId, excludeMembers] = [urlParams.get('deadline'), urlParams.get('flowId'), JSON.parse((_urlParams$get = urlParams.get('excludeMembers')) != null ? _urlParams$get : '[]')];
	    if (!CommentActionController.isActionValid(action)) {
	      return;
	    }
	    switch (action) {
	      case CommentActionController.possibleActions.deadlineChange:
	        CommentActionController.init().then(() => {
	          CommentActionController.showDeadlinePicker(link.anchor, taskId, deadline);
	        });
	        return;
	      case CommentActionController.possibleActions.taskChangeResponsible:
	        CommentActionController.showResponsibleSelector(link.anchor, taskId, flowId);
	        return;
	      case CommentActionController.possibleActions.showFlowAttendees:
	        CommentActionController.showFlowAttendees(link.anchor, flowId, excludeMembers);
	        return;
	      default:
	        CommentActionController.checkCanRun(action, taskId).then(response => {
	          if (response) {
	            CommentActionController.runAjaxAction(action, taskId);
	          }
	        }, response => console.error(response));
	    }
	  }
	  static async showFlowAttendees(target, flowId, excludeMembers = []) {
	    const {
	      TeamPopup
	    } = await main_core.Runtime.loadExtension('tasks.flow.team-popup');
	    TeamPopup.showInstance({
	      flowId,
	      bindElement: target,
	      excludeMembers
	    });
	  }
	  static showDeadlinePicker(target, taskId, deadline) {
	    const now = new Date();
	    const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), CommentActionController.workHours.end.hours, CommentActionController.workHours.end.minutes));
	    const value = deadline ? new Date((Number(deadline) - new Date().getTimezoneOffset() * 60) * 1000) : today;
	    const calendar = main_core.Reflection.getClass('BX.calendar');
	    calendar({
	      node: target,
	      value,
	      field: '',
	      form: '',
	      bTime: true,
	      currentTime: Math.round(new Date() / 1000) - new Date().getTimezoneOffset() * 60,
	      bHideTimebar: true,
	      bCompatibility: true,
	      bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
	      bTimeVisibility: CommentActionController.workSettings ? CommentActionController.workSettings.deadlineTimeVisibility === 'Y' : false,
	      callback_after: value => CommentActionController.onDeadlinePicked(value, taskId)
	    });
	  }
	  static showResponsibleSelector(target, taskId, flowId = null) {
	    const entities = [{
	      id: 'department'
	    }];
	    const isFlowCorrect = flowId !== null;
	    if (isFlowCorrect) {
	      entities.unshift({
	        id: 'flow-user',
	        options: {
	          flowId
	        },
	        dynamicLoad: true
	      });
	    } else {
	      entities.unshift({
	        id: 'user',
	        options: {
	          intranetUsersOnly: true,
	          emailUsers: false,
	          inviteEmployeeLink: false,
	          inviteGuestLink: false
	        }
	      });
	    }
	    const dialog = new ui_entitySelector.Dialog({
	      targetNode: target,
	      enableSearch: true,
	      multiple: false,
	      cacheable: false,
	      dropdownMode: isFlowCorrect,
	      entities,
	      clearSearchOnSelect: true,
	      events: {
	        'Item:onSelect': event => {
	          var _event$data;
	          const item = event == null ? void 0 : (_event$data = event.data) == null ? void 0 : _event$data.item;
	          if (item) {
	            CommentActionController.onResponsibleSelected(item.id, taskId);
	          }
	          dialog.hide();
	        }
	      }
	    });
	    dialog.show();
	  }
	  static onResponsibleSelected(userId, taskId) {
	    const action = CommentActionController.possibleActions.taskChangeResponsible;
	    CommentActionController.runAjaxAction(action, taskId, {
	      fields: {
	        RESPONSIBLE_ID: userId
	      }
	    });
	  }
	  static onDeadlinePicked(value, taskId) {
	    const action = CommentActionController.possibleActions.deadlineChange;
	    CommentActionController.checkCanRun(action, taskId).then(response => {
	      if (response) {
	        CommentActionController.runAjaxAction(action, taskId, {
	          fields: {
	            DEADLINE: value.toISOString()
	          }
	        });
	      }
	    }, response => console.error(response));
	  }
	  static checkCanRun(action, taskId) {
	    return new Promise((resolve, reject) => {
	      if (CommentActionController.isAjaxRunning) {
	        resolve(false);
	      }
	      CommentActionController.isAjaxRunning = true;
	      main_core.ajax.runAction('tasks.task.getAccess', {
	        data: {
	          taskId
	        }
	      }).then(response => {
	        CommentActionController.isAjaxRunning = false;
	        const {
	          allowedActions
	        } = response.data;
	        const userId = Object.keys(allowedActions)[0];
	        const accessAction = CommentActionController.accessActions[action];
	        resolve(allowedActions && allowedActions[userId] && allowedActions[userId][accessAction]);
	      }, response => reject(response));
	    });
	  }
	  static runAjaxAction(action, taskId, data = {}) {
	    if (CommentActionController.isAjaxRunning) {
	      return;
	    }
	    CommentActionController.isAjaxRunning = true;
	    const defaultData = {
	      taskId
	    };
	    data = {
	      ...data,
	      ...defaultData
	    };
	    if (!data.params) {
	      data.params = {};
	    }
	    data.params.PLATFORM = 'web';
	    main_core.ajax.runAction(CommentActionController.ajaxActions[action], {
	      data: data
	    }).then(() => {
	      CommentActionController.showNotification(action);
	      CommentActionController.isAjaxRunning = false;
	    }, response => {
	      if (response && response.errors) {
	        const errorMsg = {
	          MESSAGE: response.errors[0].message,
	          DATA: {
	            ui: 'notification'
	          }
	        };
	        const Tasks = main_core.Reflection.getClass('BX.Tasks');
	        Tasks.alert([errorMsg]);
	      }
	      CommentActionController.isAjaxRunning = false;
	    });
	  }
	  static showNotification(action) {
	    main_core.Runtime.loadExtension('ui.notification').then(() => {
	      const notificationCenter = main_core.Reflection.getClass('BX.UI.Notification.Center');
	      notificationCenter.notify({
	        content: CommentActionController.actionNotificationMessages[action]
	      });
	    });
	  }
	}
	CommentActionController.workHours = null;
	CommentActionController.workSettings = null;
	CommentActionController.isAjaxRunning = false;

	exports.CommentActionController = CommentActionController;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX,BX.UI.EntitySelector));
//# sourceMappingURL=comment-action-controller.bundle.js.map
