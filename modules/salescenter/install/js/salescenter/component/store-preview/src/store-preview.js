import {Vue} from 'ui.vue';
import './store-preview.css';

const PreviewBlock = {
	props: ['options'],
	computed:
		{
			loc() {
				return Vue.getFilteredPhrases('SC_STORE_PREVIEW_')
			},
			getClassPreviewImage()
			{
				return {
					'salescenter-company-contacts-prev': this.options.lang === 'ru',
					'salescenter-company-contacts-prev-en': this.options.lang === 'en',
					'salescenter-company-contacts-prev-ua': this.options.lang === 'ua'
				}
			}
		},
	template: `
			<div class="salescenter-company-contacts-item salescenter-company-contacts-item--gray">
				<div class="salescenter-company-contacts-item-preview">
					<div class="salescenter-company-contacts-item-preview-image">
						<div :class="getClassPreviewImage"></div>
					</div>
				</div>
			</div>`
};
export {
	PreviewBlock
}