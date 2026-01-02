import type { PopupOptions } from 'main.popup';
import { Popup } from 'ui.vue3.components.popup';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { DeadlinePopupContent } from './deadline-popup-content';

// @vue/component
export const DeadlinePopup = {
	components: {
		Popup,
		DeadlinePopupContent,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	emits: ['update', 'close'],
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				id: `tasks-field-deadline-popup-${this.taskId}`,
				bindElement: this.bindElement,
				padding: 24,
				offsetTop: 5,
				offsetLeft: -100,
				targetContainer: document.body,
			};
		},
	},
	methods: {
		clear(): void
		{
			this.deadlineTs = null;
		},
		handleUpdate(dateTs: number): void
		{
			this.deadlineTs = dateTs;
			this.$emit('update', dateTs);
		},
		handleClose(): void
		{
			if (this.deadlineTs)
			{
				void taskService.update(
					this.taskId,
					{
						deadlineTs: this.deadlineTs,
					},
				);
			}

			this.$emit('close');
		},
	},
	template: `
		<Popup
			:options="popupOptions"
			@close="handleClose"
		>
			<DeadlinePopupContent :taskId="taskId" @update="handleUpdate" @close="handleClose"/>
		</Popup>
	`,
};
