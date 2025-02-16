import { HelpDesk } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';
import './text-layout.css';

export const TextLayout = {
	name: 'ResourceSettingsCardTextLayout',
	props: {
		type: {
			type: String,
			required: true,
		},
		text: {
			type: String,
			required: true,
		},
	},
	methods: {
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk[`Resource${this.type}`].code,
				HelpDesk[`Resource${this.type}`].anchorCode,
			);
		},
	},
	template: `
		<div class="resource-creation-wizard__form-settings-text-row">
			<div class="resource-creation-wizard__form-settings-text">
				{{ text }}
				<span @click="showHelpDesk">{{ loc('BRCW_SETTINGS_CARD_MORE') }}</span>
			</div>
		</div>
	`,
};
