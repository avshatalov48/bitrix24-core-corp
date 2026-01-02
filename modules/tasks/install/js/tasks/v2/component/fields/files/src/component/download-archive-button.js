import { type UploaderFileInfo, formatFileSize } from 'ui.uploader.core';
import { Button as UiButton, ButtonSize, AirButtonStyle, ButtonCounterColor } from 'ui.vue3.components.button';
import { Outline } from 'ui.icon-set.api.vue';

import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';

// @vue/component
export const DownloadArchiveButton = {
	components: {
		UiButton,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		files: {
			type: Array,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			ButtonSize,
			AirButtonStyle,
			ButtonCounterColor,
			Outline,
		};
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		archiveLink(): ?string
		{
			return this.task.archiveLink;
		},
		filesSize(): number
		{
			return this.files.reduce((total, file: UploaderFileInfo) => total + file.size, 0);
		},
		formattedFilesSize(): string
		{
			return formatFileSize(this.filesSize);
		},
	},
	template: `
		<a :href="archiveLink" data-task-files-download-archive>
			<UiButton
				:text="loc('TASKS_V2_FILES_DOWNLOAD_ARCHIVE', { '#FILES_SIZE#': formattedFilesSize })"
				:size="ButtonSize.MEDIUM"
				:style="AirButtonStyle.PLAIN_NO_ACCENT"
			/>
		</a>
	`,
};
