/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,tasks_v2_const,ui_iconSet_api_core,ui_iconSet_actions,tasks_v2_lib_relationTasksDialog,tasks_v2_provider_service_relationService,tasks_v2_component_fields_relationTasks) {
	'use strict';

	const relatedTasksMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.RelatedTasks,
	  icon: ui_iconSet_api_core.Actions.CONNECTION,
	  idsField: 'relatedTaskIds',
	  containsField: 'containsRelatedTasks',
	  title: main_core.Loc.getMessage('TASKS_V2_RELATED_TASKS_TITLE'),
	  chipTitle: main_core.Loc.getMessage('TASKS_V2_RELATED_TASKS_TITLE_CHIP'),
	  hint: main_core.Loc.getMessage('TASKS_V2_RELATED_TASKS_ADD'),
	  countLoc: 'TASKS_V2_RELATED_TASKS_TITLE_COUNT',
	  service: tasks_v2_provider_service_relationService.relatedTasksService,
	  dialog: tasks_v2_lib_relationTasksDialog.relatedTasksDialog
	});

	// @vue/component
	const RelatedTasks = {
	  name: 'TaskRelatedTasks',
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
	      relatedTasksMeta
	    };
	  },
	  template: `
		<RelationTasks :taskId="taskId" :meta="relatedTasksMeta"/>
	`
	};

	// @vue/component
	const RelatedTasksChip = {
	  components: {
	    RelationTasksChip: tasks_v2_component_fields_relationTasks.RelationTasksChip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      relatedTasksMeta
	    };
	  },
	  template: `
		<RelationTasksChip :taskId="taskId" :meta="relatedTasksMeta"/>
	`
	};

	exports.RelatedTasks = RelatedTasks;
	exports.RelatedTasksChip = RelatedTasksChip;
	exports.relatedTasksMeta = relatedTasksMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX,BX.Tasks.V2.Const,BX.UI.IconSet,BX,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Component.Fields));
//# sourceMappingURL=related-tasks.bundle.js.map
