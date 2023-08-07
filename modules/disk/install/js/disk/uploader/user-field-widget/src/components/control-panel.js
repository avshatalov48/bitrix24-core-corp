import { Text } from 'main.core';

import { openDiskFileDialog } from '../helpers/open-disk-file-dialog';
import { openCloudFileDialog } from '../helpers/open-cloud-file-dialog';

import { Loader } from './loader';

import './css/control-panel.css';

export const ControlPanel = {
	name: 'ControlPanel',
	inject: ['userFieldControl', 'uploader', 'getMessage'],
	components: {
		Loader,
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
		this.importServices = this.userFieldControl.getImportServices();
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
				onLoad: (): void => { this.showDialogLoader = false; },
				onClose: (): void => { this.showDialogLoader = false; },
			});
		},

		openCloudFileDialog(serviceId)
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
	// language=Vue
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
				v-if="importServices['gdrive']"
				@click="openCloudFileDialog('gdrive')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--google-docs">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'gdrive'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['gdrive']['name'] }}</div>
					</div>
				</div>
			</div>
			<div 
				class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
				v-if="importServices['office365']"
				@click="openCloudFileDialog('office365')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--office365">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'office365'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['office365']['name'] }}</div>
					</div>
				</div>
			</div>
			<div 
				class="disk-user-field-panel-card-box disk-user-field-panel-card-file"
				v-if="importServices['dropbox']"
				@click="openCloudFileDialog('dropbox')"
			>
				<div class="disk-user-field-panel-card disk-user-field-panel-card-icon--dropbox">
					<div class="disk-user-field-panel-card-content">
						<Loader v-if="showCloudDialogLoader && currentServiceId === 'dropbox'" :offset="{ top: '-7px' }" />
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ importServices['dropbox']['name'] }}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	`
};
