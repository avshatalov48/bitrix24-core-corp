import 'ui.forms';
import 'ui.layout-form';
import 'ui.design-tokens';
import 'ui.icon-set.main';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { NotificationsModel } from 'booking.model.notifications';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import { BaseInfo } from './base-info/base-info';
import { Confirmation } from './confirmation/confirmation';
import { Reminder } from './reminder/reminder';
import { Feedback } from './feedback/feedback';
import { Late } from './late/late';

import './resource-notification-card.css';

export const ResourceNotificationCard = {
	name: 'ResourceNotificationCard',
	components: {
		BaseInfo,
		Confirmation,
		Reminder,
		Late,
		Feedback,
	},
	methods: {
		...mapMutations(
			'resource-creation-wizard',
			['updateResource'],
		),
	},
	computed: {
		notificationViews(): { view: string, model: NotificationsModel }[]
		{
			return this.notifications.map((notificationsModel: NotificationsModel) => {
				switch (notificationsModel.type)
				{
					case this.dictionary.Info.value:
						return { view: 'BaseInfo', model: notificationsModel };
					case this.dictionary.Confirmation.value:
						return { view: 'Confirmation', model: notificationsModel };
					case this.dictionary.Reminder.value:
						return { view: 'Reminder', model: notificationsModel };
					case this.dictionary.Delayed.value:
						return { view: 'Late', model: notificationsModel };
					case this.dictionary.Feedback.value:
						return { view: 'Feedback', model: notificationsModel };
					default:
						return '';
				}
			});
		},
		...mapGetters({
			notifications: `${Model.Notifications}/get`,
			dictionary: `${Model.Dictionary}/getNotifications`,
			resource: `${Model.ResourceCreationWizard}/getResource`,
		}),
		resourceType(): ResourceTypeModel
		{
			return this.$store.getters['resourceTypes/getById'](this.resource.typeId);
		},
	},
	template: `
		<div class="resource-notification-card">
			<slot v-for="notification of notificationViews" :key="notification.view">
				<component
					:is="notification.view"
					:model="notification.model"
					:resourceType="this.resourceType"
					:data-id="'brcw-resource-notification-view-' + notification.view"
				/>
			</slot>
		</div>
	`,
};
