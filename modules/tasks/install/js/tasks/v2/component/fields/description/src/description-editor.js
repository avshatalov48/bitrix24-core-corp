import { Type } from 'main.core';

import { BIcon, Outline } from 'ui.icon-set.api.vue';
import { TextEditor } from 'ui.text-editor';
import { Button as UiButton, ButtonColor, ButtonSize } from 'ui.vue3.components.button';

import { TaskModel } from 'tasks.v2.model.tasks';
import { Model } from 'tasks.v2.const';
import { fileService, type FileService } from 'tasks.v2.provider.service.file-service';
import { descriptionTextEditor, type DescriptionTextEditor } from './description-text-editor';

import { DescriptionTextArea } from './description-text-area';
import { Copilot } from './actions/copilot';
import { Attach } from './actions/attach';
import { Mention } from './actions/mention';

import './description.css';

// @vue/component
export const DescriptionEditor = {
	name: 'TaskDescriptionContent',
	components: {
		BIcon,
		UiButton,
		Copilot,
		Attach,
		Mention,
		DescriptionTextArea,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['close', 'show'],
	setup(props): { fileService: FileService, descriptionTextEditor: DescriptionTextEditor }
	{
		return {
			ButtonSize,
			ButtonColor,
			Outline,
			fileService: fileService.get(props.taskId),
			descriptionTextEditor: descriptionTextEditor.get(props.taskId),
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		readonly(): boolean
		{
			return !this.task.rights.edit;
		},
		editor(): TextEditor
		{
			return this.descriptionTextEditor.getEditor();
		},
	},
	mounted(): void
	{
		this.$emit('show');

		if (!Type.isStringFilled(this.task.description) || this.taskId === 0)
		{
			setTimeout(this.focusToEnd, 500);
		}
	},
	methods: {
		handleClose(): void
		{
			this.$emit('close');
		},
		focusToEnd(): void
		{
			this.editor.focus(null, { defaultSelection: 'rootEnd' });
		},
		handleMentionButtonClick(): void
		{
			this.editor.focus(
				() => {
					this.editor.dispatchCommand(
						BX.UI.TextEditor.Plugins.Mention.INSERT_MENTION_DIALOG_COMMAND,
					);
				},
				{ defaultSelection: 'rootEnd' },
			);
		},
		handleAttachButtonClick(): void
		{
			this.fileService.browse({
				bindElement: this.$refs.attach.$el,
				onHideCallback: this.onFileBrowserClose,
			});
		},
		onFileBrowserClose(): void
		{
			this.fileService.setFileBrowserClosed(false);
		},
	},
	template: `
		<div class="tasks-card-description-wrapper" ref="wrapper">
			<div class="tasks-card-description-header" ref="descriptionHeader">
				<div class="tasks-card-description-title">
					{{ loc('TASKS_V2_CHANGE_DESCRIPTION') }}
				</div>
				<BIcon
					:name="Outline.CROSS_L"
					:hoverable="true"
					class="tasks-card-description-field-icon"
					@click="handleClose"
				/>
			</div>
			<div class="tasks-card-description-editor-wrapper" id="descriptionTextAreaDestination"/>
			<div v-if="!readonly" class="tasks-card-description-footer" ref="descriptionActions">
				<div class="tasks-card-description-action-list">
					<Copilot />
					<Attach ref="attach" @click="handleAttachButtonClick"/>
					<Mention @click="handleMentionButtonClick"/>
				</div>
				<div class="tasks-card-description-footer-buttons">
					<UiButton
						:text="loc('TASKS_V2_DESCRIPTION_BUTTON_SAVE')"
						:size="ButtonSize.MEDIUM"
						:color="ButtonColor.PRIMARY"
						@click="handleClose"
					/>
				</div>
			</div>
		</div>
	`,
};
