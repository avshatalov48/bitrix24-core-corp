export const ResourceNotificationTextRow = {
	name: 'ResourceNotificationTextRow',
	props: {
		icon: {
			type: String,
			required: true,
		},
	},
	template: `
		<div class="resource-creation-wizard__form-notification-text-row">
			<div :class="[icon, 'ui-icon-set', 'resource-creation-wizard__form-notification-text-row-icon']"></div>
			<slot/>
		</div>
	`,
};
