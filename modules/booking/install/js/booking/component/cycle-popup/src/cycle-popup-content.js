import { CardId } from './const/card-id';
import { Step } from './step/step';
import './cycle-popup.css';

type StepOptions = {
	id: string,
	title: string,
	cards: CardOptions[],
};

type CardOptions = {
	id: string,
	title: string,
	description: string,
};

// @vue/component
export const CyclePopupContent = {
	components: {
		Step,
	},
	provide(): Object
	{
		return {
			context: this.context,
		};
	},
	props: {
		context: {
			type: String,
			default: null,
		},
		scrollToCard: {
			type: String,
			default: null,
		},
	},
	emits: ['close'],
	computed: {
		steps(): StepOptions[]
		{
			return [
				{
					id: 'booked',
					title: this.loc('BOOKING_CYCLE_POPUP_BOOKED'),
					cards: [
						{
							id: CardId.Default,
							title: this.loc('BOOKING_CYCLE_POPUP_NEW_TITLE'),
							description: this.loc('BOOKING_CYCLE_POPUP_NEW_DESCRIPTION'),
						},
						{
							id: CardId.Unconfirmed,
							title: this.loc('BOOKING_CYCLE_POPUP_UNCONFIRMED_TITLE'),
							description: this.loc('BOOKING_CYCLE_POPUP_UNCONFIRMED_DESCRIPTION'),
						},
					],
				},
				{
					id: 'confirmed',
					title: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_MSGVER1'),
					cards: [
						{
							id: CardId.Confirmed,
							title: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_TITLE'),
							description: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_DESCRIPTION'),
						},
					],
				},
				{
					id: 'late',
					title: this.loc('BOOKING_CYCLE_POPUP_LATE_MSGVER1'),
					cards: [
						{
							id: CardId.Late,
							title: this.loc('BOOKING_CYCLE_POPUP_LATE_TITLE_MSGVER1'),
							description: this.loc('BOOKING_CYCLE_POPUP_LATE_DESCRIPTION'),
						},
					],
				},
				{
					id: 'waitlist',
					title: this.loc('BOOKING_CYCLE_POPUP_WAITLIST'),
					cards: [
						{
							id: CardId.Waitlist,
							title: this.loc('BOOKING_CYCLE_POPUP_WAITLIST_TITLE_MSGVER1'),
							description: this.loc('BOOKING_CYCLE_POPUP_WAITLIST_DESCRIPTION'),
						},
					],
				},
				{
					id: 'overbooking',
					title: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING'),
					cards: [
						{
							id: CardId.Overbooking,
							title: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING_TITLE_MSGVER1'),
							description: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING_DESCRIPTION_MSGVER1'),
						},
					],
				},
			];
		},
	},
	mounted(): void
	{
		setTimeout(() => {
			this.$refs.scrollable.scroll({
				top: this.getScrollElement(this.scrollToCard)?.offsetTop,
				behavior: 'smooth',
			});
		});
	},
	methods: {
		getScrollElement(cardId: string): HTMLElement
		{
			const step: StepOptions = this.steps.find((it) => it.cards.some((card) => card.id === cardId));
			if (step?.cards.length === 1)
			{
				return this.$refs.container.querySelector(`[data-step-id="${step.id}"]`);
			}

			return this.$refs.container.querySelector(`[data-card-id="${cardId}"]`);
		},
	},
	template: `
		<div class="booking-cycle-popup" ref="container">
			<div class="booking-cycle-popup-header">
				<div class="booking-cycle-popup-title">{{ loc('BOOKING_CYCLE_POPUP_TITLE') }}</div>
				<div class="booking-cycle-popup-description">{{ loc('BOOKING_CYCLE_POPUP_DESCRIPTION') }}</div>
			</div>
			<div class="booking-cycle-popup-main" ref="scrollable">
				<template v-for="(step, index) of steps" :key="index">
					<Step
						:id="step.id"
						:ordinal="index + 1"
						:title="step.title"
						:cards="step.cards"
					/>
				</template>
			</div>
		</div>
	`,
};
