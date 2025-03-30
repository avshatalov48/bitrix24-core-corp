import { Model } from 'booking.const';
import { ResourceNotificationCheckBoxRow } from '../resource-notification-checkbox-row';

export const CheckedForAll = {
	props: {
		type: {
			type: String,
			required: true,
		},
		disabled: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		isCheckedForAll: {
			get(): boolean
			{
				return this.$store.getters[`${Model.ResourceCreationWizard}/isCheckedForAll`](this.type);
			},
			set(isChecked: boolean): void
			{
				this.$store.dispatch(`${Model.ResourceCreationWizard}/setCheckedForAll`, {
					type: this.type,
					isChecked,
				});
			},
		},
	},
	components: {
		ResourceNotificationCheckBoxRow,
	},
	template: `
		<ResourceNotificationCheckBoxRow
			v-model:checked="isCheckedForAll"
			:disabled="disabled"
		>
			<div class="resource-creation-wizard__form-notification-text">
				{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
			</div>
		</ResourceNotificationCheckBoxRow>
	`,
};
