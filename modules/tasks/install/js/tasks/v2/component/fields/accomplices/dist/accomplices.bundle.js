/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,tasks_v2_core,tasks_v2_provider_service_taskService,ui_entitySelector,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_component_elements_participantList,tasks_v2_lib_fieldHighlighter,tasks_v2_lib_userSelectorDialog) {
	'use strict';

	const accomplicesMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Accomplices,
	  title: main_core.Loc.getMessage('TASKS_V2_ACCOMPLICES_TITLE')
	});

	const AccomplicesMixin = {
	  methods: {
	    update(users) {
	      this.insertUsers(users);
	      this.updateTask(users.map(({
	        id
	      }) => id));
	    },
	    insertUsers(users) {
	      void tasks_v2_core.Core.getStore().dispatch(`${tasks_v2_const.Model.Users}/upsertMany`, users);
	    },
	    updateTask(accomplicesIds) {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        accomplicesIds
	      });
	    }
	  }
	};

	// @vue/component
	const Accomplices = {
	  name: 'TaskAccomplices',
	  components: {
	    ParticipantList: tasks_v2_component_elements_participantList.ParticipantList
	  },
	  mixins: [AccomplicesMixin],
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    users() {
	      return this.$store.getters[`${tasks_v2_const.Model.Users}/getByIds`](this.task.accomplicesIds);
	    },
	    dataset() {
	      return {
	        'data-task-id': this.taskId,
	        'data-task-field-id': accomplicesMeta.id,
	        'data-task-field-value': this.task.accomplicesIds.join(',')
	      };
	    }
	  },
	  template: `
		<ParticipantList
			:taskId="taskId"
			context="accomplices"
			:users="users"
			:dataset="dataset"
			@update="update"
		/>
	`
	};

	// @vue/component
	const AccomplicesChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
	  },
	  mixins: [AccomplicesMixin],
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      accomplicesMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    preselected() {
	      return this.task.accomplicesIds.map(id => ['user', id]);
	    },
	    design() {
	      return this.isSelected ? tasks_v2_component_elements_chip.ChipDesign.ShadowAccent : tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, accomplicesMeta.id);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  unmounted() {
	    var _this$selector;
	    (_this$selector = this.selector) == null ? void 0 : _this$selector.destroy();
	  },
	  methods: {
	    showDialog() {
	      var _this$selector2;
	      if (this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      (_this$selector2 = this.selector) != null ? _this$selector2 : this.selector = new tasks_v2_lib_userSelectorDialog.UserSelectorDialog({
	        taskId: this.taskId,
	        preselected: this.preselected,
	        dialogOptions: tasks_v2_component_elements_participantList.participantMeta.dialogOptions(this.taskId, 'accomplices'),
	        events: {
	          onHide: () => {
	            const users = this.selector.getDialog().getSelectedItems().map(item => ({
	              id: item.getId(),
	              name: item.getTitle(),
	              image: item.getAvatar()
	            }));
	            if (!this.isSelected && users.length > 0) {
	              this.highlightField();
	            }
	            this.update(users);
	          }
	        }
	      });
	      this.selector.selectItemsByIds(this.preselected);
	      this.selector.show(this.$el);
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(accomplicesMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.PERSON"
			:text="loc('TASKS_V2_ACCOMPLICES_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="accomplicesMeta.id"
			:data-task-chip-value="task.accomplicesIds.join(',')"
			@click="showDialog"
		/>
	`
	};

	exports.Accomplices = Accomplices;
	exports.AccomplicesChip = AccomplicesChip;
	exports.accomplicesMeta = accomplicesMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX,BX.Tasks.V2,BX.Tasks.V2.Provider.Service,BX.UI.EntitySelector,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib));
//# sourceMappingURL=accomplices.bundle.js.map
