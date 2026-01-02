import { DocumentPreview } from '../document-preview/document-preview';
import { LocMixin } from 'sign.v2.b2e.vue-util';
import './style.css';

// @vue/component
export const DocumentPreviewList = {
	name: 'DocumentPreviewList',
	components: {
		DocumentPreview,
	},
	mixins: [LocMixin],
	props: {
		documentList: {
			type: Array,
			required: true,
		},
		maxPreviewCount: {
			type: Number,
			default: 7,
		},
	},
	data(): Object
	{
		return {
			isShowMoreButtonVisible: true,
			previewCount: 0,
		};
	},
	computed: {
		showMoreCount(): number
		{
			return this.documentCount - this.maxPreviewCount;
		},
		isShowMoreVisible(): boolean
		{
			return this.showMoreCount > 0;
		},
		documentCount(): number
		{
			return this.documentList.length;
		},
		documentPreviewList(): Array
		{
			const result = [];
			for (const document of this.documentList)
			{
				result.push(document);

				if (result.length >= this.previewCount)
				{
					break;
				}
			}

			return result;
		},
	},
	created(): void {
		this.previewCount = this.maxPreviewCount;
	},
	methods: {
		onShowMoreClick(): void
		{
			this.previewCount = this.documentCount;
			this.isShowMoreButtonVisible = false;
		},
		onShowLessClick(): void
		{
			this.previewCount = this.maxPreviewCount;
			this.isShowMoreButtonVisible = true;
		},
	},
	template: `
		<div class="sign-b2e-settings__item sign-b2e-regional-settings_apply_for_all_list">
			<template v-for="document in documentPreviewList">
				<div class="sign-b2e-regional-settings_apply_for_all_settings_preview_block">
					<DocumentPreview
						:document-id="document?.documentId"
						:preview-src="document?.previewUrl"
						:is-icon-visible="true"
						preview-image-class="sign-b2e-regional-settings_apply_for_all_settings_preview">
					</DocumentPreview>
					<div class="sign-b2e-regional-settings_apply_for_all_settings_preview_title">
						{{ document.title }}
					</div>
				</div>
			</template>
			<div v-if="isShowMoreVisible" class="sign-b2e-regional-settings_apply_for_all_settings_preview_show_more_less">
				<span v-if="isShowMoreButtonVisible" @click="onShowMoreClick" class="sign-b2e-regional-settings_apply_for_all_settings_more_less_button">
					{{ loc('SIGN_V2_B2E_DOCUMENT_PREVIEW_LIST_MORE_TITLE', { '#COUNT#': String(showMoreCount) }) }}
				</span>
				<span v-else @click="onShowLessClick" class="sign-b2e-regional-settings_apply_for_all_settings_more_less_button">
					{{ loc('SIGN_V2_B2E_DOCUMENT_PREVIEW_LIST_LESS_TITLE') }}
				</span>
			</div>
		</div>
	`,
};
