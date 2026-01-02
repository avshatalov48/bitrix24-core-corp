import { Type } from 'main.core';

import type { VueUploaderAdapter } from 'ui.uploader.vue';

import { TaskModel } from 'tasks.v2.model.tasks';
import { Model } from 'tasks.v2.const';
import { fileService } from 'tasks.v2.provider.service.file-service';
import type { FileService } from 'tasks.v2.provider.service.file-service';

import { MiniFormButton } from './mini-form-button.js';
import { MiniForm } from './mini-form.js';
import { DescriptionPreview } from './description-preview';

import './description.css';

// @vue/component
export const DescriptionField = {
	name: 'TaskDescriptionField',
	components: {
		MiniFormButton,
		MiniForm,
		DescriptionPreview,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	setup(props): { fileService: FileService, uploaderAdapter: VueUploaderAdapter }
	{
		return {
			fileService: fileService.get(props.taskId),
			uploaderAdapter: fileService.get(props.taskId).getAdapter(),
		};
	},
	data(): Object
	{
		return {
			isMiniFormShown: false,
			isSlotShown: false,
			doOpenInEditMode: false,
			files: this.fileService.getFiles(),
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		filesCount(): number
		{
			return this.files.length;
		},
	},
	methods: {
		openSlotInEditMode(): void
		{
			this.doOpenInEditMode = true;
			this.isSlotShown = true;
		},
		closeMiniForm(): void
		{
			this.isMiniFormShown = false;
		},
		onPreviewButtonClick(eventData): void
		{
			this.doOpenInEditMode = eventData.doOpenInEditMode === true;
			this.isMiniFormShown = true;
		},
		closeSlot(): void
		{
			this.isSlotShown = false;
		},
		async save(): Promise<void>
		{
			await this.$refs?.miniForm?.handleAddButtonClick();
		},
	},
	template: `
		<slot
			:isShown="isSlotShown"
			:doOpenInEditMode="doOpenInEditMode"
			:close="closeSlot"
		/>
		<div
			v-if="!readonly || task.description.length > 0 || filesCount > 0"
			class="tasks-card-description-field"
			:data-task-id="taskId"
			:data-task-field-id="'description'"
		>
			<MiniFormButton
				v-if="(task.description.length === 0) && !isMiniFormShown"
				:filesCount="filesCount"
				@click="isMiniFormShown = true"
			/>
			<MiniForm
				v-else-if="!readonly && (isMiniFormShown || (task.description.length > 0 && !isEdit))"
				:taskId="taskId"
				:isSlotShown="isSlotShown"
				@expand="openSlotInEditMode"
				@closeEdit="closeMiniForm"
				ref="miniForm"
			/>
			<DescriptionPreview
				v-else-if="isMiniFormShown || isEdit"
				:taskId="taskId"
				:files="files"
				:filesCount="filesCount"
				:isMiniFormShown="isMiniFormShown"
				@previewButtonClick="onPreviewButtonClick"
			/>
		</div>
	`,
};
