import { Event } from 'main.core';

import { Button as UiButton, ButtonSize } from 'ui.vue3.components.button';

import { Hint } from 'tasks.v2.component.elements.hint';
import { Model, TaskField } from 'tasks.v2.const';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import type { TaskModel } from 'tasks.v2.model.tasks';
import { fileService, EntityTypes } from 'tasks.v2.provider.service.file-service';

import './add-task-button.css';

// @vue/component
export const AddTaskButton = {
	name: 'AddTaskButton',
	components: {
		UiButton,
		Hint,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		size: {
			type: String,
			default: ButtonSize.LARGE,
		},
		hasError: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'addTask',
		'update:hasError',
	],
	data(): Object
	{
		return {
			fieldContainer: null,
			isPopupShown: false,
			isLoading: false,
			errorReason: null,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isUploading(): boolean
		{
			return fileService.get(this.taskId).isUploading();
		},
		isCheckListUploading(): boolean
		{
			return this.task.checklist?.some((itemId) => fileService
				.get(itemId, EntityTypes.CheckListItem).isUploading());
		},
		isDisabled(): boolean
		{
			return (
				this.task.title.trim() === ''
				|| this.isUploading
				|| this.isCheckListUploading
				|| this.isLoading
			);
		},
	},
	watch: {
		hasError(value: boolean): void
		{
			if (value === true)
			{
				this.isLoading = false;
			}
		},
	},
	methods: {
		handleClick(): void
		{
			if (!this.isDisabled)
			{
				this.isLoading = true;
				this.$emit('update:hasError', false);
				this.$emit('addTask');

				return;
			}

			if (this.task.title.trim() === '')
			{
				setTimeout(() => this.highlightTitle());
			}
			else if (this.isUploading)
			{
				setTimeout(() => this.highlightFiles());
			}
			else if (this.isCheckListUploading)
			{
				setTimeout(() => this.highlightChecklist());
			}
		},
		highlightTitle(): void
		{
			this.errorReason = this.loc('TASKS_V2_TITLE_IS_EMPTY');

			this.fieldContainer = fieldHighlighter
				.setContainer(this.$root.$el)
				.addHighlight(TaskField.Title)
				.getFieldContainer(TaskField.Title)
			;

			this.fieldContainer.querySelector('textarea').focus();

			this.showPopup();
		},
		highlightFiles(): void
		{
			this.errorReason = this.loc('TASKS_V2_FILE_IS_UPLOADING');

			this.fieldContainer = fieldHighlighter
				.setContainer(this.$root.$el)
				.addChipHighlight(TaskField.Files)
				.getChipContainer(TaskField.Files)
			;

			this.showPopup();
		},
		highlightChecklist(): void
		{
			this.errorReason = this.loc('TASKS_V2_FILE_IS_UPLOADING');

			this.fieldContainer = fieldHighlighter
				.setContainer(this.$root.$el)
				.addChipHighlight(TaskField.CheckList)
				.getChipContainer(TaskField.CheckList)
			;

			this.showPopup();
		},
		showPopup(): void
		{
			const removeHighlight = () => {
				this.isPopupShown = false;
				Event.unbind(window, 'keydown', removeHighlight);
			};
			Event.bind(window, 'keydown', removeHighlight);

			this.isPopupShown = true;
		},
	},
	template: `
		<div
			class="tasks-add-task-button-container"
			:class="{ '--disabled': isDisabled }"
			:data-task-id="taskId"
			data-task-button-id="create"
			@click="handleClick"
		>
			<UiButton
				class="tasks-add-task-button"
				:text="loc('TASKS_V2_ADD_TASK')"
				:size="size"
				:disabled="isDisabled"
				:loading="isLoading && !hasError"
			/>
		</div>
		<Hint
			v-if="isPopupShown"
			:bindElement="fieldContainer"
			@close="isPopupShown = false"
		>
			{{ errorReason }}
		</Hint>
	`,
};
