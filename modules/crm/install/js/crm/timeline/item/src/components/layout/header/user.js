import {Text} from "main.core"

export const User = {
	props: {
		title: String,
		detailUrl: String,
		imageUrl: String,
	},
	inject: ['isLogMessage'],
	computed: {
		styles()
		{
			if (!this.imageUrl)
			{
				return {};
			}

			return {
				backgroundImage: "url('" + encodeURI(Text.encode(this.imageUrl)) + "')",
				backgroundSize: '21px'
			};
		},

		className() {
			return [
				'ui-icon',
				'ui-icon-common-user',
				'crm-timeline__user-icon', {
				'--muted': this.isLogMessage,
				}
			]
		},
	},
	// language=Vue
	template: `<a :class="className" :href="detailUrl"
				  target="_blank" :title="title"><i :style="styles"></i></a>`
};
