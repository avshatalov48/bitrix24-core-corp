import {InfoPopupContentBlock} from "./InfoPopupContentBlock";
import {Type} from "main.core";

export const InfoPopupContentBlockPhone = {
	extends: InfoPopupContentBlock,
	computed: {
		phoneNumber()
		{
			return this.attributes.phone || '';
		},
		canPerformCalls()
		{
			return !!this.attributes.canPerformCalls;
		},
	},
	methods: {
		makeCall()
		{
			if (
				typeof(window.top['BXIM']) !== 'undefined'
				&& this.canPerformCalls
			)
			{
				window.top['BXIM'].phoneTo(this.phoneNumber);
			}
		},
	},
	template: `
		<span
			class="crm__info-popup_content-table-field-link --internal"
			@click="makeCall"
		>
			{{ content }}
		</span>
	`
}
