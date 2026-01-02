import { FieldList } from 'tasks.v2.component.elements.field-list';
import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { DatePlanDate } from './components/date-plan-date';
import { DatePlanContent } from './components/date-plan-content';
import { datePlanMeta } from './date-plan-meta';
import './date-plan.css';

// @vue/component
export const DatePlan = {
	components: {
		FieldList,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['open'],
	setup(): Object
	{
		return {
			datePlanMeta,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		wasFilled(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, datePlanMeta.id);
		},
		fields(): string[]
		{
			const isEmpty = !this.task.startPlanTs && !this.task.endPlanTs;
			if (isEmpty && (this.wasFilled || this.task.matchesSubTasksTime))
			{
				return [{
					title: datePlanMeta.title,
					component: DatePlanContent,
					props: {
						taskId: this.taskId,
					},
				}];
			}

			return [
				{
					title: this.loc('TASKS_V2_DATE_PLAN_FIELD_START'),
					component: DatePlanDate,
					props: {
						dateTs: this.task.startPlanTs,
						readonly: this.readonly,
					},
				},
				{
					title: this.loc('TASKS_V2_DATE_PLAN_FIELD_END'),
					component: DatePlanDate,
					props: {
						dateTs: this.task.endPlanTs,
						readonly: this.readonly,
					},
				},
			].filter(({ props: { dateTs } }) => dateTs);
		},
		readonly(): boolean
		{
			return !this.task.rights.datePlan;
		},
	},
	methods: {
		handleClick(): void
		{
			if (!this.readonly)
			{
				this.$emit('open');
			}
		},
	},
	template: `
		<div
			class="tasks-field-date-plan"
			:class="{ '--readonly': readonly }"
			:data-task-id="taskId"
			:data-task-field-id="datePlanMeta.id"
			:data-task-plan-start="task.startPlanTs"
			:data-task-plan-end="task.endPlanTs"
			@click="handleClick"
		>
			<FieldList :fields="fields"/>
		</div>
	`,
};
