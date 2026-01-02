import { Loc } from 'main.core';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model, Limit } from 'tasks.v2.const';

import { TaskLine } from './task-line';
import { TaskLineSkeleton } from './task-line-skeleton';
import './task-list.css';

const limit = Limit.RelationList;

// @vue/component
export const TaskList = {
	components: {
		BIcon,
		TaskLine,
		TaskLineSkeleton,
	},
	props: {
		taskIds: {
			type: Array,
			required: true,
		},
		context: {
			type: String,
			required: true,
		},
		readonly: {
			type: Boolean,
			required: true,
		},
	},
	emits: ['openMore', 'removeTask'],
	setup(): Object
	{
		return {
			Outline,
		};
	},
	computed: {
		limitedTasks(): number[]
		{
			return this.taskIds.slice(0, limit);
		},
		shouldShowMore(): boolean
		{
			return this.taskIds.length > limit;
		},
		moreText(): string
		{
			const count = this.taskIds.length - limit;

			return Loc.getMessagePlural('TASKS_V2_TASK_LIST_MORE', count, {
				'#COUNT#': count,
			});
		},
	},
	methods: {
		isLast(index: number): boolean
		{
			return index === (Math.min(this.taskIds.length, limit) - 1);
		},
		isTaskLoaded(taskId: number): boolean
		{
			return Boolean(this.$store.getters[`${Model.Tasks}/getById`](taskId));
		},
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
	`,
};
