import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { FieldAdd } from 'tasks.v2.component.elements.field-add';
import { tooltip } from 'tasks.v2.component.elements.hint';
import type { TaskModel } from 'tasks.v2.model.tasks';

export const DatePlanContent = {
	components: {
		HoverPill,
		FieldAdd,
	},
	directives: { hint },
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Outline,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_DATE_PLAN_NO_SUBTASKS_HINT'),
				popupOptions: {
					offsetLeft: this.$refs.hint.offsetWidth / 2,
				},
				timeout: 100,
			});
		},
	},
	template: `
		<HoverPill v-if="task.matchesSubTasksTime" :readonly="true">
			<div class="tasks-field-date-plan-content">
				<div>{{ loc('TASKS_V2_DATE_PLAN_MATCH_SUBTASKS_TIME_STATE') }}</div>
				<div v-hint="tooltip" class="tasks-hint-badge" ref="hint">?</div>
			</div>
		</HoverPill>
		<FieldAdd v-else :icon="Outline.PLANNING"/>
	`,
};
