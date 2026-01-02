/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,ui_vue3_vuex,tasks_v2_const) {
	'use strict';

	class CheckList extends ui_vue3_vuex.BuilderEntityModel {
	  getName() {
	    return tasks_v2_const.Model.CheckList;
	  }
	  getElementState() {
	    return {
	      id: 0,
	      nodeId: null,
	      title: '',
	      creator: null,
	      toggledBy: null,
	      toggledDate: null,
	      accomplices: [],
	      auditors: [],
	      attachments: [],
	      isComplete: false,
	      isImportant: false,
	      parentId: 0,
	      parentNodeId: null,
	      sortIndex: 0,
	      actions: {
	        modify: true,
	        remove: true,
	        toggle: true
	      },
	      panelIsShown: false,
	      myFilterActive: false,
	      collapsed: false,
	      expanded: false,
	      localCompleteState: null,
	      localCollapsedState: null,
	      areCompletedCollapsed: false,
	      hidden: false,
	      groupMode: {
	        active: false,
	        selected: false
	      }
	    };
	  }
	}

	exports.CheckList = CheckList;

}((this.BX.Tasks.V2.Model = this.BX.Tasks.V2.Model || {}),BX.Vue3.Vuex,BX.Tasks.V2.Const));
//# sourceMappingURL=check-list.bundle.js.map
