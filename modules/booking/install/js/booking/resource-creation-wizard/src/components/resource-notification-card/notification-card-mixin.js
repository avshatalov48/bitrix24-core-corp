import { NotificationChannel } from 'booking.const';

export const NotificationCardMixin = {
	methods: {
		getMessageTemplate(messenger: string): string
		{
			switch (messenger)
			{
				case NotificationChannel.WhatsApp:
					return this.template?.text || '';
				case NotificationChannel.Sms:
					return this.template?.textSms || '';
				default:
					return '';
			}
		},
	},
	mounted()
	{
		// set settings from resource type for new resource
		if (!this.resource.id)
		{
			this.updateResource({
				isConfirmationNotificationOn: this.resourceType.isConfirmationNotificationOn,
				isFeedbackNotificationOn: this.resourceType.isFeedbackNotificationOn,
				isInfoNotificationOn: this.resourceType.isInfoNotificationOn,
				isDelayedNotificationOn: this.resourceType.isDelayedNotificationOn,
				isReminderNotificationOn: this.resourceType.isReminderNotificationOn,
				templateTypeConfirmation: this.resourceType.templateTypeConfirmation,
				templateTypeFeedback: this.resourceType.templateTypeFeedback,
				templateTypeInfo: this.resourceType.templateTypeInfo,
				templateTypeDelayed: this.resourceType.templateTypeDelayed,
				templateTypeReminder: this.resourceType.templateTypeReminder,
			});
		}
	},
	computed: {
		isCheckedForAll: {
			get(): boolean
			{
				return this.isCheckedForAllLocal;
			},
			set(isChecked: boolean)
			{
				this.isCheckedForAllLocal = isChecked;
				this.updateResourceType();
			},
		},
	},
};
