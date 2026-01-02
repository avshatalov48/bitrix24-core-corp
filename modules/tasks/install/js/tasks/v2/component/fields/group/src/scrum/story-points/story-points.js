import { hint, type HintParams } from 'ui.vue3.directives.hint';

import { tooltip } from 'tasks.v2.component.elements.hint';
import { Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './story-points.css';

// @vue/component
export const StoryPoints = {
	directives: { hint },
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): Object
	{
		return {
			isFocused: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		storyPoints(): string
		{
			return this.task.storyPoints?.trim();
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.loc('TASKS_V2_GROUP_STORY_POINTS_HINT'),
				popupOptions: {
					offsetLeft: this.$refs.storyPoints.offsetWidth / 2,
				},
			});
		},
	},
	methods: {
		async handleClick(): Promise<void>
		{
			this.isFocused = true;

			await this.$nextTick();

			this.$refs.input.focus();
		},
		handleBlur(): void
		{
			this.isFocused = false;

			void taskService.update(
				this.taskId,
				{
					storyPoints: this.$refs.input.value.trim(),
				},
			);
		},
	},
	template: `
		<input
			v-if="isFocused"
			class="tasks-field-group-scrum-story-points-input"
			:value="storyPoints"
			ref="input"
			@blur="handleBlur"
		/>
		<div
			v-else
			v-hint="tooltip"
			class="tasks-field-group-scrum-story-points"
			:class="{ '--filled': storyPoints }"
			ref="storyPoints"
			@click="handleClick"
		>
			{{ storyPoints || '-' }}
		</div>
	`,
};
