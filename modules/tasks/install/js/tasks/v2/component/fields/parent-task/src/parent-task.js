import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { TextMd } from 'ui.system.typography.vue';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { TaskList } from 'tasks.v2.component.task-list';
import { tooltip } from 'tasks.v2.component.elements.hint';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { subTasksService } from 'tasks.v2.provider.service.relation-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { parentTaskMeta } from './parent-task-meta';
import { parentTaskDialog } from './parent-task-dialog';

import './parent-task.css';

// @vue/component
export const ParentTask = {
	name: 'TaskParentTask',
	components: {
		BIcon,
		TaskList,
		TextMd,
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
			parentTaskMeta,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		parentTask(): ?TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.parentId);
		},
		parentId(): number
		{
			return this.task.parentId;
		},
		hasParent(): boolean
		{
			return this.task.parentId > 0;
		},
		parentTaskIds(): number[]
		{
			return this.hasParent ? [this.task.parentId] : [];
		},
		text(): string
		{
			return parentTaskMeta.title;
		},
		context(): string
		{
			return parentTaskMeta.id;
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_PARENT_TASK_SELECT'),
				popupOptions: {
					offsetLeft: this.$refs.add.$el.offsetWidth / 2,
				},
			});
		},
	},
	watch: {
		parentId: {
			immediate: true,
			handler(): void
			{
				if (this.hasParent)
				{
					void this.loadParentTask();
				}
			},
		},
	},
	methods: {
		async loadParentTask(): void
		{
			if (this.parentTask)
			{
				return;
			}

			await taskService.getById(this.parentId);
		},
		handleTitleClick(): void
		{
			if (this.readonly)
			{
				return;
			}

			parentTaskDialog.setTaskId(this.taskId).showTo(this.$refs.title);
		},
		handleEditClick(): void
		{
			parentTaskDialog.setTaskId(this.taskId).showTo(this.$refs.add.$el);
		},
		async handleRemoveParentTask(parentId): void
		{
			await subTasksService.delete(parentId, [this.taskId]);
		},
	},
	template: `
		<div
			class="tasks-field-parent-task"
			:data-task-id="taskId"
			:data-task-field-id="parentTaskMeta.id"
		>
			<div class="tasks-field-parent-task-title">
				<div
					class="tasks-field-parent-task-main"
					:class="{ '--readonly': readonly }"
					ref="title"
					@click="handleTitleClick"
				>
					<BIcon :name="Outline.SUBTASK"/>
					<TextMd :accent="true">{{ text }}</TextMd>
				</div>
				<div
					v-if="!readonly"
					v-hint="tooltip"
					class="tasks-field-parent-task-edit-container"
				>
					<BIcon
						class="tasks-field-parent-task-icon"
						:name="Outline.PLUS_L"
						:hoverable="true"
						:data-task-relation-add="parentTaskMeta.id"
						ref="add"
						@click="handleEditClick"
					/>
				</div>
			</div>
			<TaskList
				v-if="hasParent"
				:readonly="readonly"
				:context="context"
				:taskIds="parentTaskIds"
				@removeTask="handleRemoveParentTask"
			/>
		</div>
	`,
};
