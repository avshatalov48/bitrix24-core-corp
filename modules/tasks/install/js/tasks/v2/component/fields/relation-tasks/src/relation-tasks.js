import { EventEmitter } from 'main.core.events';
import { hint, type HintParams } from 'ui.vue3.directives.hint';
import { TextMd } from 'ui.system.typography.vue';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.actions';

import { EventName, Model } from 'tasks.v2.const';
import { TaskList } from 'tasks.v2.component.task-list';
import { tooltip } from 'tasks.v2.component.elements.hint';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { RelationMeta } from './types';
import './relation-tasks.css';

// @vue/component
export const RelationTasks = {
	name: 'TaskRelationTasks',
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
		/** @type RelationMeta */
		meta: {
			type: Object,
			required: true,
		},
	},
	setup(): { meta: RelationMeta }
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
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
		ids(): number[]
		{
			return [...this.task[this.meta.idsField]].sort((a, b) => b - a);
		},
		text(): string
		{
			if (this.ids.length > 0)
			{
				return this.loc(this.meta.countLoc, {
					'#COUNT#': this.ids.length,
				});
			}

			return this.meta.title;
		},
		context(): string
		{
			return this.meta.id;
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.meta.hint,
				popupOptions: {
					offsetLeft: this.$refs.add.$el.offsetWidth / 2,
				},
			});
		},
	},
	watch: {
		ids(newIds: number[], oldIds: number[]): void
		{
			if ([...newIds || []].sort().join(',') === [...oldIds || []].sort().join(','))
			{
				return;
			}

			if (this.meta.service.hasUnloadedIds(this.taskId))
			{
				void this.meta.service.list(this.taskId);
			}
		},
	},
	created(): void
	{
		if (!this.meta.service.areIdsLoaded(this.taskId) || this.meta.service.hasUnloadedIds(this.taskId))
		{
			void this.meta.service.list(this.taskId, true);
		}
	},
	methods: {
		handleTitleClick(): void
		{
			if (this.isEdit && (this.readonly || this.task[this.meta.containsField]))
			{
				this.openGrid();

				return;
			}

			this.showDialog();
		},
		openGrid(): void
		{
			EventEmitter.emit(EventName.OpenGrid, {
				taskId: this.taskId,
				type: this.meta.id,
			});
		},
		showDialog(): void
		{
			this.meta.dialog.setTaskId(this.taskId).showTo(this.$refs.add.$el);
		},
		async handleRemove(id: number): void
		{
			await this.meta.service.delete(this.taskId, [id]);
		},
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
	`,
};
