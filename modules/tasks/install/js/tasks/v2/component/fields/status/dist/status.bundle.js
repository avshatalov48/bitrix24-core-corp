/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_date,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_outline,tasks_v2_component_elements_hint,main_core,tasks_v2_const) {
	'use strict';

	const statusMeta = Object.freeze({
	  id: tasks_v2_const.TaskField.Status,
	  title: main_core.Loc.getMessage('TASKS_V2_STATUS_TITLE')
	});

	// @vue/component
	const Status = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Hint: tasks_v2_component_elements_hint.Hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  setup() {
	    return {
	      statusMeta
	    };
	  },
	  data() {
	    return {
	      isHintShown: false
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    icon() {
	      var _statuses$this$task$s;
	      const statuses = {
	        [tasks_v2_const.TaskStatus.Pending]: ui_iconSet_api_core.Outline.HOURGLASS,
	        [tasks_v2_const.TaskStatus.InProgress]: ui_iconSet_api_core.Outline.NEXT,
	        [tasks_v2_const.TaskStatus.SupposedlyCompleted]: ui_iconSet_api_core.Outline.REFRESH,
	        [tasks_v2_const.TaskStatus.Completed]: ui_iconSet_api_core.Outline.SENDED,
	        [tasks_v2_const.TaskStatus.Deferred]: ui_iconSet_api_core.Outline.PAUSE_L
	      };
	      return (_statuses$this$task$s = statuses[this.task.status]) != null ? _statuses$this$task$s : ui_iconSet_api_core.Outline.HOURGLASS;
	    },
	    statusFormatted() {
	      var _statuses$this$task$s2;
	      const statuses = {
	        [tasks_v2_const.TaskStatus.Pending]: this.loc('TASKS_V2_STATUS_PENDING'),
	        [tasks_v2_const.TaskStatus.InProgress]: this.loc('TASKS_V2_STATUS_IN_PROGRESS'),
	        [tasks_v2_const.TaskStatus.SupposedlyCompleted]: this.loc('TASKS_V2_STATUS_SUPPOSEDLY_COMPLETED'),
	        [tasks_v2_const.TaskStatus.Completed]: this.loc('TASKS_V2_STATUS_COMPLETED'),
	        [tasks_v2_const.TaskStatus.Deferred]: this.loc('TASKS_V2_STATUS_DEFERRED')
	      };
	      return (_statuses$this$task$s2 = statuses[this.task.status]) != null ? _statuses$this$task$s2 : this.loc('TASKS_V2_STATUS_PENDING');
	    },
	    statusAtFormatted() {
	      const statuses = {
	        [tasks_v2_const.TaskStatus.Pending]: 'TASKS_V2_STATUS_PENDING_FROM',
	        [tasks_v2_const.TaskStatus.InProgress]: 'TASKS_V2_STATUS_IN_PROGRESS_FROM',
	        [tasks_v2_const.TaskStatus.SupposedlyCompleted]: 'TASKS_V2_STATUS_SUPPOSEDLY_COMPLETED_FROM',
	        [tasks_v2_const.TaskStatus.Completed]: 'TASKS_V2_STATUS_COMPLETED_AT',
	        [tasks_v2_const.TaskStatus.Deferred]: 'TASKS_V2_STATUS_DEFERRED_AT'
	      };
	      return this.loc(statuses[this.task.status], {
	        '#DATE#': this.formatDate(this.task.statusChangedTs),
	        '#TIME#': this.formatTime(this.task.statusChangedTs)
	      });
	    },
	    createdAtFormatted() {
	      return this.loc('TASKS_V2_STATUS_CREATED_AT', {
	        '#DATE#': this.formatDate(this.task.createdTs),
	        '#TIME#': this.formatTime(this.task.createdTs)
	      });
	    }
	  },
	  methods: {
	    formatDate(timestamp) {
	      return main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('SHORT_DATE_FORMAT'), timestamp / 1000);
	    },
	    formatTime(timestamp) {
	      return main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), timestamp / 1000);
	    },
	    handleClick() {
	      this.clearTimeouts();
	      if (this.isHintShown) {
	        this.closePopup();
	      } else {
	        this.showPopup();
	      }
	    },
	    handleMouseEnter() {
	      this.clearTimeouts();
	      this.showTimeout = setTimeout(() => this.showPopup(), 100);
	    },
	    handleMouseLeave() {
	      this.clearTimeouts();
	      this.closePopup();
	    },
	    showPopup() {
	      this.clearTimeouts();
	      this.isHintShown = true;
	    },
	    closePopup() {
	      this.clearTimeouts();
	      this.isHintShown = false;
	    },
	    clearTimeouts() {
	      clearTimeout(this.showTimeout);
	    }
	  },
	  template: `
		<div
			class="tasks-field-status"
			:data-task-id="taskId"
			:data-task-field-id="statusMeta.id"
			:data-task-field-value="task.status"
			:data-task-created-ts="task.createdTs"
			:data-task-status-changes-ts="task.statusChangedTs"
			ref="container"
			@click="handleClick"
			@mouseenter="handleMouseEnter"
			@mouseleave="handleMouseLeave"
		>
			<BIcon class="tasks-field-status-icon" :name="icon"/>
			<div class="tasks-field-status-text">{{ statusFormatted }}</div>
		</div>
		<Hint v-if="isHintShown" :bindElement="$refs.container" @close="closePopup">
			<div class="tasks-field-status-hint">
				<div>{{ statusAtFormatted }}</div>
				<div>{{ createdAtFormatted }}</div>
			</div>
		</Hint>
	`
	};

	exports.Status = Status;
	exports.statusMeta = statusMeta;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Main,BX.UI.IconSet,BX.UI.IconSet,BX,BX.Tasks.V2.Component.Elements,BX,BX.Tasks.V2.Const));
//# sourceMappingURL=status.bundle.js.map
