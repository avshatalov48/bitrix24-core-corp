import { Alert } from 'ui.alerts';

export const RoleMasterWarning = {
	props: {
		text: String,
	},
	computed: {
		alertHtmlString(): string {
			const alert = new Alert({
				inline: true,
				text: this.text,
				color: Alert.Color.WARNING,
				animated: false,
				icon: Alert.Icon.INFO,
				size: Alert.Size.XS,
				closeBtn: false,
			});

			alert.show();

			return alert.render().outerHTML;
		},
	},
	template: `
		<div v-html="alertHtmlString"></div>
	`,
};
