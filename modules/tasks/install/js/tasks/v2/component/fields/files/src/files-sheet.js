import { Type } from 'main.core';

import { mapGetters } from 'ui.vue3.vuex';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';
import type { VueUploaderAdapter } from 'ui.uploader.vue';

import { UserFieldWidgetComponent, type UserFieldWidgetOptions } from 'disk.uploader.user-field-widget';
import { Model } from 'tasks.v2.const';
import { BottomSheet } from 'tasks.v2.component.elements.bottom-sheet';
import { DropZone } from 'tasks.v2.component.drop-zone';
import { type FileService, fileService } from 'tasks.v2.provider.service.file-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { UploadButton } from './component/upload-button';
import { DownloadArchiveButton } from './component/download-archive-button';

// @vue/component
export const FilesSheet = {
	name: 'TaskFilesSheet',
	components: {
		UserFieldWidgetComponent,
		UploadButton,
		DownloadArchiveButton,
		BottomSheet,
		BIcon,
		DropZone,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isShown: {
			type: Boolean,
			required: true,
		},
		getBindElement: {
			type: Function,
			default: null,
		},
		getTargetContainer: {
			type: Function,
			default: null,
		},
	},
	emits: ['close'],
	setup(props): { fileService: FileService, uploaderAdapter: VueUploaderAdapter }
	{
		return {
			Outline,
			fileService: fileService.get(props.taskId),
			uploaderAdapter: fileService.get(props.taskId).getAdapter(),
		};
	},
	data(): Object
	{
		return {
			files: this.fileService.getFiles(),
		};
	},
	computed: {
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
		}),
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		canAttachFiles(): boolean
		{
			return this.task.rights.attachFile || this.task.rights.edit;
		},
		filesCount(): number
		{
			return this.files.length;
		},
		archiveLink(): ?string
		{
			return this.task.archiveLink;
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
				tileWidgetOptions: {
					compact: true,
					autoCollapse: false,
					readonly: !this.canAttachFiles,
					enableDropzone: false,
					hideDropArea: true,
					removeFromServer: !this.isEdit,
					forceDisableSelection: true,
				},
			};
		},
		showFooter(): boolean
		{
			return this.filesCount > 1 || this.canAttachFiles;
		},
		canDownloadArchive(): boolean
		{
			return this.filesCount > 1 && this.archiveLink;
		},
	},
	watch: {
		titleFieldOffsetHeight(): void
		{
			this.$refs.bottomSheet?.adjustPosition();
		},
	},
	template: `
		<BottomSheet
			v-if="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="bottomSheet"
		>
			<div class="tasks-field-files-sheet" :data-task-id="taskId">
				<div class="tasks-field-files-header">
					<div class="tasks-field-files-title">
						{{ loc('TASKS_V2_FILES_TITLE') }}
					</div>
					<BIcon
						data-task-files-close
						class="tasks-field-files-close"
						:hoverable="true"
						:name="Outline.CROSS_L"
						@click="$emit('close')"
					/>
				</div>
				<div class="tasks-field-files-list">
					<UserFieldWidgetComponent
						:uploaderAdapter="uploaderAdapter"
						:widgetOptions="widgetOptions"
					/>
				</div>
				<div
					v-if="showFooter"
					class="tasks-field-files-footer"
					:class="{ '--with-archive': canDownloadArchive }"
				>
					<DownloadArchiveButton
						v-if="canDownloadArchive"
						:taskId="taskId"
						:files="files"
					/>
					<UploadButton v-if="canAttachFiles" :taskId="taskId"/>
				</div>
			</div>
			<DropZone :taskId="taskId"/>
		</BottomSheet>
	`,
};
