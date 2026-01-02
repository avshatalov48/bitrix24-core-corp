import './helpdesk-rule.css';

// @vue/component
export const HelpDeskRule = {
	name: 'HelpDeskRule',
	props: {
		// default property for every rule
		text: String,
		// current rule properties
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
					redirect: this.redirect,
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
	template: `
		<span
			:class="linkClass"
			role="button"
			tabindex="0"
			@click="showHelpDesk"
		>
		  {{ text }}
		</span>
	`,
};
