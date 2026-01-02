/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core,ui_iconSet_outline,main_core_events,main_date,ui_system_typography_vue,ui_iconSet_api_vue,tasks_v2_const,tasks_v2_component_elements_hoverPill,tasks_v2_lib_calendar,tasks_v2_provider_service_taskService,tasks_v2_component_fields_responsible,tasks_v2_component_fields_deadline,ui_system_skeleton_vue) {
	'use strict';

	// @vue/component
	const TaskLine = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Responsible: tasks_v2_component_fields_responsible.Responsible,
	    DeadlinePopup: tasks_v2_component_fields_deadline.DeadlinePopup,
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill,
	    TextMd: ui_system_typography_vue.TextMd,
	    TextSm: ui_system_typography_vue.TextSm
	  },
	  props: {
	    context: {
	      type: String,
	      required: true
	    },
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    readonly: {
	      type: Boolean,
	      required: true
	    },
	    last: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['remove'],
	  setup() {
	    return {
	      responsibleMeta: tasks_v2_component_fields_responsible.responsibleMeta,
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  data() {
	    return {
	      isDeadlinePopupShown: false,
	      selectingDeadlineTs: null,
	      nowTs: Date.now()
	    };
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    responsible() {
	      return this.$store.getters[`${tasks_v2_const.Model.Users}/getById`](this.task.responsibleId);
	    },
	    dialogOptions() {
	      return tasks_v2_component_fields_responsible.responsibleMeta.dialogOptions(this.context);
	    },
	    preselected() {
	      return [['user', this.task.responsibleId || this.currentUserId]];
	    },
	    dataset() {
	      return {
	        'data-task-id': this.taskId,
	        'data-task-field-id': tasks_v2_component_fields_responsible.responsibleMeta.id,
	        'data-task-field-value': this.task.responsibleId
	      };
	    },
	    deadlineTs() {
	      var _this$selectingDeadli;
	      return (_this$selectingDeadli = this.selectingDeadlineTs) != null ? _this$selectingDeadli : this.task.deadlineTs;
	    },
	    expiredDuration() {
	      if (!this.deadlineTs) {
	        return 0;
	      }
	      return this.nowTs - this.deadlineTs;
	    },
	    isExpired() {
	      return this.expiredDuration > 0 && !this.isFlowFilledOnAdd;
	    },
	    deadlineFormatted() {
	      if (this.isFlowFilledOnAdd) {
	        return this.loc('TASKS_V2_DEADLINE_AUTO');
	      }
	      if (!this.deadlineTs) {
	        return this.loc('TASKS_V2_DEADLINE_EMPTY');
	      }
	      if (this.isExpired) {
	        return this.loc('TASKS_V2_TASK_LIST_DEADLINE_EXPIRED', {
	          '#DURATION#': new main_date.DurationFormat(this.expiredDuration).formatClosest()
	        });
	      }
	      return tasks_v2_lib_calendar.calendar.formatDateTime(this.deadlineTs);
	    },
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    isFlowFilledOnAdd() {
	      return this.task.flowId > 0 && !this.isEdit;
	    },
	    deadlineReadonly() {
	      return this.readonly || !this.task.rights.deadline || this.isFlowFilledOnAdd;
	    },
	    detachReadonly() {
	      const canDetach = this.task.rights.detachParent || this.task.rights.detachRelated;
	      return this.readonly || !canDetach;
	    }
	  },
	  mounted() {
	    this.nowTsInterval = setInterval(() => {
	      this.nowTs = Date.now();
	    }, 1000);
	  },
	  beforeUnmount() {
	    clearInterval(this.nowTsInterval);
	  },
	  methods: {
	    handleEditDeadline() {
	      if (this.deadlineReadonly) {
	        return;
	      }
	      this.isDeadlinePopupShown = true;
	    },
	    handleClearDeadline() {
	      if (this.deadlineReadonly) {
	        return;
	      }
	      void tasks_v2_provider_service_taskService.taskService.update(this.taskId, {
	        deadlineTs: 0
	      });
	    },
	    handleDeadlineUpdate(selectingDeadlineTs) {
	      this.selectingDeadlineTs = selectingDeadlineTs;
	    },
	    handleDeadlinePopupClose() {
	      this.isDeadlinePopupShown = false;
	      this.selectingDeadlineTs = null;
	    },
	    handleTaskOpen() {
	      main_core_events.EventEmitter.emit(tasks_v2_const.EventName.OpenFullCard, {
	        taskId: this.taskId,
	        widthOffset: 48
	      });
	    },
	    handleRemove() {
	      if (this.readonly) {
	        return;
	      }
	      this.$emit('remove');
	    }
	  },
	  template: `
		<div class="tasks-task-line-container" :class="{ '--last': last }">
			<div class="tasks-task-line-wrapper">
				<div class="tasks-task-line-title-container" @click="handleTaskOpen">
					<TextMd class="tasks-task-line-title">
						{{ task.title }}
					</TextMd>
				</div>
				<div class="tasks-task-line-fields-container">
					<Responsible
						:context="context"
						:taskId="taskId"
						:selectorWithActionMenu="true"
						:avatarOnly="true"
					/>
					<div
						class="tasks-task-line-deadline"
						:class="{ '--expired': isExpired }"
						ref="deadline"
					>
						<HoverPill
							:withClear="Boolean(deadlineTs) && !deadlineReadonly"
							:readonly="deadlineReadonly"
							@click="handleEditDeadline"
							@clear="handleClearDeadline"
						>
							<TextSm class="tasks-task-line-deadline-text" :accent="isExpired">
								{{ deadlineFormatted }}
							</TextSm>
						</HoverPill>
					</div>
				</div>
				<div
					class="tasks-task-line-cross"
					:class="{ '--readonly': detachReadonly }"
					@click="handleRemove"
				>
					<BIcon :name="Outline.CROSS_L" :hoverable="true"/>
				</div>
			</div>
		</div>
		<DeadlinePopup
			v-if="isDeadlinePopupShown"
			:taskId="taskId"
			:bindElement="$refs.deadline"
			@update="handleDeadlineUpdate"
			@close="handleDeadlinePopupClose"
		/>
	`
	};

	const TaskLineSkeleton = {
	  components: {
	    BLine: ui_system_skeleton_vue.BLine
	  },
	  props: {
	    last: {
	      type: Boolean,
	      default: false
	    }
	  },
	  template: `
		<div class="tasks-task-line-container" :class="{ '--last': last }">
			<div class="tasks-task-line-wrapper">
				<div class="tasks-task-line-title-container">
					<BLine :width="200" :height="10"/>
				</div>
				<div class="tasks-task-line-fields-container">
					<div class="tasks-task-line-skeleton-avatar">
						<BLine circle :width="25" :height="25" :radius="25"/>
					</div>
					<div class="tasks-task-line-deadline">
						<BLine :width="80" :height="10"/>
					</div>
				</div>
				<div class="tasks-task-line-cross"/>
			</div>
		</div>
	`
	};

	const limit = tasks_v2_const.Limit.RelationList;

	// @vue/component
	const TaskList = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    TaskLine,
	    TaskLineSkeleton
	  },
	  props: {
	    taskIds: {
	      type: Array,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    },
	    readonly: {
	      type: Boolean,
	      required: true
	    }
	  },
	  emits: ['openMore', 'removeTask'],
	  setup() {
	    return {
	      Outline: ui_iconSet_api_vue.Outline
	    };
	  },
	  computed: {
	    limitedTasks() {
	      return this.taskIds.slice(0, limit);
	    },
	    shouldShowMore() {
	      return this.taskIds.length > limit;
	    },
	    moreText() {
	      const count = this.taskIds.length - limit;
	      return main_core.Loc.getMessagePlural('TASKS_V2_TASK_LIST_MORE', count, {
	        '#COUNT#': count
	      });
	    }
	  },
	  methods: {
	    isLast(index) {
	      return index === Math.min(this.taskIds.length, limit) - 1;
	    },
	    isTaskLoaded(taskId) {
	      return Boolean(this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](taskId));
	    }
	  },
	  template: `
		<div class="tasks-task-list">
			<TaskLineSkeleton v-if="taskIds.length === 0" :last="true"/>
			<template v-for="(taskId, index) in limitedTasks" :key="taskId">
				<TaskLine
					v-if="isTaskLoaded(taskId)"
					:taskId="taskId"
					:context="context"
					:readonly="readonly"
					:last="isLast(index)"
					@remove="$emit('removeTask', taskId)"
				/>
				<TaskLineSkeleton
					v-else
					:last="isLast(index)"
				/>
			</template>
			<div
				v-if="shouldShowMore"
				class="tasks-task-list-more"
				@click="$emit('openMore')"
			>
				<div class="tasks-task-list-more-text">
					{{ moreText }}
				</div>
				<BIcon
					class="tasks-task-list-icon"
					:name="Outline.CHEVRON_RIGHT_L"
					:hoverable="true"
				/>
			</div>
		</div>
	`
	};

	exports.TaskList = TaskList;

}((this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {}),BX,BX,BX.Event,BX.Main,BX.UI.System.Typography.Vue,BX.UI.IconSet,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib,BX.Tasks.V2.Provider.Service,BX.Tasks.V2.Component.Fields,BX.Tasks.V2.Component.Fields,BX.UI.System.Skeleton.Vue));
//# sourceMappingURL=task-list.bundle.js.map
