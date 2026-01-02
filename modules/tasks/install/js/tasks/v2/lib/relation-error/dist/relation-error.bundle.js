/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_popup,tasks_v2_core,tasks_v2_const) {
	'use strict';

	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	class RelationError {
	  constructor() {
	    Object.defineProperty(this, _taskId, {
	      writable: true,
	      value: void 0
	    });
	  }
	  setTaskId(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId] = taskId;
	    return this;
	  }
	  async showError(errorText, fieldId) {
	    void this.$store.dispatch(`${tasks_v2_const.Model.Tasks}/setFieldFilled`, {
	      id: babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId],
	      fieldName: fieldId
	    });
	    await new Promise(resolve => {
	      setTimeout(() => resolve(), 0);
	    });
	    const scrollContainer = document.querySelector(`[data-task-card-scroll="${babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]}"]`);
	    const addButton = scrollContainer.querySelector(`[data-task-relation-add="${fieldId}"]`);
	    const popup = new main_popup.Popup({
	      id: 'tasks-relation-error',
	      className: 'tasks-hint',
	      background: 'var(--ui-color-bg-content-inapp)',
	      bindElement: addButton,
	      content: errorText,
	      angle: true,
	      autoHide: true,
	      autoHideHandler: () => true,
	      cacheable: false,
	      animation: 'fading',
	      offsetLeft: addButton.offsetWidth / 2,
	      targetContainer: scrollContainer
	    });
	    popup.show();
	    setTimeout(() => popup.close(), 3000);
	  }
	  get $store() {
	    return tasks_v2_core.Core.getStore();
	  }
	}
	const relationError = new RelationError();

	exports.relationError = relationError;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX.Main,BX.Tasks.V2,BX.Tasks.V2.Const));
//# sourceMappingURL=relation-error.bundle.js.map
