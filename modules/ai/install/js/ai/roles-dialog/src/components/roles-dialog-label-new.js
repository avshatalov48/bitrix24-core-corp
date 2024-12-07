import { Label, LabelColor, LabelSize } from 'ui.label';

export const RolesDialogLabelNew = {
	props: {
		inverted: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		labelHTML(): string {
			const labelColor = this.inverted ? LabelColor.COPILOT_LIGHT_REVERSE : LabelColor.COPILOT_LIGHT;

			const label = new Label({
				color: labelColor,
				size: LabelSize.SM,
				text: 'NEW',
				fill: true,
			});

			return label.render().outerHTML;
		},
	},
	template: `
		<div ref="label" class="ai__roles-dialog_label" v-html="labelHTML"></div>
	`,
};
