// @vue/component

import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { Duration } from 'booking.lib.duration';

import { WaitListGroupMenu } from './wait-list-group-menu/wait-list-group-menu';
import type { WaitListItemModel, WaitListGroup } from './types';
import './wait-list-groups.css';

export const WaitListGroups = {
	name: 'WaitListGroups',
	components: {
		Icon,
		WaitListGroupMenu,
	},
	setup(): { dragManager: ?DragManager, IconSet: Object }
	{
		const dragManager = null;

		return {
			dragManager,
			IconSet,
		};
	},
	computed: {
		...mapGetters({
			editingWaitListItemId: `${Model.Interface}/editingWaitListItemId`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			animationPause: `${Model.Interface}/animationPause`,
			waitListItems: `${Model.WaitList}/get`,
		}),
		waitListGroups(): WaitListGroup[]
		{
			const groups: { title: string, items: WaitListItemModel[]}[] = [
				{
					title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_TODAY'),
					items: [],
				},
				{
					title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_THIS_WEEK'),
					items: [],
				},
				{
					title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_THIS_MONTH'),
					items: [],
				},
				{
					title: this.loc('BOOKING_BOOKING_WAIT_LIST_ADDED_OVER_MONTH'),
					items: [],
				},
			];

			const today = new Date().setHours(0, 0, 0, 0);
			const { d: dayDuration, w: weekDuration, m: monthDuration } = Duration.getUnitDurations();

			[...this.waitListItems]
				.sort((a, b) => b.createdAt - a.createdAt)
				.forEach((waitListItem) => {
					const duration = today - new Date(waitListItem.createdAt).setHours(0, 0, 0, 0);

					if (duration < dayDuration)
					{
						groups[0].items.push(waitListItem);
					}
					else if (duration < weekDuration)
					{
						groups[1].items.push(waitListItem);
					}
					else if (duration < monthDuration)
					{
						groups[2].items.push(waitListItem);
					}
					else
					{
						groups[3].items.push(waitListItem);
					}
				});

			return groups.filter(({ items }) => items.length > 0);
		},
	},
	template: `
		<div class="booking-wait-list-groups">
			<div v-for="group of waitListGroups" :key="group.title" class="booking-wait-list-group">
				<div class="booking-wait-list-group-header">
					<div class="booking-wait-list-group-title">{{ group.title }}</div>
					<div class="booking-sidebar-button">
						<WaitListGroupMenu :waitListGroup="group"/>
					</div>
				</div>
				<TransitionGroup :name="animationPause ? 'none' : 'wait-list'" tag="div">
					<template v-for="item of group.items" :key="item.id">
						<slot name="item" :item="item"/>
					</template>
				</TransitionGroup>
			</div>
		</div>
	`,
};
