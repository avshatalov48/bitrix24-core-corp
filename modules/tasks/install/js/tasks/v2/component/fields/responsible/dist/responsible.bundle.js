/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_vue3_vuex,ui_iconSet_api_vue,tasks_v2_component_elements_participant,tasks_v2_lib_analytics,tasks_v2_provider_service_taskService,main_core,tasks_v2_core,tasks_v2_const) {
	'use strict';

	const responsibleMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Responsible,
	  title: main_core.Loc.getMessage('TASKS_V2_RESPONSIBLE_TITLE'),
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
	const Responsible = {
	  name: 'TaskResponsible',
	  components: {
	    Participant: tasks_v2_component_elements_participant.Participant,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  inject: ['analytics', 'cardType'],
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
	    },
	    avatarOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  setup() {
	    return {
	      BIcon: ui_iconSet_api_vue.BIcon,
	      Outline: ui_iconSet_api_vue.Outline
	    };
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
	      return responsibleMeta.dialogOptions(this.localContext);
	    },
	    preselected() {
	      return [['user', this.task.responsibleId || this.currentUserId]];
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    isFlowFilledOnAdd() {
	      return this.task.flowId > 0 && !this.isEdit;
	    },
	    dataset() {
	      return {
	        'data-task-id': this.taskId,
	        'data-task-field-id': responsibleMeta.id,
	        'data-task-field-value': this.task.responsibleId
	      };
	    },
	    readonly() {
	      return !this.task.rights.delegate;
	    }
	  },
	  methods: {
	    handleHintClick() {
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        creatorId: this.currentUserId
	      });
	    },
	    updateTask(user) {
	      void this.$store.dispatch(`${tasks_v2_const.Model.Users}/upsert`, user);
	      if (this.task.responsibleId !== user.id) {
	        this.sendAnalytics();
	      }
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        responsibleId: user.id
	      });
	    },
	    sendAnalytics() {
	      tasks_v2_lib_analytics.analytics.sendAssigneeChange(this.analytics, {
	        cardType: this.cardType
	      });
	    }
	  },
	  template: `
		<div v-if="isFlowFilledOnAdd" class="tasks-field-responsible-auto">
			<BIcon :name="Outline.BOTTLENECK" color="var(--ui-color-accent-main-primary)"></BIcon>
			<div v-if="!avatarOnly">
				{{ loc('TASKS_V2_RESPONSIBLE_AUTO') }}
			</div>
		</div>
		<Participant
			v-else
			:taskId="taskId"
			:dialogOptions="dialogOptions"
			:preselected="preselected"
			:canChange="() => task.rights.delegate || task.creatorId === currentUserId"
			:cantChangeHint="loc('TASKS_V2_RESPONSIBLE_CANT_CHANGE')"
			:hintClickHandler="handleHintClick"
			:selectorWithActionMenu="selectorWithActionMenu"
			:dataset="dataset"
			:readonly="readonly"
			:avatarOnly="avatarOnly"
			@update="updateTask"
		/>
	`
	};

	exports.Responsible = Responsible;
	exports.responsibleMeta = responsibleMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Vue3.Vuex,BX.UI.IconSet,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX,BX.Tasks.V2,BX.Tasks.V2.Const));
//# sourceMappingURL=responsible.bundle.js.map
