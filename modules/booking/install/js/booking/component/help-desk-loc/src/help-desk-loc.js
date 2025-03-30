import { RichLoc } from 'ui.vue3.components.rich-loc';
import './help-desk-loc.css';

export const HelpDeskLoc = {
	name: 'HelpDeskLoc',
	props: {
		message: {
			type: String,
			required: true,
		},
		code: {
			type: String,
			required: true,
		},
		anchor: {
			type: String,
			default: null,
		},
		redirect: {
			type: String,
			default: 'detail',
		},
		linkClass: {
			type: [String, Object, Array],
			default: 'booking--help-desk-link',
		},
	},
	methods: {
		showHelpDesk(): void
		{
			if (top.BX.Helper)
			{
				const anchor = this.anchor;
				const params = {
					redirect: 'detail',
					code: this.code,
					...(anchor !== null && { anchor }),
				};

				const queryString = Object.entries(params)
					.map(([key, value]) => `${key}=${value}`)
					.join('&');

				top.BX.Helper.show(queryString);
			}
		},
	},
	components: {
		RichLoc,
	},
	template: `
		<RichLoc :text="message" placeholder="[helpdesk]">
			<template #helpdesk="{ text }">
				<slot name="helpdesk">
					<span
						:class="linkClass"
						role="button"
						tabindex="0"
						@click="showHelpDesk"
					>
						{{ text }}
					</span>
				</slot>
			</template>
		</RichLoc>
	`,
};
