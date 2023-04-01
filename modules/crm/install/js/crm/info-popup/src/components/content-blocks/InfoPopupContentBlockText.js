import {InfoPopupContentBlock} from "./InfoPopupContentBlock";

export const InfoPopupContentBlockText = {
	extends: InfoPopupContentBlock,
	template: `
		<span>{{ content }}</span>
	`
}