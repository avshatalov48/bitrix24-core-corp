export const ResourceNotificationCheckBoxRow = {
	name: 'ResourceNotificationCheckBoxRow',
	emits: ['update:checked'],
	props: {
		checked: {
			type: Boolean,
			required: true,
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	template: `
		<label class="resource-creation-wizard__form-notification-text-row">
			<span class="resource-creation-wizard__form-notification-text-row-checkbox">
				<input
					type="checkbox"
					:disabled="disabled"
					:checked="checked"
					@input="$emit('update:checked', $event.target.checked)"
				/>
			</span>
			<slot/>
		</label>
	`,
};
