export type { AdvertisingResourceType } from 'booking.model.resource-creation-wizard';
import type { NotificationsModel, NotificationsSenderModel } from 'booking.model.notifications';

export type NotificationsSettings = {
	notifications: NotificationsModel[],
	senders: NotificationsSenderModel[],
}
