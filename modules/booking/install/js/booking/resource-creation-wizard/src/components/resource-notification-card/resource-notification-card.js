import { mapGetters } from 'ui.vue3.vuex';
import 'ui.forms';
import 'ui.layout-form';
import type { BitrixVueComponentProps } from 'ui.vue3';

import { Model } from 'booking.const';
import type { NotificationsModel } from 'booking.model.notifications';

import { BaseInfo } from './base-info/base-info';
import { Confirmation } from './confirmation/confirmation';
import { Reminder } from './reminder/reminder';
import { Feedback } from './feedback/feedback';
import { Late } from './late/late';

import './resource-notification-card.css';

// @vue/component
export const ResourceNotificationCard = {
	name: 'ResourceNotificationCard',
	computed: {
		notificationViews(): { view: BitrixVueComponentProps, model: NotificationsModel }[]
		{
			return this.notifications.map((model: NotificationsModel) => {
				return {
					[this.dictionary.Info.value]: { view: BaseInfo, model },
					[this.dictionary.Confirmation.value]: { view: Confirmation, model },
					[this.dictionary.Reminder.value]: { view: Reminder, model },
					[this.dictionary.Delayed.value]: { view: Late, model },
					[this.dictionary.Feedback.value]: { view: Feedback, model },
				}[model.type] ?? {};
			});
		},
		...mapGetters({
			notifications: `${Model.Notifications}/get`,
			dictionary: `${Model.Dictionary}/getNotifications`,
		}),
	},
	template: `
		<div class="resource-notification-card">
			<slot v-for="notification of notificationViews" :key="notification.view">
				<component
					:is="notification.view"
					:model="notification.model"
					:data-id="'brcw-resource-notification-view-' + notification.view"
				/>
			</slot>
		</div>
	`,
};
