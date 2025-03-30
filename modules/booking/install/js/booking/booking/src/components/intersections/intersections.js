import { Dom } from 'main.core';
import { MenuManager, MenuItem, MenuItemOptions } from 'main.popup';

import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { HelpDesk, Model, Option } from 'booking.const';
import { optionService } from 'booking.provider.service.option-service';
import { limit } from 'booking.lib.limit';
import { helpDesk } from 'booking.lib.help-desk';
import { busySlots } from 'booking.lib.busy-slots';

import { Multiple } from './multiple/multiple';
import { Single } from './single/single';
import './intersections.css';

export const Intersections = {
	components: {
		Icon,
		Multiple,
		Single,
	},
	data(): Object
	{
		return {
			IconSet,
			intersectionModeMenuItemId: 'booking-intersection-menu-mode',
		};
	},
	mounted(): void
	{
		this.menu = MenuManager.create(
			'booking-intersection-menu',
			this.$refs.intersectionMenu,
			this.getMenuItems(),
			{
				closeByEsc: true,
				autoHide: true,
				cacheable: true,
			},
		);
	},
	unmounted(): void
	{
		this.menu.destroy();
		this.menu = null;
	},
	methods: {
		showMenu(): void
		{
			if (this.isFeatureEnabled)
			{
				this.menu.show();
			}
			else
			{
				void limit.show();
			}
		},
		getMenuItems(): MenuItemOptions[]
		{
			return [
				this.getIntersectionForAllItem(),
				{
					delimiter: true,
				},
				this.getHelpDeskItem(),
			];
		},
		getIntersectionForAllItem(): MenuItemOptions
		{
			return {
				id: this.intersectionModeMenuItemId,
				dataset: {
					id: this.intersectionModeMenuItemId,
				},
				text: this.loc('BOOKING_BOOKING_INTERSECTION_MENU_ALL'),
				className: this.isIntersectionForAll
					? 'menu-popup-item menu-popup-item-accept'
					: 'menu-popup-item menu-popup-no-icon',
				onclick: () => {
					this.menu.close();

					const value = !this.isIntersectionForAll;

					void this.$store.dispatch(`${Model.Interface}/setIntersectionMode`, value);
					void optionService.setBool(Option.IntersectionForAll, value);
				},
			};
		},
		getHelpDeskItem(): MenuItemOptions
		{
			return {
				id: 'booking-intersection-menu-info',
				dataset: {
					id: 'booking-intersection-menu-info',
				},
				text: this.loc('BOOKING_BOOKING_INTERSECTION_MENU_HOW'),
				onclick: () => {
					helpDesk.show(
						HelpDesk.Intersection.code,
						HelpDesk.Intersection.anchorCode,
					);
				},
			};
		},
		async showIntersections(selectedResourceIds: number[], resourceId: number = 0): void
		{
			const intersections = {
				...(resourceId === 0 ? {} : this.intersections),
				[resourceId]: selectedResourceIds,
			};

			await this.$store.dispatch(`${Model.Interface}/setIntersections`, intersections);

			await busySlots.loadBusySlots();
		},
		toggleMenuItemActivityState(item: MenuItem): void
		{
			Dom.toggleClass(item.getContainer(), 'menu-popup-item-accept');
			Dom.toggleClass(item.getContainer(), 'menu-popup-no-icon');
		},
		updateScroll(): void
		{
			if (this.$refs.inner)
			{
				this.$refs.inner.scrollLeft = this.scroll;
			}
		},
	},
	watch: {
		async isIntersectionForAll(): void
		{
			await this.$store.dispatch(`${Model.Interface}/setIntersections`, {});

			this.updateScroll();

			await busySlots.loadBusySlots();

			this.toggleMenuItemActivityState(
				this.menu.getMenuItem(this.intersectionModeMenuItemId),
			);
		},
		scroll(): void
		{
			this.updateScroll();
		},
	},
	computed: {
		...mapGetters({
			resourcesIds: `${Model.Interface}/resourcesIds`,
			isFilterMode: `${Model.Interface}/isFilterMode`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			intersections: `${Model.Interface}/intersections`,
			isIntersectionForAll: `${Model.Interface}/isIntersectionForAll`,
			scroll: `${Model.Interface}/scroll`,
			isLoaded: `${Model.Interface}/isLoaded`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		hasIntersections(): boolean
		{
			return Object.values(this.intersections).some((resourcesIds: number[]) => resourcesIds.length > 0);
		},
		disabled(): boolean
		{
			return !this.isLoaded || this.isFilterMode || this.isEditingBookingMode;
		},
	},
	template: `
		<div class="booking-booking-intersections" :class="{'--disabled': disabled}">
			<div
				ref="intersectionMenu"
				class="booking-booking-intersections-left-panel"
				:class="{'--active': hasIntersections}"
				@click="showMenu"
				data-id="booking-intersections-left-panel-menu"
			>
				<div class="ui-icon-set --double-rhombus"></div>
				<div v-if="!isFeatureEnabled" class="booking-lock-icon-container">
					<Icon :name="IconSet.LOCK"/>
				</div>
			</div>
			<Single v-if="isIntersectionForAll" @change="showIntersections"/>
			<template v-else>
				<div
					ref="inner"
					class="booking-booking-intersections-inner"
					@scroll="$store.dispatch('interface/setScroll', $refs.inner.scrollLeft)"
				>
					<div class="booking-booking-intersections-row">
						<div class="booking-booking-intersections-row-inner">
							<template v-for="resourceId of resourcesIds" :key="resourceId">
								<Multiple :resourceId="resourceId" @change="showIntersections"/>
							</template>
						</div>
					</div>
					<div class="booking-booking-intersections-inner-blank"></div>
				</div>
			</template>
		</div>
	`,
};
