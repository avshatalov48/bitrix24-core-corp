/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,ui_richTextArea,disk_uploader_userFieldWidget,ui_textEditor,ui_uploader_core) {
	'use strict';

	/**
	 * @memberof BX.UI.RichTextArea
	 */
	const DiskRichTextAreaComponent = {
	  name: 'DiskRichTextAreaComponent',
	  components: {
	    RichTextAreaComponent: ui_richTextArea.RichTextAreaComponent,
	    UserFieldWidgetComponent: disk_uploader_userFieldWidget.UserFieldWidgetComponent,
	    FileButton: ui_richTextArea.FileButton,
	    CreateDocumentButton: ui_richTextArea.CreateDocumentButton
	  },
	  props: {
	    editorOptions: {
	      type: Object
	    },
	    editorInstance: {
	      type: ui_textEditor.TextEditor
	    },
	    uploaderOptions: {
	      type: Object
	    },
	    uploaderInstance: {
	      type: ui_uploader_core.Uploader
	    },
	    widgetOptions: {
	      type: Object,
	      default: {}
	    },
	    files: {
	      type: Array,
	      default: []
	    }
	  },
	  data() {
	    return {
	      fileCount: 0,
	      panelVisibility: 'hidden'
	    };
	  },
	  methods: {
	    getUploaderOptions() {
	      return {
	        imagePreviewHeight: 1200,
	        // double size (see DiskUploaderController)
	        imagePreviewWidth: 1200,
	        imagePreviewQuality: 0.85,
	        treatOversizeImageAsFile: true,
	        ignoreUnknownImageTypes: true,
	        controller: 'disk.uf.integration.diskUploaderController',
	        multiple: true,
	        maxFileSize: null,
	        ...(main_core.Type.isPlainObject(this.uploaderOptions) ? this.uploaderOptions : {})
	      };
	    },
	    getUploaderInstance() {
	      return this.uploaderInstance;
	    },
	    getEditorOptions() {
	      const editorOptions = main_core.Type.isPlainObject(this.editorOptions) ? {
	        ...this.editorOptions
	      } : {};
	      editorOptions.file = editorOptions.file || {};
	      editorOptions.file.mode = 'disk';
	      return editorOptions;
	    },
	    getEditorInstance() {
	      return this.editorInstance;
	    },
	    getRichTextAreaWidgetOptions() {
	      const richTextOptions = this.widgetOptions.richTextOptions || {};
	      return {
	        ...richTextOptions,
	        events: [{
	          'Item:onAdd': event => {
	            this.fileCount = event.getData().fileCount;
	            this.panelVisibility = 'uploader';
	          },
	          'Item:onRemove': event => {
	            this.fileCount = event.getData().fileCount;
	          },
	          'Item:onInsertChange': event => {
	            if (this.getUserFieldControl().getPhotoTemplateMode() === 'auto') {
	              this.getUserFieldControl().setPhotoTemplate(event.getData().hasInsertedItems ? 'gallery' : 'grid');
	            }
	          }
	        }, richTextOptions.events]
	      };
	    },
	    getRichTextArea() {
	      return this.$refs.richTextArea.getRichTextArea();
	    },
	    getEditor() {
	      return this.$refs.richTextArea.getEditor();
	    },
	    getUploader() {
	      return this.$refs.richTextArea.getUploader();
	    },
	    getUserFieldControl() {
	      return this.$refs.userFieldWidget.getUserFieldControl();
	    },
	    handleFileClick() {
	      if (this.panelVisibility === 'uploader') {
	        this.panelVisibility = 'hidden';
	      } else {
	        this.panelVisibility = 'uploader';
	      }
	    },
	    handleCreateDocumentClick() {
	      if (this.panelVisibility === 'documents') {
	        this.panelVisibility = 'hidden';
	      } else {
	        this.panelVisibility = 'documents';
	      }
	    }
	  },
	  computed: {
	    userFieldWidgetOptions() {
	      const options = {
	        insertIntoText: true,
	        ...this.widgetOptions
	      };
	      options.tileWidgetOptions = options.tileWidgetOptions || {};
	      options.tileWidgetOptions.enableDropzone = false;
	      if (options.insertIntoText) {
	        options.tileWidgetOptions.events = options.tileWidgetOptions.events || {};
	        options.tileWidgetOptions.events.onInsertIntoText = event => {
	          this.getRichTextArea().insertFile(event.getData().item);
	        };
	      }

	      // Just in case
	      delete options.files; // use 'files' prop
	      delete options.eventObject;
	      delete options.mainPostFormId;
	      return options;
	    },
	    canCreateDocuments() {
	      const settings = main_core.Extension.getSettings('disk.uploader.user-field-widget');
	      const canCreateDocuments = settings.get('canCreateDocuments', false);
	      return canCreateDocuments && this.widgetOptions.canCreateDocuments !== false;
	    }
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
	`
	};

	exports.DiskRichTextAreaComponent = DiskRichTextAreaComponent;

}((this.BX.Disk.RichTextArea = this.BX.Disk.RichTextArea || {}),BX,BX.UI.RichTextArea,BX.Disk.Uploader,BX.UI.TextEditor,BX.UI.Uploader));
//# sourceMappingURL=disk-rich-text-area.bundle.js.map
