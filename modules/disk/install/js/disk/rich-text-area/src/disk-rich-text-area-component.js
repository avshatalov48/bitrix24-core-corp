import { Extension, Type } from 'main.core';

import {
	RichTextAreaComponent,
	FileButton,
	CreateDocumentButton,
	type RichTextArea,
	type RichTextAreaWidgetOptions,
} from 'ui.rich-text-area';

import {
	UserFieldWidgetComponent,
	type UserFieldWidgetOptions,
	type UserFieldControl,
} from 'disk.uploader.user-field-widget';

import { TextEditor, type TextEditorOptions } from 'ui.text-editor';
import { Uploader, type UploaderOptions } from 'ui.uploader.core';

import type { BaseEvent } from 'main.core.events';
import type { BitrixVueComponentProps } from 'ui.vue3';


/**
 * @memberof BX.UI.RichTextArea
 */
export const DiskRichTextAreaComponent: BitrixVueComponentProps = {
	name: 'DiskRichTextAreaComponent',
	components: {
		RichTextAreaComponent,
		UserFieldWidgetComponent,
		FileButton,
		CreateDocumentButton,
	},
	props: {
		editorOptions: {
			type: Object,
		},
		editorInstance: {
			type: TextEditor,
		},
		uploaderOptions: {
			type: Object,
		},
		uploaderInstance: {
			type: Uploader,
		},
		widgetOptions: {
			type: Object,
			default: {},
		},
		files: {
			type: Array,
			default: [],
		},
	},
	data() {
		return {
			fileCount: 0,
			panelVisibility: 'hidden',
		};
	},
	methods: {
		getUploaderOptions(): UploaderOptions
		{
			return {
				imagePreviewHeight: 1200, // double size (see DiskUploaderController)
				imagePreviewWidth: 1200,
				imagePreviewQuality: 0.85,
				treatOversizeImageAsFile: true,
				ignoreUnknownImageTypes: true,
				controller: 'disk.uf.integration.diskUploaderController',
				multiple: true,
				maxFileSize: null,
				...(Type.isPlainObject(this.uploaderOptions) ? this.uploaderOptions : {}),
			};
		},
		getUploaderInstance(): Uploader
		{
			return this.uploaderInstance;
		},
		getEditorOptions(): TextEditorOptions
		{
			const editorOptions: TextEditorOptions = (
				Type.isPlainObject(this.editorOptions) ? { ...this.editorOptions } : {}
			);

			editorOptions.file = editorOptions.file || {};
			editorOptions.file.mode = 'disk';

			return editorOptions;
		},
		getEditorInstance(): TextEditor
		{
			return this.editorInstance;
		},
		getRichTextAreaWidgetOptions(): RichTextAreaWidgetOptions
		{
			const richTextOptions: RichTextAreaWidgetOptions = this.widgetOptions.richTextOptions || {};

			return {
				...richTextOptions,
				events: [{
					'Item:onAdd': (event: BaseEvent<{ fileCount: number }>): void => {
						this.fileCount = event.getData().fileCount;
						this.panelVisibility = 'uploader';
					},
					'Item:onRemove': (event: BaseEvent<{ fileCount: number }>): void => {
						this.fileCount = event.getData().fileCount;
					},
					'Item:onInsertChange': (event: BaseEvent<{ hasInsertedItems: boolean }>): void => {
						if (this.getUserFieldControl().getPhotoTemplateMode() === 'auto')
						{
							this.getUserFieldControl().setPhotoTemplate(
								event.getData().hasInsertedItems ? 'gallery' : 'grid',
							);
						}
					},
				}, richTextOptions.events],
			};
		},
		getRichTextArea(): RichTextArea
		{
			return this.$refs.richTextArea.getRichTextArea();
		},
		getEditor(): TextEditor
		{
			return this.$refs.richTextArea.getEditor();
		},
		getUploader(): Uploader
		{
			return this.$refs.richTextArea.getUploader();
		},
		getUserFieldControl(): UserFieldControl
		{
			return this.$refs.userFieldWidget.getUserFieldControl();
		},
		handleFileClick()
		{
			if (this.panelVisibility === 'uploader')
			{
				this.panelVisibility = 'hidden';
			}
			else
			{
				this.panelVisibility = 'uploader';
			}
		},
		handleCreateDocumentClick()
		{
			if (this.panelVisibility === 'documents')
			{
				this.panelVisibility = 'hidden';
			}
			else
			{
				this.panelVisibility = 'documents';
			}
		},
	},
	computed: {
		userFieldWidgetOptions(): UserFieldWidgetOptions
		{
			const options: UserFieldWidgetOptions = {
				insertIntoText: true,
				...this.widgetOptions,
			};

			options.tileWidgetOptions = options.tileWidgetOptions || {};
			options.tileWidgetOptions.enableDropzone = false;

			if (options.insertIntoText)
			{
				options.tileWidgetOptions.events = options.tileWidgetOptions.events || {};
				options.tileWidgetOptions.events.onInsertIntoText = (event: BaseEvent) => {
					this.getRichTextArea().insertFile(event.getData().item);
				};
			}

			// Just in case
			delete options.files; // use 'files' prop
			delete options.eventObject;
			delete options.mainPostFormId;

			return options;
		},
		canCreateDocuments(): boolean
		{
			const settings = Extension.getSettings('disk.uploader.user-field-widget');
			const canCreateDocuments = settings.get('canCreateDocuments', false);

			return canCreateDocuments && this.widgetOptions.canCreateDocuments !== false;
		},
	},
	// language=Vue
	template: `
		<RichTextAreaComponent
			:editor-options="getEditorOptions()"
			:editor-instance="getEditorInstance()"
			:uploader-options="getUploaderOptions()"
			:uploader-instance="getUploaderInstance()"
			:widget-options="getRichTextAreaWidgetOptions()"
			:files="files"
			ref="richTextArea"
		>
			<template #uploader="{ adapter }">
				<UserFieldWidgetComponent
					:widgetOptions="userFieldWidgetOptions"
					:uploader-adapter="adapter"
					:visibility="panelVisibility"
					ref="userFieldWidget"
				/>
			</template>

			<template #after-buttons v-if="$slots['after-buttons']">
				<slot name="after-buttons"></slot>
			</template>
			<template #file-button>
				<FileButton
					:selected="this.panelVisibility === 'uploader'"
					:counter="fileCount"
					@click="handleFileClick" />
				<CreateDocumentButton
					v-if="canCreateDocuments"
					:selected="this.panelVisibility === 'documents'"
					@click="handleCreateDocumentClick"
				/>
			</template>
			<template #before-buttons v-if="$slots['before-buttons']">
				<slot name="before-buttons"></slot>
			</template>
		</RichTextAreaComponent>
	`,
};
