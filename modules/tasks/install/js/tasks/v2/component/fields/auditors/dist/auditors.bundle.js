/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core,tasks_v2_core,tasks_v2_provider_service_taskService,ui_entitySelector,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_component_elements_participantList,tasks_v2_lib_fieldHighlighter,tasks_v2_lib_userSelectorDialog) {
	'use strict';

	const auditorsMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Auditors,
	  title: main_core.Loc.getMessage('TASKS_V2_AUDITORS_TITLE')
	});

	const AuditorsMixin = {
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
	    updateTask(auditorsIds) {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        auditorsIds
	      });
	    }
	  }
	};

	// @vue/component
	const Auditors = {
	  name: 'TaskAuditors',
	  components: {
	    ParticipantList: tasks_v2_component_elements_participantList.ParticipantList
	  },
	  mixins: [AuditorsMixin],
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
	      return this.$store.getters[`${tasks_v2_const.Model.Users}/getByIds`](this.task.auditorsIds);
	    },
	    dataset() {
	      return {
	        'data-task-id': this.taskId,
	        'data-task-field-id': auditorsMeta.id,
	        'data-task-field-value': this.task.auditorsIds.join(',')
	      };
	    }
	  },
	  template: `
		<ParticipantList
			:taskId="taskId"
			context="auditors"
			:users="users"
			:dataset="dataset"
			@update="update"
		/>
	`
	};

	// @vue/component
	const AuditorsChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
	  },
	  mixins: [AuditorsMixin],
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Outline: ui_iconSet_api_core.Outline,
	      auditorsMeta
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    preselected() {
	      return this.task.auditorsIds.map(id => ['user', id]);
	    },
	    design() {
	      return this.isSelected ? tasks_v2_component_elements_chip.ChipDesign.ShadowAccent : tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, auditorsMeta.id);
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
	    handleClick() {
	      var _this$selector2;
	      if (this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      (_this$selector2 = this.selector) != null ? _this$selector2 : this.selector = new tasks_v2_lib_userSelectorDialog.UserSelectorDialog({
	        taskId: this.taskId,
	        preselected: this.preselected,
	        dialogOptions: tasks_v2_component_elements_participantList.participantMeta.dialogOptions(this.taskId, 'auditors'),
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
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(auditorsMeta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:icon="Outline.OBSERVER"
			:text="loc('TASKS_V2_AUDITORS_TITLE_CHIP')"
			:data-task-id="taskId"
			:data-task-chip-id="auditorsMeta.id"
			:data-task-chip-value="task.auditorsIds.join(',')"
			@click="handleClick"
		/>
	`
	};

	exports.Auditors = Auditors;
	exports.AuditorsChip = AuditorsChip;
	exports.auditorsMeta = auditorsMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX,BX.Tasks.V2,BX.Tasks.V2.Provider.Service,BX.UI.EntitySelector,BX.UI.IconSet,BX,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Lib));
//# sourceMappingURL=auditors.bundle.js.map
