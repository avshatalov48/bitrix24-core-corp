import { FileUploaderPopup } from 'crm.activity.file-uploader-popup';
import File from './file';
import {BIcon, Set} from "ui.icon-set.api.vue";
import 'ui.icon-set.main';
import 'ui.icon-set.actions';

export const FileList = {
	components: {
		File,
		BIcon,
	},
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
		numberOfFiles: {
			type: Number,
			required: false,
			default: 0,
		},
		files: {
			type: Array,
			required: true,
			default: [],
		},
		updateParams: {
			type: Object,
			required: false,
			default: {},
		},
		visibleFilesNumber: {
			type: Number,
			required: false,
			default: 5,
		},
	},

	inject: ['isReadOnly'],

	data() {
		return {
			visibleFilesAmount: this.visibleFilesNumber,
		}
	},

	computed: {
		isEditable(): boolean
		{
			return Object.keys(this.updateParams).length > 0 && !this.isReadOnly;
		},

		visibleFiles(): Array {
			return this.files.slice(0, this.visibleFilesAmount);
		},

		editFilesBtnClassname(): Array {
			return [
				'crm-timeline__file-list-btn', {
				'--disabled': !this.isEditable,
				}
			]
		},

		expandFileListBtnTitle() {
			return this.isAllFilesVisible
				?
				this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_FILE_LIST_COLLAPSE')
				:
				this.$Bitrix.Loc.getMessage('CRM_TIMELINE_ITEM_FILE_LIST_EXPAND')
			;
		},

		editFilesBtnIcon(): string {
			return Set.PENCIL_40;
		},

		addVisibleFilesBtnIcon(): string {
			return Set.CHEVRON_DOWN;
		},

		isAllFilesVisible(): boolean {
			return this.visibleFilesAmount === this.numberOfFiles
		},

		isShowExpandFileListBtn(): boolean {
			return this.numberOfFiles > this.visibleFilesNumber;
		},

		expandBtnIconClassname(): string {
			return [
				'crm-timeline__file-list-btn-icon', {
				'--upended': this.isAllFilesVisible,
				}
			]
		},
	},

	methods: {
		fileProps(file: Object): Object
		{
			return {
				id: file.id,
				text: file.name,
				href: file.viewUrl,
				size: file.size,
				attributes: file.attributes,
				hasAudioPlayer: file.hasAudioPlayer,
			};
		},

		showFileUploaderPopup(): void
		{
			if (!this.isEditable)
			{
				return;
			}

			const popup = new FileUploaderPopup(this.updateParams);
			popup.show();
		},

		handleShowFilesBtnClick() {
			if (this.isAllFilesVisible)
			{
				this.collapseFileList();
			}
			else
			{
				this.expandFileList();
			}
		},

		expandFileList(): void
		{
			this.visibleFilesAmount = this.numberOfFiles;
		},

		collapseFileList(): void {
			this.visibleFilesAmount = this.visibleFilesNumber;
		}
	},

	template:
		`
			<div class="crm-timeline__file-list-wrapper">
				<div class="crm-timeline__file-list-container">
					<div
						class="crm-timeline__file-container"
						v-for="file in visibleFiles"
					>
						<File :key="file.id" v-bind="fileProps(file)"></File>
					</div>
				</div>
				<footer class="crm-timeline__file-list-footer">
					<div
						v-if="isShowExpandFileListBtn"
						class="crm-timeline__file-list-btn-container"
					>
						<button
							class="crm-timeline__file-list-btn"
							@click="handleShowFilesBtnClick"
						>
							<span class="crm-timeline__file-list-btn-text">{{expandFileListBtnTitle}}</span>
							<i :class="expandBtnIconClassname">
								<BIcon :name="addVisibleFilesBtnIcon" :size="18"></BIcon>
							</i>
						</button>
					</div>
					<div
						v-if="isEditable"
						class="crm-timeline__file-list-btn-container"
					>
						<button
							v-if="title !== '' || numberOfFiles > 0"
							@click="showFileUploaderPopup"
							:class="editFilesBtnClassname"
						>
							<span class="crm-timeline__file-list-btn-text">{{ title }}</span>
							<i class="crm-timeline__file-list-btn-icon">
								<BIcon :name="editFilesBtnIcon" :size="18"></BIcon>
							</i>
							<i ref="edit-icon" class="crm-timeline__file-list-btn-icon"></i>
					</button>
					</div>
				</footer>
			</div>
		`
};
