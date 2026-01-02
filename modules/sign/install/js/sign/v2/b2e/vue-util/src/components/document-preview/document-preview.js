import { Router } from 'crm.router';
import './style.css';

// @vue/component
export const DocumentPreview = {
	name: 'DocumentPreview',
	props: {
		previewSrc: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		documentId: {
			type: Number,
			required: true,
		},
		previewImageClass: {
			type: String,
			required: false,
			default: '',
		},
		isIconVisible: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	methods: {
		previewDocument(): Promise
		{
			if (this.documentId < 1)
			{
				return Promise.resolve();
			}

			return Router.openSlider(
				`/sign/b2e/preview/0/?documentId=${this.documentId}&noRedirect=Y`,
				{ width: 800, cacheable: false },
			);
		},
	},
	template: `
		<div class="sign-b2e_document_preview_container">
			<div v-if="isIconVisible" @click="previewDocument()" class="sign-b2e_document_preview_icon"></div>
			<img v-if="previewSrc" @click="previewDocument()" :class="previewImageClass" :src="previewSrc" :alt="title">
		</div>	
	`,
};
