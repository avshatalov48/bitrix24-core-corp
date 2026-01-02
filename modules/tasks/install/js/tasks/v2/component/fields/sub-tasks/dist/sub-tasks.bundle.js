/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_lib_relationTasksDialog,tasks_v2_provider_service_relationService,ui_vue3_directives_hint,tasks_v2_component_elements_hint,tasks_v2_component_fields_relationTasks) {
	'use strict';

	const subTasksMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.SubTasks,
	  icon: ui_iconSet_api_core.Outline.RELATED_TASKS,
	  idsField: 'subTaskIds',
	  containsField: 'containsSubTasks',
	  title: main_core.Loc.getMessage('TASKS_V2_SUB_TASKS_TITLE'),
	  chipTitle: main_core.Loc.getMessage('TASKS_V2_SUB_TASKS_TITLE_CHIP'),
	  hint: main_core.Loc.getMessage('TASKS_V2_SUB_TASKS_ADD'),
	  countLoc: 'TASKS_V2_SUB_TASKS_TITLE_COUNT',
	  service: tasks_v2_provider_service_relationService.subTasksService,
	  dialog: tasks_v2_lib_relationTasksDialog.subTasksDialog
	});

	// @vue/component
	const SubTasks = {
	  name: 'TaskSubTasks',
	  components: {
	    RelationTasks: tasks_v2_component_fields_relationTasks.RelationTasks
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      subTasksMeta
	    };
	  },
	  template: `
		<RelationTasks :taskId="taskId" :meta="subTasksMeta"/>
	`
	};

	// @vue/component
	const SubTasksChip = {
	  components: {
	    RelationTasksChip: tasks_v2_component_fields_relationTasks.RelationTasksChip
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
	      subTasksMeta
	    };
	  },
	  computed: {
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.loc('TASKS_V2_SUB_TASKS_DISABLED_HINT'),
	        popupOptions: {
	          offsetLeft: this.$el.offsetWidth / 2,
	          targetContainer: document.querySelector(`[data-task-card-scroll="${this.taskId}"]`)
	        },
	        timeout: 200
	      });
	    }
	  },
	  template: `
		<RelationTasksChip v-hint="tooltip" :taskId="taskId" :meta="subTasksMeta" :disabled="!isEdit"/>
	`
	};

	exports.SubTasks = SubTasks;
	exports.SubTasksChip = SubTasksChip;
	exports.subTasksMeta = subTasksMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Vue3.Directives,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Fields));
//# sourceMappingURL=sub-tasks.bundle.js.map
