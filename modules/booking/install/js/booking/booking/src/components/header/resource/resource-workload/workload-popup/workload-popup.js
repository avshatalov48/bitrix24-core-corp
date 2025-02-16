import { StatisticsPopup } from 'booking.component.statistics-popup';

export const WorkloadPopup = {
	emits: ['close'],
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
		slotsCount: {
			type: Number,
			required: true,
		},
		bookingCount: {
			type: Number,
			required: true,
		},
		workLoadPercent: {
			type: Number,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},
	computed: {
		popupId(): string
		{
			return 'booking-booking-resource-workload-popup';
		},
		title(): string
		{
			return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_POPUP_TITLE');
		},
		rows(): { title: string, value: string }[]
		{
			return [
				{
					title: this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_SLOTS_BOOKED'),
					value: this.slotsBookedFormatted,
					dataset: {
						element: 'booking-resource-workload-popup-count',
						resourceId: this.resourceId,
						bookedCount: this.bookingCount,
						totalCount: this.slotsCount,
					},
				},
				{
					title: this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD'),
					value: this.workLoadPercentFormatted,
					dataset: {
						element: 'booking-resource-workload-popup-percent',
						resourceId: this.resourceId,
						percent: this.workLoadPercent,
					},
				},
			];
		},
		slotsBookedFormatted(): string
		{
			return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_BOOKED_FROM_SLOTS_COUNT', {
				'#BOOKED#': this.bookingCount,
				'#SLOTS_COUNT#': this.slotsCount,
			});
		},
		workLoadPercentFormatted(): string
		{
			return this.loc('BOOKING_BOOKING_RESOURCE_WORKLOAD_PERCENT', {
				'#PERCENT#': this.workLoadPercent,
			});
		},
	},
	components: {
		StatisticsPopup,
	},
	template: `
		<StatisticsPopup
			:popupId="popupId"
			:bindElement="bindElement"
			:title="title"
			:rows="rows"
			:dataset="{
				id: resourceId,
				element: 'booking-resource-workload-popup',
			}"
			@close="$emit('close')"
		/>
	`,
};
