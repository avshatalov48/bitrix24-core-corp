import type { PopupOptions } from 'main.popup';

import { Popup } from 'ui.vue3.components.popup';
import { Button as UiButton, AirButtonStyle, ButtonSize, ButtonIcon } from 'ui.vue3.components.button';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';
import type { VueUploaderAdapter } from 'ui.uploader.vue';

import { UserFieldWidgetComponent, type UserFieldWidgetOptions } from 'disk.uploader.user-field-widget';
import { fileService } from 'tasks.v2.provider.service.file-service';
import './files-popup.css';

// @vue/component
export const FilesPopup = {
	components: {
		Popup,
		UiButton,
		UserFieldWidgetComponent,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		getBindElement: {
			type: Function,
			required: true,
		},
	},
	emits: ['upload', 'close'],
	setup(props): { uploaderAdapter: VueUploaderAdapter }
	{
		return {
			ButtonSize,
			AirButtonStyle,
			ButtonIcon,
			Outline,
			uploaderAdapter: fileService.get(props.taskId).getAdapter(),
		};
	},
	data(): Object
	{
		return {
			files: fileService.get(this.taskId).getFiles(),
		};
	},
	computed: {
		bindElement(): HTMLElement
		{
			return this.getBindElement();
		},
		popupOptions(): PopupOptions
		{
			return {
				id: 'tasks-field-files-popup',
				bindElement: this.bindElement,
				minWidth: 380,
				maxHeight: 320,
				padding: 18,
				bindOptions: {
					forceBindPosition: true,
					forceTop: true,
					position: 'top',
				},
				closeByEsc: true,
				autoHideHandler: (event: MouseEvent): boolean => {
					const anyTileMenuShown = this.files.some(({ isMenuShown }) => isMenuShown);

					return this.$refs.popup?.autoHideHandler(event) && !anyTileMenuShown;
				},
			};
		},
		widgetOptions(): UserFieldWidgetOptions
		{
			return {
				isEmbedded: true,
				withControlPanel: false,
				canCreateDocuments: false,
				tileWidgetOptions: {
					hideDropArea: true,
					enableDropzone: false,
					compact: true,
					autoCollapse: false,
				},
			};
		},
		filesCount(): number
		{
			return this.files.length;
		},
	},
	watch: {
		filesCount(): void
		{
			if (this.filesCount === 0)
			{
				this.closePopup();
			}
		},
	},
	methods: {
		handleClearClick(): void
		{
			fileService.get(this.taskId).getAdapter().getUploader().removeFiles();

			this.closePopup();
		},
		handleUploadClick(): void
		{
			fileService.get(this.taskId).browseFiles();
		},
		handleMyDriveClick(): void
		{
			fileService.get(this.taskId).browseMyDrive();
		},
		closePopup(): void
		{
			this.$emit('close');
		},
	},
	template: `
		<Popup
			:options="popupOptions"
			ref="popup"
			@close="closePopup"
		>
			<div class="tasks-field-files-popup">
				<div class="tasks-field-files-popup-files">
					<UserFieldWidgetComponent :uploaderAdapter="uploaderAdapter" :widgetOptions="widgetOptions"/>
				</div>
				<div class="tasks-field-files-popup-footer">
					<div class="tasks-field-files-popup-add-buttons">
						<UiButton
							:text="loc('TASKS_V2_FILES_UPLOAD_BUTTON')"
							:size="ButtonSize.SMALL"
							:style="AirButtonStyle.TINTED"
							:leftIcon="Outline.DOWNLOAD"
							@click="handleUploadClick"
						/>
						<UiButton
							:text="loc('TASKS_V2_FILES_MY_DRIVE')"
							:size="ButtonSize.SMALL"
							:style="AirButtonStyle.TINTED"
							:leftIcon="Outline.UPLOAD"
							@click="handleMyDriveClick"
						/>
					</div>
					<UiButton
						:text="loc('TASKS_V2_FILES_CLEAR')"
						:size="ButtonSize.SMALL"
						:style="AirButtonStyle.PLAIN_NO_ACCENT"
						:leftIcon="Outline.TRASHCAN"
						@click="handleClearClick"
					/>
				</div>
			</div>
		</Popup>
	`,
};
