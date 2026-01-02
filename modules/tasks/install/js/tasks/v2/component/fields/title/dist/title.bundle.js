/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,tasks_v2_component_elements_growingTextArea,tasks_v2_provider_service_taskService,tasks_v2_const) {
	'use strict';

	const titleMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Title
	});

	// @vue/component
	const Title = {
	  name: 'TaskTitle',
	  components: {
	    GrowingTextArea: tasks_v2_component_elements_growingTextArea.GrowingTextArea
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup() {
	    return {
	      titleMeta
	    };
	  },
	  computed: {
	    title: {
	      get() {
	        return this.task.title;
	      },
	      set(title) {
	        void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	          title
	        });
	      }
	    },
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleInput(title) {
	      if (!this.isEdit) {
	        this.title = title;
	      }
	    }
	  },
	  template: `
		<GrowingTextArea
			v-model="title"
			class="tasks-field-title"
			:data-task-id="taskId"
			:data-task-field-id="titleMeta.id"
			:data-task-field-value="task.title"
			data-field-container
			:placeholder="loc('TASKS_V2_TITLE_PLACEHOLDER')"
			:readonly="readonly || disabled"
			@input="handleInput"
		/>
	`
	};

	exports.Title = Title;
	exports.titleMeta = titleMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Const));
//# sourceMappingURL=title.bundle.js.map
