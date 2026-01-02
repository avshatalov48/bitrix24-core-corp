/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Provider = this.BX.Tasks.V2.Provider || {};
(function (exports,tasks_v2_model_users,tasks_v2_provider_service_userService,main_core,ui_vue3_vuex,tasks_v2_const,tasks_v2_core,tasks_v2_lib_apiClient) {
	'use strict';

	function prepareCheckLists(checklist) {
	  const parentNodeIdMap = new Map();
	  checklist.forEach(item => {
	    parentNodeIdMap.set(item.id, item.nodeId);
	  });
	  return checklist.map(item => {
	    const title = prepareTitle(item);
	    const parentNodeId = item.parentId ? parentNodeIdMap.get(item.parentId) : 0;
	    return {
	      ...item,
	      title,
	      parentNodeId
	    };
	  });
	}
	function mapDtoToModel(checkList) {
	  var _checkList$accomplice, _checkList$auditors;
	  return {
	    id: checkList.id,
	    nodeId: checkList.nodeId,
	    title: checkList.title,
	    creator: checkList.creator ? tasks_v2_provider_service_userService.UserMappers.mapDtoToModel(checkList.creator) : null,
	    toggledBy: checkList.toggledBy ? tasks_v2_provider_service_userService.UserMappers.mapDtoToModel(checkList.toggledBy) : null,
	    toggledDate: checkList.toggledDate,
	    accomplices: (_checkList$accomplice = checkList.accomplices) == null ? void 0 : _checkList$accomplice.map(it => tasks_v2_provider_service_userService.UserMappers.mapDtoToModel(it)),
	    auditors: (_checkList$auditors = checkList.auditors) == null ? void 0 : _checkList$auditors.map(it => tasks_v2_provider_service_userService.UserMappers.mapDtoToModel(it)),
	    attachments: checkList.attachments,
	    isComplete: checkList.isComplete,
	    isImportant: checkList.isImportant,
	    parentId: checkList.parentId,
	    parentNodeId: checkList.parentNodeId,
	    sortIndex: checkList.sortIndex,
	    actions: checkList.actions,
	    panelIsShown: checkList.panelIsShown,
	    myFilterActive: checkList.myFilterActive,
	    collapsed: checkList.collapsed,
	    expanded: checkList.expanded,
	    localCompleteState: checkList.localCompleteState,
	    localCollapsedState: checkList.localCollapsedState,
	    areCompletedCollapsed: checkList.areCompletedCollapsed,
	    hidden: checkList.hidden,
	    groupMode: checkList.groupMode
	  };
	}
	function mapModelToSliderData(checkLists) {
	  return Object.fromEntries(checkLists.map(item => {
	    var _item$accomplices, _item$auditors, _item$attachments, _item$creator, _item$toggledBy;
	    const accomplices = (_item$accomplices = item.accomplices) == null ? void 0 : _item$accomplices.map(accomplice => ({
	      ID: accomplice.id,
	      TYPE: 'A',
	      NAME: accomplice.name,
	      IMAGE: accomplice.image,
	      IS_COLLABER: accomplice.type === tasks_v2_model_users.UserTypes.Collaber ? 1 : ''
	    }));
	    const auditors = (_item$auditors = item.auditors) == null ? void 0 : _item$auditors.map(auditor => ({
	      ID: auditor.id,
	      TYPE: 'U',
	      NAME: auditor.name,
	      IMAGE: auditor.image,
	      IS_COLLABER: auditor.type === tasks_v2_model_users.UserTypes.Collaber ? 1 : ''
	    }));
	    const attachments = Object.fromEntries((_item$attachments = item.attachments) == null ? void 0 : _item$attachments.map(key => [key, key]));
	    const members = [...accomplices, ...auditors].reduce((acc, curr) => {
	      acc[curr.ID] = curr;
	      return acc;
	    }, {});
	    const title = prepareTitle(item);
	    const node = Object.fromEntries(Object.entries({
	      NODE_ID: item.nodeId,
	      TITLE: title,
	      CREATED_BY: (_item$creator = item.creator) == null ? void 0 : _item$creator.id,
	      TOGGLED_BY: (_item$toggledBy = item.toggledBy) == null ? void 0 : _item$toggledBy.id,
	      TOGGLED_DATE: item.toggledDate,
	      MEMBERS: members,
	      NEW_FILE_IDS: attachments,
	      ATTACHMENTS: attachments,
	      IS_COMPLETE: item.isComplete,
	      IS_IMPORTANT: item.isImportant,
	      PARENT_ID: item.parentId,
	      SORT_INDEX: item.sortIndex,
	      ACTIONS: {
	        MODIFY: item.actions.modify,
	        REMOVE: item.actions.remove,
	        TOGGLE: item.actions.toggle
	      }
	    }).filter(([, value]) => value !== null && value !== undefined));
	    return [item.nodeId, node];
	  }));
	}
	function getUserIdsFromChecklists(checkLists, userType) {
	  return checkLists.flatMap(item => (item[userType] || []).map(user => user.id)).filter((id, idx, arr) => arr.indexOf(id) === idx);
	}
	function prepareTitle(item) {
	  var _item$accomplices2, _item$auditors2;
	  const names = [...((_item$accomplices2 = item.accomplices) != null ? _item$accomplices2 : []).map(member => member.name), ...((_item$auditors2 = item.auditors) != null ? _item$auditors2 : []).map(member => member.name)].join(' ');
	  if (names) {
	    return `${item.title} ${names}`;
	  }
	  return item.title;
	}

	var _getPromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromises");
	var _updateFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFields");
	var _updatePromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updatePromises");
	var _updateServerCheckListDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateServerCheckListDebounced");
	var _updateCheckListDebounced = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCheckListDebounced");
	var _updateCheckList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCheckList");
	class CheckListService {
	  constructor() {
	    Object.defineProperty(this, _updateCheckList, {
	      value: _updateCheckList2
	    });
	    Object.defineProperty(this, _updateCheckListDebounced, {
	      value: _updateCheckListDebounced2
	    });
	    Object.defineProperty(this, _getPromises, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updateFields, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updatePromises, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _updateServerCheckListDebounced, {
	      writable: true,
	      value: {}
	    });
	  }
	  async load(taskId) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    // eslint-disable-next-line no-async-promise-executor
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getPromises)[_getPromises])[taskId]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[taskId] = new Promise(async (resolve, reject) => {
	      try {
	        const data = await new tasks_v2_lib_apiClient.ApiClient().post('CheckList.get', {
	          task: {
	            id: taskId
	          }
	        });
	        const checkLists = data.map(it => mapDtoToModel(it));
	        await Promise.all([this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/upsertMany`, checkLists), this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	          id: taskId,
	          fields: {
	            containsChecklist: checkLists.length > 0,
	            checklist: checkLists.map(({
	              id
	            }) => id)
	          }
	        })]);
	        resolve();
	      } catch (error) {
	        reject(error);
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _getPromises)[_getPromises][taskId];
	  }
	  async save(taskId, checklists) {
	    // eslint-disable-next-line no-async-promise-executor
	    return new Promise(async (resolve, reject) => {
	      try {
	        const savedList = await new tasks_v2_lib_apiClient.ApiClient().post('CheckList.save', {
	          task: {
	            id: taskId,
	            checklist: prepareCheckLists(checklists)
	          }
	        });
	        const checkLists = savedList.map(it => mapDtoToModel(it));
	        await Promise.all([this.$store.dispatch(`${tasks_v2_const.Model.Interface}/setDisableCheckListAnimations`, true), this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/upsertMany`, checkLists), this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/update`, {
	          id: taskId,
	          fields: {
	            containsChecklist: checkLists.length > 0,
	            checklist: checkLists.map(item => item.id)
	          }
	        })]);
	        void this.$store.dispatch(`${tasks_v2_const.Model.Interface}/setDisableCheckListAnimations`, false);
	        resolve();
	      } catch (error) {
	        reject(error);
	      }
	    });
	  }
	  async collapse(taskId, checkListId) {
	    await new tasks_v2_lib_apiClient.ApiClient().post('CheckList.collapse', {
	      taskId,
	      checkListId
	    });
	    void this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	      id: checkListId,
	      fields: {
	        collapsed: true,
	        expanded: false
	      }
	    });
	  }
	  async expand(taskId, checkListId) {
	    await new tasks_v2_lib_apiClient.ApiClient().post('CheckList.expand', {
	      taskId,
	      checkListId
	    });
	    void this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	      id: checkListId,
	      fields: {
	        collapsed: false,
	        expanded: true
	      }
	    });
	  }
	  async complete(taskId, checkListId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateCheckListDebounced)[_updateCheckListDebounced](taskId, checkListId, {
	      isComplete: true
	    });
	  }
	  async renew(taskId, checkListId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _updateCheckListDebounced)[_updateCheckListDebounced](taskId, checkListId, {
	      isComplete: false
	    });
	  }
	  async delete(taskId, checkListId) {
	    const task = await this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](taskId);
	    const checklists = (await (task == null ? void 0 : task.checklist)) ? this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](task.checklist) : [];
	    await this.save(taskId, checklists);
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	async function _updateCheckListDebounced2(taskId, checkListId, fields) {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12;
	  (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields])[taskId]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[taskId] = {};
	  babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][taskId][checkListId] = {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][taskId][checkListId],
	    ...fields
	  };
	  (_babelHelpers$classPr6 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises])[taskId]) != null ? _babelHelpers$classPr6 : _babelHelpers$classPr5[taskId] = {};
	  (_babelHelpers$classPr8 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][taskId])[checkListId]) != null ? _babelHelpers$classPr8 : _babelHelpers$classPr7[checkListId] = new Resolvable();
	  (_babelHelpers$classPr10 = (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _updateServerCheckListDebounced)[_updateServerCheckListDebounced])[taskId]) != null ? _babelHelpers$classPr10 : _babelHelpers$classPr9[taskId] = {};
	  (_babelHelpers$classPr12 = (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _updateServerCheckListDebounced)[_updateServerCheckListDebounced][taskId])[checkListId]) != null ? _babelHelpers$classPr12 : _babelHelpers$classPr11[checkListId] = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _updateCheckList)[_updateCheckList], 300, this);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateServerCheckListDebounced)[_updateServerCheckListDebounced][taskId][checkListId](taskId, checkListId);
	  await babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][taskId][checkListId];
	}
	async function _updateCheckList2(taskId, checkListId) {
	  const fields = babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][taskId][checkListId];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _updateFields)[_updateFields][taskId][checkListId];
	  const promise = babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][taskId][checkListId];
	  delete babelHelpers.classPrivateFieldLooseBase(this, _updatePromises)[_updatePromises][taskId][checkListId];
	  try {
	    // Update store immediately for optimistic UI
	    await this.$store.dispatch(`${tasks_v2_const.Model.CheckList}/update`, {
	      id: checkListId,
	      fields
	    });

	    // Get updated checklists and save to server
	    const task = await this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](taskId);
	    const checklists = (await (task == null ? void 0 : task.checklist)) ? this.$store.getters[`${tasks_v2_const.Model.CheckList}/getByIds`](task.checklist) : [];
	    await new tasks_v2_lib_apiClient.ApiClient().post('CheckList.save', {
	      task: {
	        id: taskId,
	        checklist: prepareCheckLists(checklists)
	      }
	    });
	    promise.resolve();
	  } catch (error) {
	    console.error('CheckListService: update error', error);
	    promise.resolve();
	  }
	}
	const checkListService = new CheckListService();
	function Resolvable() {
	  let resolve;
	  const promise = new Promise(res => {
	    resolve = res;
	  });
	  promise.resolve = resolve;
	  return promise;
	}

	const CheckListMappers = {
	  mapModelToSliderData,
	  getUserIdsFromChecklists
	};

	exports.CheckListMappers = CheckListMappers;
	exports.checkListService = checkListService;

}((this.BX.Tasks.V2.Provider.Service = this.BX.Tasks.V2.Provider.Service || {}),BX.Tasks.V2.Model,BX.Tasks.V2.Provider.Service,BX,BX.Vue3.Vuex,BX.Tasks.V2.Const,BX.Tasks.V2,BX.Tasks.V2.Lib));
//# sourceMappingURL=check-list-service.bundle.js.map
