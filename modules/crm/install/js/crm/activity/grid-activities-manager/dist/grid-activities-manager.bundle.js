this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	const Util = main_core.Reflection.namespace('BX.util');
	var _activityTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activityTypes");
	var _showProviderSix = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showProviderSix");
	var _showRestApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showRestApp");
	var _showCalendarSharing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCalendarSharing");
	var _showTask = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showTask");
	var _getCurrentUserId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentUserId");
	var _fetchActivity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchActivity");
	class Manager {
	  constructor() {
	    Object.defineProperty(this, _fetchActivity, {
	      value: _fetchActivity2
	    });
	    Object.defineProperty(this, _getCurrentUserId, {
	      value: _getCurrentUserId2
	    });
	    Object.defineProperty(this, _showTask, {
	      value: _showTask2
	    });
	    Object.defineProperty(this, _showCalendarSharing, {
	      value: _showCalendarSharing2
	    });
	    Object.defineProperty(this, _showRestApp, {
	      value: _showRestApp2
	    });
	    Object.defineProperty(this, _showProviderSix, {
	      value: _showProviderSix2
	    });
	    this.activityAddingPopup = {};
	  }
	  async showAddPopup(bindElement, gridManagerId, entityTypeId, entityId, currentUser, settings) {
	    main_core.Dom.addClass(bindElement, '--active');
	    const key = `${entityTypeId}_${entityId}`;
	    const exports = await main_core.Runtime.loadExtension('crm.activity.adding-popup');
	    if (!exports || !exports.AddingPopup) {
	      return;
	    }
	    if (!Object.hasOwn(this.activityAddingPopup, key)) {
	      this.activityAddingPopup[key] = new exports.AddingPopup(entityTypeId, entityId, currentUser, settings, {
	        events: {
	          onClose() {
	            BX.Dom.removeClass(bindElement, '--active');
	          },
	          onSave() {
	            const grid = BX.Main.gridManager.getById(gridManagerId);
	            if (grid) {
	              grid.instance.reload();
	            }
	          }
	        }
	      });
	    }
	    this.activityAddingPopup[key].show();
	  }
	  async viewActivity(gridId, activityId, allowEdit) {
	    try {
	      const activity = await babelHelpers.classPrivateFieldLooseBase(this, _fetchActivity)[_fetchActivity](activityId);
	      if (!activity) {
	        return;
	      }
	      if (activity.customViewLink) {
	        BX.Crm.Page.open(activity.customViewLink);
	      } else {
	        this.openActivityDialog(activity, allowEdit);
	      }
	    } catch (err) {
	      console.error(err);
	    }
	  }
	  openActivityDialog(activity, allowEdit) {
	    const typeId = parseInt(activity.typeID, 10);
	    if (typeId === babelHelpers.classPrivateFieldLooseBase(Manager, _activityTypes)[_activityTypes].provider && BX.CrmActivityProvider) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showProviderSix)[_showProviderSix](activity, allowEdit);
	    } else if (typeId === babelHelpers.classPrivateFieldLooseBase(Manager, _activityTypes)[_activityTypes].task) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showTask)[_showTask](activity, allowEdit);
	    }
	  }
	}
	function _showProviderSix2(activity, allowEdit) {
	  const providerID = activity.providerID;
	  switch (providerID) {
	    case 'CRM_TASKS_TASK':
	    case 'CRM_TASKS_TASK_COMMENT':
	      babelHelpers.classPrivateFieldLooseBase(this, _showTask)[_showTask](activity, allowEdit);
	      break;
	    case 'REST_APP':
	    case 'CONFIGURABLE_REST_APP':
	      babelHelpers.classPrivateFieldLooseBase(this, _showRestApp)[_showRestApp](activity);
	      break;
	    case 'CRM_CALENDAR_SHARING':
	      babelHelpers.classPrivateFieldLooseBase(this, _showCalendarSharing)[_showCalendarSharing](activity);
	      break;
	    default:
	  }
	}
	function _showRestApp2(activity) {
	  BX.rest.AppLayout.openApplication(activity.associatedEntityID || 0, {
	    action: 'view_activity',
	    activity_id: activity.ID || 0
	  });
	}
	function _showCalendarSharing2(activity) {
	  const calendarEventId = parseInt(activity.calendarEventId, 10);
	  if (!calendarEventId) {
	    return;
	  }
	  if ((window.top.BX || window.BX).Calendar.SliderLoader) {
	    const sliderId = `crm-calendar-slider-${calendarEventId}-${Math.floor(Math.random() * 1000)}`;
	    new (window.top.BX || window.BX).Calendar.SliderLoader(calendarEventId, {
	      sliderId
	    }).show();
	  }
	}
	function _showTask2(activity, allowEdit) {
	  const taskId = parseInt(activity.associatedEntityID, 10);
	  if (taskId <= 0) {
	    return;
	  }
	  let taskOpenUrl = allowEdit ? '/company/personal/user/#user_id#/tasks/task/edit/#task_id#/' : '/company/personal/user/#user_id#/tasks/task/view/#task_id#/';
	  taskOpenUrl = taskOpenUrl.replace('#user_id#', babelHelpers.classPrivateFieldLooseBase(this, _getCurrentUserId)[_getCurrentUserId]());
	  taskOpenUrl = taskOpenUrl.replace('#task_id#', taskId);
	  if (BX.SidePanel) {
	    BX.SidePanel.Instance.open(taskOpenUrl);
	  } else {
	    window.top.location.href = taskOpenUrl;
	  }
	}
	function _getCurrentUserId2() {
	  return BX.message('USER_ID');
	}
	async function _fetchActivity2(activityId) {
	  return new Promise((resolve, reject) => {
	    const serviceUrl = Util.add_url_param('/bitrix/components/bitrix/crm.activity.editor/ajax.php', {
	      id: activityId,
	      action: 'get_activity',
	      sessid: BX.bitrix_sessid()
	    });
	    main_core.ajax({
	      url: serviceUrl,
	      method: 'POST',
	      dataType: 'json',
	      data: {
	        ACTION: 'GET_ACTIVITY',
	        ID: activityId
	      },
	      onsuccess: data => {
	        if (data.ERROR) {
	          reject(data.ERROR);
	        } else {
	          resolve(data.ACTIVITY || null);
	        }
	      },
	      onfailure: errorData => {
	        reject(errorData);
	      }
	    });
	  });
	}
	Object.defineProperty(Manager, _activityTypes, {
	  writable: true,
	  value: {
	    task: 3,
	    provider: 6
	  }
	});

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	class GridActivitiesManager {
	  static showActivityAddingPopup(bindElement, gridManagerId, entityTypeId, entityId, currentUser, settings) {
	    void GridActivitiesManager.getManagerInstance().showAddPopup(bindElement, gridManagerId, entityTypeId, entityId, currentUser, settings);
	  }
	  static viewActivity(gridId, activityId, allowEdit) {
	    void GridActivitiesManager.getManagerInstance().viewActivity(gridId, activityId, allowEdit);
	  }
	  static getManagerInstance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new Manager();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	}
	Object.defineProperty(GridActivitiesManager, _instance, {
	  writable: true,
	  value: null
	});

	exports.GridActivitiesManager = GridActivitiesManager;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX));
//# sourceMappingURL=grid-activities-manager.bundle.js.map
