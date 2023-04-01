import {InfoPopupContentBlock} from "./InfoPopupContentBlock";

export const InfoPopupContentBlockLink = {
	extends: InfoPopupContentBlock,
	computed: {
		href() {
			return this.attributes?.href;
		},
	},
	template: `
		<a :href="href">{{ content }}</a>
	`
}