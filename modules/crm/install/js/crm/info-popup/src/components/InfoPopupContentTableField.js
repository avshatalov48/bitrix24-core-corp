import {InfoPopupContentBlockText} from "./content-blocks/InfoPopupContentBlockText";
import {InfoPopupContentBlockMoney} from "./content-blocks/InfoPopupContentBlockMoney";
import {InfoPopupContentBlockLink} from "./content-blocks/InfoPopupContentBlockLink";
import {InfoPopupContentBlockType} from '../enums/info-popup-content-block-type';
import {InfoPopupContentBlockPhone} from "./content-blocks/InfoPopupContentBlockPhone";

export const InfoPopupContentTableField = {
	components: {
		InfoPopupContentBlockLink,
		InfoPopupContentBlockText,
		InfoPopupContentBlockMoney,
	},
	props: {
		title: {
			type: String,
			required: true,
			default: '',
		},
		contentBlock: {
			type: Object,
			required: true,
			default: () => ({}),
		},
	},
	computed: {
		type() {
			return this.contentBlock?.type;
		},

		contentBlockComponent() {
			switch (this.type) {
				case InfoPopupContentBlockType.LINK: return InfoPopupContentBlockLink;
				case InfoPopupContentBlockType.TEXT: return InfoPopupContentBlockText;
				case InfoPopupContentBlockType.MONEY: return InfoPopupContentBlockMoney;
				case InfoPopupContentBlockType.PHONE: return InfoPopupContentBlockPhone;
				default: return InfoPopupContentBlockText;
			}
		},
	},
	template: `
		<li class="crm__info-popup_content-table-field">
			<div class="crm__info-popup_content-table-field-title">
				{{ title }}
			</div>
			<div class="crm__info-popup_content-table-field-value">
				<component
					:is="contentBlockComponent"
					:content="contentBlock.content"
					:attributes="contentBlock.attributes"
				/>
			</div>
		</li>
	`
}
