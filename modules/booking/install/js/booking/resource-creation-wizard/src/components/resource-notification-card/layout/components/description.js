import { HelpDeskLoc } from 'booking.component.help-desk-loc';
import { ResourceNotificationTextRow } from '../resource-notification-text-row';

export const Description = {
	props: {
		description: {
			type: String,
			required: true,
		},
		helpDesk: {
			type: Object,
			required: true,
		},
	},
	components: {
		HelpDeskLoc,
		ResourceNotificationTextRow,
	},
	template: `
		<ResourceNotificationTextRow icon="--info-1">
			<HelpDeskLoc 
				:message="description"
				:code="helpDesk.code"
				:anchor="helpDesk.anchorCode"
				class="resource-creation-wizard__form-notification-text"
			/>
		</ResourceNotificationTextRow>
	`,
};
