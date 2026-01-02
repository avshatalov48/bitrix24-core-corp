import { Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon, Outline } from 'ui.icon-set.api.vue';

import { AhaMoment, Model } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { filterResultNavigator } from 'booking.lib.filter-result-navigator';
import { CounterFloatingHintPopup } from './counter-floating-hint-popup';
import { CounterFloatingLimitPopup } from './counter-floating-limit-popup';

import './style.css';

const ANIMATION_TIME_MS = 500;
const LIMIT_POPUP_TIME_MS = 5000;

// @vue/component
export const CounterFloating = {
	name: 'UiCounterFloating',
	components: {
		BIcon,
		CounterFloatingHintPopup,
		CounterFloatingLimitPopup,
	},
	props: {
		count: {
			type: [String, Number],
			default: '',
		},
	},
	emits: ['previous', 'next'],
	setup(): CounterFloatingSetupObject
	{
		const iconColor = 'var(--ui-color-base-8)';
		const iconSize = 16;

		return {
			Outline,
			iconColor,
			iconSize,
		};
	},
	data(): CounterFloatingData
	{
		return {
			shownAha: false,
			shownLimitPopup: false,
			shownLimitAnimation: false,
			limitPopupTimeoutId: null,
		};
	},
	computed: {
		...mapGetters({
			isMaxFilterDate: `${Model.Filter}/isMaxFilterDate`,
			isMinFilterDate: `${Model.Filter}/isMinFilterDate`,
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
		}),
		filterCount(): string
		{
			return Loc.getMessagePlural('BOOKING_BOOKING_FILTER_COUNTER_FLOATING_COUNT', this.count, {
				'#COUNT#': this.count,
			});
		},
	},
	mounted(): void
	{
		if (this.count > 0 && this.$refs.container && ahaMoments.shouldShow(AhaMoment.SearchNavigation))
		{
			setTimeout(() => {
				this.shownAha = true;
			}, 300);
		}
	},
	methods: {
		async setSelectedDateTs(nextSelectedDateTs: number): Promise<void>
		{
			if (!nextSelectedDateTs)
			{
				return;
			}

			await this.$store.dispatch(`${Model.Interface}/setSelectedDateTs`, nextSelectedDateTs);
		},
		async previous(): void
		{
			if (this.isMinFilterDate)
			{
				this.startLimitAnimation();
				this.showLimitPopup();

				return;
			}

			const previousDateTs = await filterResultNavigator.getPreviousFilterDateTs();
			if (previousDateTs)
			{
				await this.setSelectedDateTs(previousDateTs);
			}
		},
		async next(): void
		{
			if (this.isMaxFilterDate)
			{
				this.startLimitAnimation();
				this.showLimitPopup();

				return;
			}

			const nextFilterDateTs = await filterResultNavigator.getNextFilterDateTs();
			if (nextFilterDateTs)
			{
				await this.setSelectedDateTs(nextFilterDateTs);
			}
		},
		showLimitPopup(): void
		{
			if (this.shownLimitPopup)
			{
				clearTimeout(this.limitPopupTimeoutId);
				this.shownLimitPopup = false;
			}

			this.$nextTick(() => {
				this.shownLimitPopup = true;

				this.limitPopupTimeoutId = setTimeout(() => {
					this.shownLimitPopup = false;
				}, LIMIT_POPUP_TIME_MS);
			});
		},
		startLimitAnimation(): void
		{
			this.shownLimitAnimation = true;

			setTimeout(() => {
				this.shownLimitAnimation = false;
			}, ANIMATION_TIME_MS);
		},
	},
	template: `
		<div ref="container" class="booking-booking-counter-floating">
			<div :class="['booking--booking--counter-floating_row', {
				'--twitching': shownLimitAnimation,
			}]">
				<div class="booking-booking-counter-floating-text">
					{{ filterCount }}
				</div>
				<div class="booking-booking-counter-floating-buttons">
					<div
						:class="{
							'booking-booking-counter-floating-action-button': true,
							'--disabled': isMinFilterDate,
						}"
						@click="previous"
					>
						<BIcon :name="Outline.CHEVRON_LEFT_L" :size="iconSize" :color="iconColor"/>
					</div>
					<div
						:class="{
							'booking-booking-counter-floating-action-button': true,
							'--disabled': isMaxFilterDate
						}"
						@click="next"
					>
						<BIcon :name="Outline.CHEVRON_RIGHT_L" :size="iconSize" :color="iconColor"/>
					</div>
				</div>
			</div>
			<template v-if="shownAha">
				<CounterFloatingHintPopup :count="count" :bindElement="$refs.container" @close="shownAha = false"/>
			</template>
			<template v-if="shownLimitPopup">
				<CounterFloatingLimitPopup :bindElement="$refs.container"/>
			</template>
		</div>
	`,
};

type CounterFloatingSetupObject = {
	iconColor: string,
	iconSize: number,
	Outline: { [key: string]: string }
}

type CounterFloatingData = {
	shownAha: boolean;
	shownLimitPopup: boolean;
	shownLimitAnimation: boolean;
	limitPopupTimeoutId: number | null;
}
