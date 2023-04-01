import {InfoPopupIcons} from "../enums/info-popup-icons";
import {Type} from 'main.core';

export const InfoPopupHeader = {
	props: {
		icon: {
			type: String,
			required: false,
			default: '',
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		hint: {
			type: String,
			required: false,
			default: '',
		},
		subtitle: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		iconClassname() {
			return [
				'crm__info-popup_icon',
				this.getIconModifier(),
			]
		},
	},
	methods: {
		getIconModifier() {
			if (!this.isIconExist(this.icon))
			{
				return '--empty';
			}

			return `--${(InfoPopupIcons[this.icon] || InfoPopupIcons[this.icon.toUpperCase()])}`;
		},

		isIconExist() {
			if (!Type.isString(this.icon))
			{
				return false;
			}

			return !!(InfoPopupIcons[this.icon] || InfoPopupIcons[this.icon.toUpperCase()]);
		},
	},
	template: `
		<header class="crm__info-popup_header">
				<div
					v-if="icon"
					:class="iconClassname"
				/>
				<div class="crm__info-popup_header-right">
					<div class="crm__info-popup_title-container">
						<h2 class="crm__info-popup_title ui-typography-heading-h2">
							{{ title }}
						</h2>
						<div v-if="hint" class="crm__info-popup_hint"></div>
					</div>
					<div v-if="subtitle" class="crm__info-popup_subtitle">
						{{ subtitle }}
					</div>
				</div>
			</header>
	`
}