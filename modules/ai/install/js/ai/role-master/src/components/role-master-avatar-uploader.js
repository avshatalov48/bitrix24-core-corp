import { bind, unbind } from 'main.core';
import { Uploader, UploaderError, UploaderEvent, UploaderFile } from 'ui.uploader.core';
import { BIcon, Set as IconSet } from 'ui.icon-set.api.vue';
import { UI } from 'ui.notification';
import { toRaw } from 'ui.vue3';

import 'ui.icon-set.main';
import 'ui.icon-set.actions';

import '../css/role-master-avatar-uploader.css';

export const RoleMasterAvatarUploader = {
	name: 'RoleMasterAvatarUploader',
	components: {
		BIcon,
	},
	props: {
		avatarUrl: '',
	},
	emits: ['uploadAvatarFile', 'removeAvatarFile', 'selectAvatar', 'generateAvatar'],
	data(): { isDraggingFile: boolean, uploader: Uploader } {
		return {
			uploader: null,
			isDraggingFile: false,
		};
	},
	computed: {
		avatarUploaderStyle(): Object {
			return {
				backgroundImage: this.avatarUrl ? `url(${this.avatarUrl})` : null,
			};
		},
		IconSet(): IconSet {
			return IconSet;
		},

	},
	methods: {
		handleDragElementEnterDocument() {
			this.isDraggingFile = true;
		},
		handleDragElementLeaveDocument(event: DragEvent) {
			if (event.relatedTarget === null)
			{
				this.isDraggingFile = false;
			}
		},
		handleDropFile(): void {
			this.isDraggingFile = false;
		},
		handleDragOverFile(event: DragEvent): void {
			event.preventDefault();
		},
		showNotification(message: string): void {
			const id = String(Math.random() * 1000);

			UI.Notification.Center.notify({
				id,
				content: message,
			});
		},
		removeUploadedFile(): void {
			const files: UploaderFile[] = this.uploader.getFiles();

			if (files.length > 0)
			{
				toRaw(this.uploader).removeFiles();
				this.$emit('removeAvatarFile');
			}
		},
	},
	mounted() {
		this.uploader = new Uploader({
			dropElement: this.$refs.dropArea,
			browseElement: this.$refs.uploaderContainer,
			assignServerFile: false,
			allowReplaceSingle: true,
			assignAsFile: true,
			acceptOnlyImages: true,
			acceptedFileTypes: ['image/jpeg', 'image/png'],
			maxFileSize: 1_048_076,
			multiple: false,
			events: {
				[UploaderEvent.FILE_LOAD_COMPLETE]: (event) => {
					this.uploadedFile = event.getData().file;
					this.$emit('uploadAvatarFile', event.getData().file);
					this.$refs.uploaderContainer.blur();
				},
				[UploaderEvent.FILE_ERROR]: (event) => {
					const error: UploaderError = event.getData().error;
					this.showNotification(error.getMessage());
					this.$refs.uploaderContainer.blur();
				},
				[UploaderEvent.FILE_REMOVE]: () => {
					// TODO don't work this event
					this.$emit('removeAvatarFile');
					this.$refs.uploaderContainer.blur();
				},
			},
		});

		if (this.avatarUrl)
		{
			this.uploader.addFile(0);
			this.$emit('loadAvatarFile', toRaw(this.uploader.getFiles()[0]));
		}
		bind(document, 'dragenter', this.handleDragElementEnterDocument);
		bind(document, 'dragleave', this.handleDragElementLeaveDocument);
		bind(document, 'drop', this.handleDropFile);
		bind(document, 'dragover', this.handleDragOverFile);
	},
	unmounted() {
		unbind(document, 'dragenter', this.handleDragElementEnterDocument);
		unbind(document, 'dragleave', this.handleDragElementLeaveDocument);
		unbind(document, 'drop', this.handleDropFile);
		unbind(document, 'dragover', this.handleDragOverFile);
	},
	template: `
		<div
			class="ai__role-master_avatar-uploader-wrapper"
			:style="avatarUploaderStyle"
		>
			<div
				v-show="!avatarUrl"
				ref="uploaderContainer"
				class="ai__role-master_avatar-uploader"
				:tabindex="0"
				@keyup.enter="$event.target.click()"
			>
				<div class="ai__role-master_avatar-uploader-icon">
					<BIcon
						v-if="!avatarUrl"
						:size="28"
						:name="IconSet.CAMERA"
					></BIcon>
				</div>
				<div class="ai__role-master_avatar-uploader-hover">
					<BIcon
						v-if="isDraggingFile === false && !avatarUrl"
						:size="24"
						:name="IconSet.EDIT_PENCIL"
					></BIcon>
					<BIcon
						v-else-if="isDraggingFile === false && avatarUrl"
						:size="24"
						:name="IconSet.TRASH_BIN"
					></BIcon>
				</div>
				<teleport to=".ai__role-master-app-container main">
					<transition name="ai-role-master-drop-area-fade">
						<div
							ref="dropArea"
							v-show="isDraggingFile"
							class="ai__role-master_avatar-uploader-drop-area"
						>
							<div class="ai__role-master_avatar-uploader-drop-area-icon">
								<BIcon
									:size="64"
									:name="IconSet.FILE_UPLOAD"
								></BIcon>

							</div>
							<span class="ai__role-master_avatar-uploader-drop-area-text">
						{{ $Bitrix.Loc.getMessage('ROLE_MASTER_FILE_DROPAREA_TEXT') }}
					</span>
						</div>
					</transition>
				</teleport>
			</div>
			<div
				class="ai__role-master_avatar-uploader"
				v-if="isDraggingFile === false && avatarUrl"
				@click="removeUploadedFile"
			>
				<div class="ai__role-master_avatar-uploader-hover">
					<BIcon
						:size="24"
						:name="IconSet.TRASH_BIN"
					></BIcon>
				</div>
			</div>
		</div>
	`,
};
