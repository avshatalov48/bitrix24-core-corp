/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_iconSet_api_vue,ui_iconSet_outline,tasks_v2_const,tasks_v2_provider_service_taskService) {
	'use strict';

	// @vue/component
	const Importance = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (this.readonly) {
	        return;
	      }
	      const isImportant = !this.task.isImportant;
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        isImportant
	      });
	    }
	  },
	  template: `
		<div
			class="tasks-field-importance"
			:class="{ '--active': task.isImportant, '--readonly': readonly }"
			:data-task-id="taskId"
			:data-task-field-id="'isImportant'"
			:data-task-field-value="task.isImportant"
			@click="handleClick"
		>
			<BIcon :name="task.isImportant ? Outline.FIRE_SOLID : Outline.FIRE" :hoverable="false"/>
		</div>
	`
	};

	exports.Importance = Importance;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=importance.bundle.js.map
