import { Runtime, Event, Type } from 'main.core';
import { Outline } from 'ui.icon-set.api.vue';
import { TextEditor, TextEditorComponent } from 'ui.text-editor';

import { TaskModel } from 'tasks.v2.model.tasks';
import { Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { fileService, type FileService } from 'tasks.v2.provider.service.file-service';
import { descriptionTextEditor, type DescriptionTextEditor } from './description-text-editor';

import { DescriptionTextArea } from './description-text-area';
import { Copilot } from './actions/copilot';
import { Attach } from './actions/attach';
import { Mention } from './actions/mention';
import { FullDescription } from './actions/full-description';

import './description.css';

// @vue/component
export const MiniForm = {
	name: 'TaskDescriptionMiniForm',
	components: {
		TextEditorComponent,
		Copilot,
		Attach,
		Mention,
		FullDescription,
		DescriptionTextArea,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isSlotShown: {
			type: Boolean,
			required: true,
		},
	},
	emits: ['expand', 'closeEdit'],
	setup(props): { fileService: FileService, descriptionTextEditor: DescriptionTextEditor }
	{
		return {
			Outline,
			fileService: fileService.get(props.taskId),
			descriptionTextEditor: descriptionTextEditor.get(props.taskId),
		};
	},
	data(): Object
	{
		return {
			isNeedTeleport: false,
			hasChanges: false,
			closed: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		taskDescription(): string
		{
			return this.task.description ?? '';
		},
		editor(): TextEditor
		{
			return this.descriptionTextEditor.getEditor();
		},
	},
	watch: {
		isSlotShown(newValue): void
		{
			this.handleTeleport(newValue);
		},
	},
	async created(): Promise<void>
	{
		this.saveDebounced = Runtime.debounce(this.handleSave, 30000, this);

		Event.bind(window, 'beforeunload', this.handleSave);
	},
	mounted(): void
	{
		if (!Type.isStringFilled(this.taskDescription))
		{
			this.focusToEnd();
		}
	},
	async beforeUnmount(): Promise<void>
	{
		Event.unbind(window, 'beforeunload', this.handleSave);

		await this.handleSave();

		this.closed = true;
	},
	methods: {
		focusToEnd(): void
		{
			this.editor.focus(null, { defaultSelection: 'rootEnd' });
		},
		handleExpand(): void
		{
			this.$emit('expand');
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
		handleEditorChange(): void
		{
			this.hasChanges = this.taskDescription !== this.getEditorText();

			void this.saveDebounced();
		},
		async handleAddButtonClick(): Promise<void>
		{
			await this.save();
		},
		getEditorText(): string | null
		{
			return this.editor?.getText()
				.replaceAll(/\[p]\n|\[p]\[\/p]|\[\/p]/gi, '')
				.trim()
			;
		},
		async handleSave(): Promise<void>
		{
			if (this.closed || !this.hasChanges || !this.editor)
			{
				return;
			}

			await this.save();

			this.hasChanges = false;
		},
		async save(): Promise<void>
		{
			await taskService.update(
				this.taskId,
				{ description: this.editor.getText() },
			);
		},
		handleTeleport(isSlotShown): void
		{
			if (isSlotShown === true)
			{
				setTimeout(() => {
					this.isNeedTeleport = true;
				}, 100);

				this.editor.setVisualOptions({ blockSpaceInline: 0 });
			}
			else
			{
				this.isNeedTeleport = false;
				this.editor.setMaxHeight(null);
				this.editor.setVisualOptions({ blockSpaceInline: 'var(--ui-space-stack-md2)' });
			}
		},
		onFileBrowserClose(): void
		{
			this.fileService.setFileBrowserClosed(false);
		},
	},
	template: `
		<div class="tasks-card-change-description-mini-container">
			<div
				class="tasks-full-card-field-container --description-preview"
				ref="container"
				tabindex="-1"
			>
				<Teleport :to="isNeedTeleport ? '#descriptionTextAreaDestination' : undefined" :disabled="!isNeedTeleport">
					<DescriptionTextArea
						:taskId="taskId"
						ref="descriptionTextArea"
						@change="handleEditorChange"
						@filesChange="handleEditorChange"
					/>
				</Teleport>
				<div class="tasks-card-description-footer-container">
					<div class="tasks-card-description-footer">
						<div class="tasks-card-description-action-list">
							<Copilot />
							<Attach ref="attach" @click="handleAttachButtonClick"/>
							<Mention @click="handleMentionButtonClick"/>
						</div>
						<div
							class="tasks-card-description-footer-buttons"
							ref="fullDescriptionArea"
						>
							<FullDescription @click="handleExpand"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
