import { FileStatus, UploaderFileInfo } from 'ui.uploader.core';
import { Animated, Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.animated';
import 'ui.icon-set.outline';

import { Chip, ChipDesign } from 'tasks.v2.component.elements.chip';
import { GroupType, Model, CardType } from 'tasks.v2.const';
import { fieldHighlighter } from 'tasks.v2.lib.field-highlighter';
import { analytics } from 'tasks.v2.lib.analytics';
import { fileService, type FileService } from 'tasks.v2.provider.service.file-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { GroupModel } from 'tasks.v2.model.groups';

import { filesMeta } from './files-meta';
import { FilesPopup } from './files-popup/files-popup';

// @vue/component
export const FilesChip = {
	components: {
		Chip,
		FilesPopup,
	},
	inject: ['analytics', 'cardType'],
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isAutonomous: {
			type: Boolean,
			default: false,
		},
	},
	setup(props): { fileService: FileService }
	{
		return {
			filesMeta,
			fileService: fileService.get(props.taskId),
		};
	},
	data(): Object
	{
		return {
			files: this.fileService.getFiles(),
			isPopupShown: false,
			isPopupShownWithFiles: false,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		group(): ?GroupModel
		{
			return this.$store.getters[`${Model.Groups}/getById`](this.task.groupId);
		},
		filesCount(): number
		{
			return this.files.length;
		},
		design(): string
		{
			return {
				[!this.isAutonomous && !this.isSelected]: ChipDesign.ShadowNoAccent,
				[!this.isAutonomous && this.isSelected]: ChipDesign.ShadowAccent,
				[this.isAutonomous && !this.isSelected]: ChipDesign.OutlineNoAccent,
				[this.isAutonomous && this.isSelected]: ChipDesign.OutlineAccent,
				[this.hasError]: ChipDesign.OutlineAlert,
			}.true;
		},
		isSelected(): boolean
		{
			if (this.isAutonomous)
			{
				return this.files.length > 0;
			}

			const wasFilesFilled = this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, filesMeta.id);

			return wasFilesFilled || this.files.length > 0;
		},
		icon(): string
		{
			if (this.isAutonomous && this.isUploading)
			{
				return Animated.LOADER_WAIT;
			}

			return Outline.ATTACH;
		},
		text(): string
		{
			if (this.isAutonomous && this.filesCount > 0)
			{
				return this.loc('TASKS_V2_FILES_COUNT', {
					'#COUNT#': this.filesCount,
				});
			}

			return filesMeta.title;
		},
		isUploading(): boolean
		{
			return this.fileService.isUploading();
		},
		hasError(): boolean
		{
			return this.files.some(({ status }) => [FileStatus.UPLOAD_FAILED, FileStatus.LOAD_FAILED].includes(status));
		},
		canAttachFiles(): boolean
		{
			return this.task.rights.attachFile || this.task.rights.edit;
		},
		popupHasAlreadyBeenShown(): boolean
		{
			return this.filesCount > 0 && this.isPopupShownWithFiles;
		},
	},
	mounted(): void
	{
		this.fileService.subscribe('onFileAdd', this.handleFileAdd);
		this.fileService.subscribe('onFileComplete', this.handleFileComplete);
	},
	beforeUnmount(): void
	{
		this.fileService.unsubscribe('onFileAdd', this.handleFileAdd);
		this.fileService.unsubscribe('onFileComplete', this.handleFileComplete);
	},
	methods: {
		handleFileAdd(): void
		{
			if (this.isAutonomous && this.popupHasAlreadyBeenShown)
			{
				this.showPopup();
			}
		},
		handleFileComplete(event): void
		{
			this.sendAnalytics(event.getData());
		},
		sendAnalytics(file: UploaderFileInfo): void
		{
			analytics.sendAttachFile(this.analytics, {
				cardType: this.cardType,
				collabId: this.group?.type === GroupType.Collab ? this.group.id : null,
				fileOrigin: file.origin,
				fileSize: file.size,
				fileExtension: file.extension,
				filesCount: this.filesCount,
			});
		},
		handleClick(): void
		{
			if (!this.isAutonomous && this.isSelected)
			{
				this.highlightField();

				return;
			}

			if (this.files.length > 0)
			{
				this.isPopupShownWithFiles = true;

				this.showPopup();
			}
			else
			{
				this.browseFiles();
			}
		},
		showPopup(): void
		{
			this.isPopupShown = true;
		},
		closePopup(): void
		{
			this.isPopupShown = false;

			if (!this.filesCount)
			{
				this.isPopupShownWithFiles = false;
			}
		},
		browseFiles(): void
		{
			this.fileService.browse({
				bindElement: this.$refs.chip.$el,
				onHideCallback: this.onFileBrowserClose,
				compact: this.cardType === CardType.Compact,
			});
		},
		highlightField(): void
		{
			void fieldHighlighter.setContainer(this.$root.$el).highlight(filesMeta.id);
		},
		onFileBrowserClose(): void
		{
			this.$refs.chip?.$el.focus();
			this.fileService.setFileBrowserClosed(true);
		},
	},
	template: `
		<Chip
			v-if="isSelected || canAttachFiles"
			:design="design"
			:icon="icon"
			:text="text"
			:data-task-id="taskId"
			:data-task-chip-id="filesMeta.id"
			ref="chip"
			@click="handleClick"
		/>
		<FilesPopup
			v-if="isPopupShown"
			:taskId="taskId"
			:getBindElement="() => $refs.chip.$el"
			@upload="browseFiles"
			@close="closePopup"
		/>
	`,
};
