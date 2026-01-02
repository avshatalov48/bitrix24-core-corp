/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_directives_hint,ui_system_typography_vue,ui_iconSet_api_vue,tasks_v2_component_taskList,tasks_v2_component_elements_hint,tasks_v2_provider_service_taskService,main_core,tasks_v2_core,tasks_v2_lib_relationError,tasks_v2_provider_service_relationService,tasks_v2_lib_entitySelectorDialog,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_lib_fieldHighlighter) {
	'use strict';

	const parentTaskMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Parent,
	  title: main_core.Loc.getMessage('TASKS_V2_PARENT_TASK_TITLE')
	});

	var _taskId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("taskId");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _onUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUpdateOnce");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _onItemChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onItemChange");
	var _clearOnUpdateOnce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearOnUpdateOnce");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _task = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("task");
	class ParentTaskDialog {
	  constructor() {
	    Object.defineProperty(this, _task, {
	      get: _get_task,
	      set: void 0
	    });
	    Object.defineProperty(this, _items, {
	      get: _get_items,
	      set: void 0
	    });
	    Object.defineProperty(this, _onItemChange, {
	      value: _onItemChange2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _taskId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onUpdateOnce, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _clearOnUpdateOnce, {
	      writable: true,
	      value: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = null;
	      }
	    });
	  }
	  setTaskId(taskId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId] = taskId;
	    return this;
	  }
	  showTo(targetNode) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _dialog))[_dialog]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].showTo(targetNode);
	  }
	  onUpdateOnce(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce)[_onUpdateOnce] = callback;
	  }
	}
	function _createDialog2() {
	  const onItemChangeDebounced = main_core.Runtime.debounce(babelHelpers.classPrivateFieldLooseBase(this, _onItemChange)[_onItemChange], 10, this);
	  return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    context: 'tasks-card',
	    multiple: false,
	    enableSearch: true,
	    width: 500,
	    entities: [{
	      id: tasks_v2_const.EntitySelectorEntity.Task
	    }],
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _items)[_items],
	    events: {
	      'Item:onSelect': onItemChangeDebounced,
	      'Item:onDeselect': onItemChangeDebounced,
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce],
	      onDestroy: babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]
	    }
	  });
	}
	async function _onItemChange2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	  const selectedTaskId = (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems()[0]) == null ? void 0 : _babelHelpers$classPr4.getId()) != null ? _babelHelpers$classPr3 : 0;
	  if (selectedTaskId > 0) {
	    const error = await tasks_v2_provider_service_relationService.subTasksService.setParent(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId], selectedTaskId);
	    if (error) {
	      void tasks_v2_lib_relationError.relationError.setTaskId(babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]).showError(error, tasks_v2_const.TaskField.Parent);
	      return;
	    }
	  } else if (selectedTaskId === 0 && babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].parentId > 0) {
	    await tasks_v2_provider_service_relationService.subTasksService.delete(babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].parentId, [babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]]);
	  }
	  (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _onUpdateOnce))[_onUpdateOnce]) == null ? void 0 : _babelHelpers$classPr5.call(_babelHelpers$classPr6);
	  babelHelpers.classPrivateFieldLooseBase(this, _clearOnUpdateOnce)[_clearOnUpdateOnce]();
	}
	function _get_items() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].parentId ? [[tasks_v2_const.EntitySelectorEntity.Task, babelHelpers.classPrivateFieldLooseBase(this, _task)[_task].parentId]] : [];
	}
	function _get_task() {
	  return tasks_v2_core.Core.getStore().getters[`${tasks_v2_const.Model.Tasks}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _taskId)[_taskId]);
	}
	const parentTaskDialog = new ParentTaskDialog();

	// @vue/component
	const ParentTask = {
	  name: 'TaskParentTask',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    TaskList: tasks_v2_component_taskList.TaskList,
	    TextMd: ui_system_typography_vue.TextMd
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      parentTaskMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    parentTask() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.parentId);
	    },
	    parentId() {
	      return this.task.parentId;
	    },
	    hasParent() {
	      return this.task.parentId > 0;
	    },
	    parentTaskIds() {
	      return this.hasParent ? [this.task.parentId] : [];
	    },
	    text() {
	      return parentTaskMeta.title;
	    },
	    context() {
	      return parentTaskMeta.id;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_PARENT_TASK_SELECT'),
	        popupOptions: {
	          offsetLeft: this.$refs.add.$el.offsetWidth / 2
	        }
	      });
	    }
	  },
	  watch: {
	    parentId: {
	      immediate: true,
	      handler() {
	        if (this.hasParent) {
	          void this.loadParentTask();
	        }
	      }
	    }
	  },
	  methods: {
	    async loadParentTask() {
	      if (this.parentTask) {
	        return;
	      }
	      await tasks_v2_provider_service_taskService.taskService.getById(this.parentId);
	    },
	    handleTitleClick() {
	      if (this.readonly) {
	        return;
	      }
	      parentTaskDialog.setTaskId(this.taskId).showTo(this.$refs.title);
	    },
	    handleEditClick() {
	      parentTaskDialog.setTaskId(this.taskId).showTo(this.$refs.add.$el);
	    },
	    async handleRemoveParentTask(parentId) {
	      await tasks_v2_provider_service_relationService.subTasksService.delete(parentId, [this.taskId]);
	    }
	  },
	  template: `
		<div
			class="tasks-field-parent-task"
			:data-task-id="taskId"
			:data-task-field-id="parentTaskMeta.id"
		>
			<div class="tasks-field-parent-task-title">
				<div
					class="tasks-field-parent-task-main"
					:class="{ '--readonly': readonly }"
					ref="title"
					@click="handleTitleClick"
				>
					<BIcon :name="Outline.SUBTASK"/>
					<TextMd :accent="true">{{ text }}</TextMd>
				</div>
				<div
					v-if="!readonly"
					v-hint="tooltip"
					class="tasks-field-parent-task-edit-container"
				>
					<BIcon
						class="tasks-field-parent-task-icon"
						:name="Outline.PLUS_L"
						:hoverable="true"
						:data-task-relation-add="parentTaskMeta.id"
						ref="add"
						@click="handleEditClick"
					/>
				</div>
			</div>
			<TaskList
				v-if="hasParent"
				:readonly="readonly"
				:context="context"
				:taskIds="parentTaskIds"
				@removeTask="handleRemoveParentTask"
			/>
		</div>
	`
	};

	// @vue/component
	const ParentTaskChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    isAutonomous: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup() {
	    return {
	      parentTaskMeta,
	      Outline: ui_iconSet_api_core.Outline
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    parentTask() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.task.parentId);
	    },
	    hasParent() {
	      return this.task.parentId > 0;
	    },
	    icon() {
	      return ui_iconSet_api_core.Outline.SUBTASK;
	    },
	    design() {
	      return {
	        [!this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent,
	        [!this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.ShadowAccent,
	        [this.isAutonomous && !this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineNoAccent,
	        [this.isAutonomous && this.isSelected]: tasks_v2_component_elements_chip.ChipDesign.OutlineAccent
	      }.true;
	    },
	    isSelected() {
	      if (this.isAutonomous) {
	        return this.hasParent;
	      }
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, parentTaskMeta.id);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    text() {
	      return parentTaskMeta.title;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (!this.isAutonomous && this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      parentTaskDialog.setTaskId(this.taskId).showTo(this.$el);
	      if (!this.isAutonomous) {
	        parentTaskDialog.onUpdateOnce(this.highlightField);
	      }
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(parentTaskMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:text="text"
			:icon="icon"
			:data-task-id="taskId"
			:data-task-chip-id="parentTaskMeta.id"
			ref="chip"
			@click="handleClick"
		/>
	`
	};

	exports.ParentTask = ParentTask;
	exports.ParentTaskChip = ParentTaskChip;
	exports.parentTaskMeta = parentTaskMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Vue3.Directives,BX.UI.System.Typography.Vue,BX.UI.IconSet,BX.Tasks.V2.Component,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.V2,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Lib,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib));
//# sourceMappingURL=parent-task.bundle.js.map
