import { FileUploaderPopup } from 'crm.activity.file-uploader-popup';
import File from './file';

export const FileList = {
	components: {
		File,
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
	},

	inject: ['isReadOnly'],

	computed: {
		titleClassName(): Object
		{
			return {
				'crm-timeline__file-list-title': true,
				'--editable': this.isEditable,
			}
		},

		fileListTitleText(): string
		{
			const numberOfFiles = this.numberOfFiles > 0 ? `(${this.numberOfFiles})` : '';
			const title = this.title !== '' ? this.title : '';

			return `${title} ${numberOfFiles}`;
		},

		isEditable(): boolean
		{
			return Object.keys(this.updateParams).length > 0 && !this.isReadOnly;
		}
	},

	methods: {
		fileProps(file: Object): Object
		{
			return {
				text: file.name,
				href: file.viewUrl,
				size: file.size,
				attributes: file.attributes,
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
	},

	template:
		`
			<div class="crm-timeline__file-list-wrapper">
				<div class="crm-timeline__file-list-title-container">
					<span
						v-if="title !== '' || numberOfFiles > 0"
						@click="showFileUploaderPopup"
						:class="titleClassName"
					>
						{{ fileListTitleText }}
					</span>
					<button
						v-if="isEditable"
						@click="showFileUploaderPopup"
						class="crm-timeline__file-list-edit-btn"
					>
						<i class="crm-timeline__editable-text_edit-icon"></i>
					</button>
				</div>
				<div class="crm-timeline__file-list-container">
					<div
						class="crm-timeline__file-container"
						v-for="file in files"
					>
						<File :key="file.id" v-bind="fileProps(file)"></File>
					</div>
				</div>
			</div>
		`
};
