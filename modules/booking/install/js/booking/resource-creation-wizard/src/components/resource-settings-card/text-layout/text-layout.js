import { HelpDesk } from 'booking.const';
import { HelpDeskLoc } from 'booking.component.help-desk-loc';
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
	setup(props): { code: string, anchorCode: string }
	{
		return {
			code: HelpDesk[`Resource${props.type}`].code,
			anchorCode: HelpDesk[`Resource${props.type}`].anchorCode,
		};
	},
	components: {
		HelpDeskLoc,
	},
	template: `
		<div class="resource-creation-wizard__form-settings-text-row">
			<HelpDeskLoc
				:message="text"
				:code="code"
				:anchor="anchorCode"
				class="resource-creation-wizard__form-settings-text"
			/>
		</div>
	`,
};
