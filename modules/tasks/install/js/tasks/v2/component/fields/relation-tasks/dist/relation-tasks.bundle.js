/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,main_core_events,ui_vue3_directives_hint,ui_system_typography_vue,ui_iconSet_api_vue,ui_iconSet_actions,tasks_v2_component_taskList,tasks_v2_component_elements_hint,tasks_v2_const,tasks_v2_component_elements_chip,tasks_v2_lib_fieldHighlighter) {
	'use strict';

	// @vue/component
	const RelationTasks = {
	  name: 'TaskRelationTasks',
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    TaskList: tasks_v2_component_taskList.TaskList,
	    TextMd: ui_system_typography_vue.TextMd
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    /** @type RelationMeta */
	    meta: {
	      type: Object,
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
	    isEdit() {
	      return Number.isInteger(this.taskId) && this.taskId > 0;
	    },
	    ids() {
	      return [...this.task[this.meta.idsField]].sort((a, b) => b - a);
	    },
	    text() {
	      if (this.ids.length > 0) {
	        return this.loc(this.meta.countLoc, {
	          '#COUNT#': this.ids.length
	        });
	      }
	      return this.meta.title;
	    },
	    context() {
	      return this.meta.id;
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    },
	    tooltip() {
	      return () => tasks_v2_component_elements_hint.tooltip({
	        text: this.meta.hint,
	        popupOptions: {
	          offsetLeft: this.$refs.add.$el.offsetWidth / 2
	        }
	      });
	    }
	  },
	  watch: {
	    ids(newIds, oldIds) {
	      if ([...(newIds || [])].sort().join(',') === [...(oldIds || [])].sort().join(',')) {
	        return;
	      }
	      if (this.meta.service.hasUnloadedIds(this.taskId)) {
	        void this.meta.service.list(this.taskId);
	      }
	    }
	  },
	  created() {
	    if (!this.meta.service.areIdsLoaded(this.taskId) || this.meta.service.hasUnloadedIds(this.taskId)) {
	      void this.meta.service.list(this.taskId, true);
	    }
	  },
	  methods: {
	    handleTitleClick() {
	      if (this.isEdit && (this.readonly || this.task[this.meta.containsField])) {
	        this.openGrid();
	        return;
	      }
	      this.showDialog();
	    },
	    openGrid() {
	      main_core_events.EventEmitter.emit(tasks_v2_const.EventName.OpenGrid, {
	        taskId: this.taskId,
	        type: this.meta.id
	      });
	    },
	    showDialog() {
	      this.meta.dialog.setTaskId(this.taskId).showTo(this.$refs.add.$el);
	    },
	    async handleRemove(id) {
	      await this.meta.service.delete(this.taskId, [id]);
	    }
	  },
	  template: `
		<div
			class="tasks-field-relation-tasks"
			:data-task-id="taskId"
			:data-task-field-id="meta.id"
		>
			<div class="tasks-field-relation-tasks-title">
				<div
					class="tasks-field-relation-tasks-main"
					data-task-relation-open
					@click="handleTitleClick"
				>
					<BIcon :name="meta.icon"/>
					<TextMd :accent="true">{{ text }}</TextMd>
				</div>
				<div
					v-if="!readonly"
					v-hint="tooltip"
					class="tasks-field-relation-tasks-add-container"
				>
					<BIcon
						class="tasks-field-relation-tasks-icon"
						:name="Outline.PLUS_L"
						:hoverable="true"
						:data-task-relation-add="meta.id"
						ref="add"
						@click="showDialog"
					/>
				</div>
			</div>
			<TaskList
				v-if="task[meta.containsField]"
				:taskIds="ids"
				:context="context"
				:readonly="readonly"
				@openMore="openGrid"
				@removeTask="handleRemove"
			/>
		</div>
	`
	};

	// @vue/component
	const RelationTasksChip = {
	  components: {
	    Chip: tasks_v2_component_elements_chip.Chip
	  },
	  props: {
	    taskId: {
	      type: [Number, String],
	      required: true
	    },
	    /** @type RelationMeta */
	    meta: {
	      type: Object,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    task() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/getById`](this.taskId);
	    },
	    count() {
	      return this.task[this.meta.idsField].length;
	    },
	    design() {
	      if (this.disabled) {
	        return tasks_v2_component_elements_chip.ChipDesign.ShadowDisabled;
	      }
	      return this.isSelected ? tasks_v2_component_elements_chip.ChipDesign.ShadowAccent : tasks_v2_component_elements_chip.ChipDesign.ShadowNoAccent;
	    },
	    isSelected() {
	      return this.$store.getters[`${tasks_v2_const.Model.Tasks}/wasFieldFilled`](this.taskId, this.meta.id);
	    },
	    readonly() {
	      return !this.task.rights.edit;
	    }
	  },
	  methods: {
	    handleClick() {
	      if (this.disabled) {
	        return;
	      }
	      if (this.isSelected) {
	        this.highlightField();
	        return;
	      }
	      this.meta.dialog.setTaskId(this.taskId).onUpdateOnce(this.highlightField).showTo(this.$el);
	    },
	    highlightField() {
	      void tasks_v2_lib_fieldHighlighter.fieldHighlighter.setContainer(this.$root.$el).highlight(this.meta.id);
	    }
	  },
	  template: `
		<Chip
			v-if="isSelected || !readonly"
			:design="design"
			:text="meta.chipTitle"
			:icon="meta.icon"
			:data-task-id="taskId"
			:data-task-chip-id="meta.id"
			ref="chip"
			@click="handleClick"
		/>
	`
	};

	exports.RelationTasks = RelationTasks;
	exports.RelationTasksChip = RelationTasksChip;

}((this.BX.Tasks.V2.Component.Fields = this.BX.Tasks.V2.Component.Fields || {}),BX.Event,BX.Vue3.Directives,BX.UI.System.Typography.Vue,BX.UI.IconSet,BX,BX.Tasks.V2.Component,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Const,BX.Tasks.V2.Component.Elements,BX.Tasks.V2.Lib));
//# sourceMappingURL=relation-tasks.bundle.js.map
