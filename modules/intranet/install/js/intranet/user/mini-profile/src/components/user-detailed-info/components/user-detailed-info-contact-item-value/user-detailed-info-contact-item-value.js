import { UI } from 'ui.notification';

import './style.css';

type ContactItemType = 'mail' | 'phone';
const ContactItem: Record<string, ContactItemType> = Object.freeze({
	Mail: 'mail',
	Phone: 'phone',
});

// @vue/component
export const UserDetailedInfoContactItemValue = {
	name: 'UserDetailedInfoContactItemValue',
	props: {
		type: {
			/** @type ContactItemType */
			type: String,
			required: true,
		},
		value: {
			type: String,
			required: true,
		},
	},
	computed: {
		href(): string
		{
			if (this.type === ContactItem.Mail)
			{
				return `mailto:${this.value}`;
			}

			return null;
		},
	},
	methods: {
		onClick(event: Event): void
		{
			if (this.type === ContactItem.Phone)
			{
				event.preventDefault();
				if (navigator.clipboard)
				{
					navigator.clipboard.writeText(this.value)
						.then(() => {
							UI.Notification.Center.notify({
								content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_SUCCESS'),
							});
						})
						.catch(() => {
							UI.Notification.Center.notify({
								content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_ERROR'),
							});
						});
				}
				else
				{
					UI.Notification.Center.notify({
						content: this.$Bitrix.Loc.getMessage('INTRANET_USER_MINI_PROFILE_ACTION_CLIPBOARD_COPY_PHONE_ERROR'),
					});
				}
			}
		},
	},
	template: `
		<a 
			class="intranet-user-mini-profile__detailed-info__contact-item-value"
			:href="href"
			target
			:data-test-id="'usermp_contact-' + type"
			@click="onClick"
		> 
			{{ value }}
		</a>
	`,
};
