/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_vuex,tasks_v2_component_elements_participant,tasks_v2_provider_service_taskService,main_core,tasks_v2_core,tasks_v2_const) {
	'use strict';

	const creatorMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Creator,
	  title: main_core.Loc.getMessage('TASKS_V2_CREATOR_TITLE'),
	  dialogOptions: (context = 'tasks-card-participant') => {
	    const limits = tasks_v2_core.Core.getParams().limits;
	    return {
	      context,
	      multiple: false,
	      enableSearch: true,
	      entities: [{
	        id: tasks_v2_const.EntitySelectorEntity.User,
	        options: {
	          emailUsers: true,
	          inviteGuestLink: true,
	          analyticsSource: 'tasks',
	          lockGuestLink: !limits.mailUserIntegration,
	          lockGuestLinkFeatureId: limits.mailUserIntegrationFeatureId
	        }
	      }, {
	        id: 'department'
	      }]
	    };
	  }
	});

	// @vue/component
	const Creator = {
	  name: 'TaskCreator',
	  components: {
	    Participant: tasks_v2_component_elements_participant.Participant
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    },
	    selectorWithActionMenu: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      localContext: `${this.context}-${this.$options.name}`
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      currentUserId: `${tasks_v2_const.Model.Interface}/currentUserId`
	    }),
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    dialogOptions() {
	      return creatorMeta.dialogOptions(this.localContext);
	    },
	    preselected() {
	      return [['user', this.task.creatorId || this.currentUserId]];
	    },
	    cantChangeHint() {
	      if (this.task.flowId > 0) {
	        return this.loc('TASKS_V2_CREATOR_CANT_CHANGE_FLOW');
	      }
	      return this.loc('TASKS_V2_CREATOR_CANT_CHANGE');
	    },
	    dataset() {
	      return {
	        'data-task-id': this.taskId,
	        'data-task-field-id': creatorMeta.id,
	        'data-task-field-value': this.task.creatorId
	      };
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleHintClick() {
	      if (this.task.flowId > 0) {
	        return;
	      }
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        responsibleId: this.currentUserId
	      });
	    },
	    updateTask(user) {
	      void this.$store.dispatch(`${tasks_v2_const.Model.Users}/upsert`, user);
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        creatorId: user.id
	      });
	    }
	  },
	  template: `
		<Participant
			:taskId="taskId"
			:dialogOptions="dialogOptions"
			:preselected="preselected"
			:canChange="() => task.responsibleId === currentUserId && (task.flowId ?? 0) <= 0"
			:cantChangeHint="cantChangeHint"
			:hintClickHandler="handleHintClick"
			:selectorWithActionMenu="selectorWithActionMenu"
			:dataset="dataset"
			:readonly="readonly"
			@update="updateTask"
		/>
	`
	};

	exports.Creator = Creator;
	exports.creatorMeta = creatorMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Vue3.Vuex,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.V2,BX.Tasks.V2.Const));
//# sourceMappingURL=creator.bundle.js.map
