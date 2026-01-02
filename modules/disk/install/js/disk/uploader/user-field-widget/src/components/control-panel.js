import { Text } from 'main.core';

import { DocumentService } from '../const';
import { openDiskFileDialog, openCloudFileDialog } from '../helpers';
import { userFieldSettings } from '../user-field-settings';
import { Loader } from './loader';

import './css/control-panel.css';

// @vue/component
export const ControlPanel = {
	name: 'ControlPanel',
	components: {
		Loader,
	},
	inject: ['userFieldControl', 'uploader', 'getMessage'],
	setup(): { importServices: Object }
	{
		return {
			DocumentService,
			importServices: userFieldSettings.getImportServices(),
		};
	},
	data: () => ({
		showDialogLoader: false,
		showCloudDialogLoader: false,
		currentServiceId: null,
	}),
	created(): void
	{
		this.fileDialogId = `file-dialog-${Text.getRandom(5)}`;
		this.cloudDialogId = `cloud-dialog-${Text.getRandom(5)}`;
	},
	mounted(): void
	{
		this.uploader.assignBrowse(this.$refs.upload);
	},
	methods: {
		openDiskFileDialog(): void
		{
			if (this.showDialogLoader)
			{
				return;
			}

			this.showDialogLoader = true;

			openDiskFileDialog({
				dialogId: this.fileDialogId,
				uploader: this.uploader,
				onLoad: (): void => {
					this.showDialogLoader = false;
				},
				onClose: (): void => {
					this.showDialogLoader = false;
				},
			});
		},
		openCloudFileDialog(serviceId): void
		{
			if (this.showCloudDialogLoader)
			{
				return;
			}

			this.currentServiceId = serviceId;
			this.showCloudDialogLoader = true;

			const finalize = (): void => {
				this.showCloudDialogLoader = false;
				this.currentServiceId = null;
			};

			openCloudFileDialog({
				dialogId: this.cloudDialogId,
				uploader: this.uploader,
				serviceId,
				onLoad: finalize,
				onClose: finalize,
			});
		},
	},
	template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-file-wrap">
				<div class="disk-user-field-panel-card-box disk-user-field-panel-card-file" ref="upload">
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--upload">
						<div class="disk-user-field-panel-card-content">
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_UPLOAD_FILES') }}</div>
						</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box disk-user-field-panel-card-file" @click="openDiskFileDialog">
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--b24">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showDialogLoader" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_MY_DRIVE') }}</div>
						</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-divider"></div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Google]"
					@click="openCloudFileDialog(DocumentService.Google)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--google-docs">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Google" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Google]['name'] }}</div>
						</div>
					</div>
				</div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Office]"
					@click="openCloudFileDialog(DocumentService.Office)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--office365">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Office" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Office].name }}</div>
						</div>
					</div>
				</div>
				<div 
					class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
					v-if="importServices[DocumentService.Dropbox]"
					@click="openCloudFileDialog(DocumentService.Dropbox)"
				>
					<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--dropbox">
						<div class="disk-user-field-panel-card-content">
							<Loader v-if="showCloudDialogLoader && currentServiceId === DocumentService.Dropbox" :offset="{ top: '-7px' }" />
							<div class="disk-user-field-panel-card-icon"></div>
							<div class="disk-user-field-panel-card-btn"></div>
							<div class="disk-user-field-panel-card-name">{{ importServices[DocumentService.Dropbox].name }}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
