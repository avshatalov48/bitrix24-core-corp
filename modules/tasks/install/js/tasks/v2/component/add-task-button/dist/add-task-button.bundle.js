/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core,ui_vue3_components_button,tasks_v2_component_elements_hint,tasks_v2_const,tasks_v2_lib_fieldHighlighter,tasks_v2_provider_service_fileService) {
	'use strict';

	// @vue/component
	const AddTaskButton = {
	  name: 'AddTaskButton',
	  components: {
	    UiButton: ui_vue3_components_button.Button,
	    Hint: tasks_v2_component_elements_hint.Hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    size: {
	      type: String,
	      default: ui_vue3_components_button.ButtonSize.LARGE
	    },
	    hasError: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['addTask', 'update:hasError'],
	  data() {
	    return {
	      fieldContainer: null,
	      isPopupShown: false,
	      isLoading: false,
	      errorReason: null
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    isUploading() {
	      return tasks_v2_provider_service_fileService.fileService.get(this.taskId).isUploading();
	    },
	    isCheckListUploading() {
	      var _this$task$checklist;
	      return (_this$task$checklist = this.task.checklist) == null ? void 0 : _this$task$checklist.some(itemId => tasks_v2_provider_service_fileService.fileService.get(itemId, tasks_v2_provider_service_fileService.EntityTypes.CheckListItem).isUploading());
	    },
	    isDisabled() {
	      return this.task.title.trim() === '' || this.isUploading || this.isCheckListUploading || this.isLoading;
	    }
	  },
	  watch: {
	    hasError(value) {
	      if (value === true) {
	        this.isLoading = false;
	      }
	    }
	  },
	  methods: {
	    handleClick() {
	      if (!this.isDisabled) {
	        this.isLoading = true;
	        this.$emit('update:hasError', false);
	        this.$emit('addTask');
	        return;
	      }
	      if (this.task.title.trim() === '') {
	        setTimeout(() => this.highlightTitle());
	      } else if (this.isUploading) {
	        setTimeout(() => this.highlightFiles());
	      } else if (this.isCheckListUploading) {
	        setTimeout(() => this.highlightChecklist());
	      }
	    },
	    highlightTitle() {
	      this.errorReason = this.loc('TASKS_V2_TITLE_IS_EMPTY');
	      this.fieldContainer = tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).addHighlight(tasks_v2_const.TaskField.Title).getFieldContainer(tasks_v2_const.TaskField.Title);
	      this.fieldContainer.querySelector('textarea').focus();
	      this.showPopup();
	    },
	    highlightFiles() {
	      this.errorReason = this.loc('TASKS_V2_FILE_IS_UPLOADING');
	      this.fieldContainer = tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).addChipHighlight(tasks_v2_const.TaskField.Files).getChipContainer(tasks_v2_const.TaskField.Files);
	      this.showPopup();
	    },
	    highlightChecklist() {
	      this.errorReason = this.loc('TASKS_V2_FILE_IS_UPLOADING');
	      this.fieldContainer = tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).addChipHighlight(tasks_v2_const.TaskField.CheckList).getChipContainer(tasks_v2_const.TaskField.CheckList);
	      this.showPopup();
	    },
	    showPopup() {
	      const removeHighlight = () => {
	        this.isPopupShown = false;
	        main_core.Event.unbind(window, 'keydown', removeHighlight);
	      };
	      main_core.Event.bind(window, 'keydown', removeHighlight);
	      this.isPopupShown = true;
	    }
	  },
	  template: `
		<div
			class="tasks-add-task-button-container"
			:class="{ '--disabled': isDisabled }"
			:data-task-id="taskId"
			data-task-button-id="create"
			@click="handleClick"
		>
			<UiButton
				class="tasks-add-task-button"
				:text="loc('TASKS_V2_ADD_TASK')"
				:size="size"
				:disabled="isDisabled"
				:loading="isLoading && !hasError"
			/>
		</div>
		<Hint
			v-if="isPopupShown"
			:bindElement="fieldContainer"
			@close="isPopupShown = false"
		>
			{{ errorReason }}
		</Hint>
	`
	};

	exports.AddTaskButton = AddTaskButton;

}((this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {}),BX,BX.Vue3.Components,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Const,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service));
//# sourceMappingURL=add-task-button.bundle.js.map
