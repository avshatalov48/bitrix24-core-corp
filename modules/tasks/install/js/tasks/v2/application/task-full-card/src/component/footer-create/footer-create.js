import { Event } from 'main.core';
import { Button as UiButton, AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';

import { Core } from 'tasks.v2.core';
import { EventName } from 'tasks.v2.const';
import { AddTaskButton } from 'tasks.v2.component.add-task-button';
import './footer-create.css';

// @vue/component
export const FooterCreate = {
	components: {
		UiButton,
		AddTaskButton,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['addTask'],
	setup(): Object
	{
		return {
			AirButtonStyle,
			ButtonSize,
		};
	},
	computed: {
		isTemplateEnabled(): boolean
		{
			return Core.getParams().features.isTemplateEnabled;
		},
	},
	methods: {
		close(): void
		{
			Event.EventEmitter.emit(EventName.CloseFullCard, { taskId: this.taskId });
		},
	},
	template: `
		<div class="tasks-full-card-footer-create">
			<div class="tasks-full-card-footer-main-buttons">
				<AddTaskButton :taskId="taskId" @addTask="$emit('addTask')"/>
				<UiButton
					:text="loc('TASKS_V2_TASK_FULL_CARD_CANCEL')"
					:size="ButtonSize.LARGE"
					:style="AirButtonStyle.PLAIN"
					data-task-cancel-button
					@click="close"
				/>
			</div>
			<UiButton
				v-if="isTemplateEnabled"
				class="tasks-full-card-footer-template-button"
				:text="loc('TASKS_V2_TASK_FULL_CARD_TEMPLATES')"
				:size="ButtonSize.SMALL"
				:style="AirButtonStyle.PLAIN_NO_ACCENT"
				:dropdown="true"
			/>
		</div>
	`,
};
