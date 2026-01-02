import { BaseEvent } from 'main.core.events';
import { Type } from 'main.core';

import type { UserFieldWidgetOptions } from 'disk.uploader.user-field-widget';
import { UserFieldWidgetComponent } from 'disk.uploader.user-field-widget';

import { TextEditorComponent, type TextEditor } from 'ui.text-editor';
import type { VueUploaderAdapter } from 'ui.uploader.vue';

import { Model } from 'tasks.v2.const';
import { TaskModel } from 'tasks.v2.model.tasks';
import { fileService } from 'tasks.v2.provider.service.file-service';
import type { FileService } from 'tasks.v2.provider.service.file-service';

import { type DescriptionTextEditor, descriptionTextEditor } from './description-text-editor';

// @vue/component
export const DescriptionTextArea = {
	name: 'TaskDescriptionTextArea',
	components: {
		TextEditorComponent,
		UserFieldWidgetComponent,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['change', 'filesChange'],
	setup(props): {
		editor: TextEditor,
		descriptionTextEditor: DescriptionTextEditor,
		uploaderAdapter: VueUploaderAdapter,
		fileService: FileService,
	}
	{
		return {
			/** @type TextEditor */
			editor: null,
			descriptionTextEditor: descriptionTextEditor.get(props.taskId),
			fileService: fileService.get(props.taskId),
			uploaderAdapter: fileService.get(props.taskId).getAdapter(),
		};
	},
	data(): { files: Array<Object> }
	{
		return {
			files: this.fileService.getFiles(),
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
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		widgetOptions(): UserFieldWidgetOptions
		{
			return {
				isEmbedded: true,
				withControlPanel: false,
				canCreateDocuments: false,
				insertIntoText: true,
				tileWidgetOptions: {
					compact: true,
					hideDropArea: true,
					readonly: this.readonly,
					autoCollapse: false,
					removeFromServer: !this.isEdit,
					events: {
						onInsertIntoText: this.handleInsertFile,
					},
				},
			};
		},
		filesCount(): number
		{
			return this.files.length;
		},
	},
	created(): Promise<void>
	{
		this.editor = this.descriptionTextEditor.getEditor();
	},
	mounted(): void
	{
		this.fileService.subscribe('onFileAdd', this.onFileAdd);
		this.fileService.subscribe('onFileRemove', this.onFileRemove);
		this.descriptionTextEditor.subscribe('editorChanged', this.onEditorChange);
	},
	unmounted(): void
	{
		this.fileService.unsubscribe('onFileAdd', this.onFileAdd);
		this.fileService.unsubscribe('onFileRemove', this.onFileRemove);
		this.descriptionTextEditor.unsubscribe('editorChanged', this.onEditorChange);
	},
	methods: {
		onFileAdd(): void
		{
			this.$emit('filesChange');
		},
		onFileRemove(): void
		{
			this.$emit('filesChange');
		},
		onEditorChange(): void
		{
			this.$emit('change');
		},
		handleInsertFile(event: BaseEvent): void
		{
			const fileInfo = event.getData().item;

			this.descriptionTextEditor.insertFile(fileInfo);
		},
	},
	template: `
		<div class="tasks-card-description-text-area-wrapper" ref="editorWrapper">
			<TextEditorComponent :editorInstance="editor">
				<template v-if="filesCount > 0" #footer>
					<div id="descriptionEditorFiles" class="tasks-card-description-editor-files" ref="filesWrapper">
						<UserFieldWidgetComponent
							:uploaderAdapter="uploaderAdapter"
							:widgetOptions="widgetOptions"
						/>
					</div>
				</template>
			</TextEditorComponent>
		</div>
	`,
};
