import { Ears } from 'ui.ears';
import { Model } from 'booking.const';
import { MultiBookingItem } from './multi-booking-item';
import './multi-booking-items-list.css';

export const MultiBookingItemsList = {
	name: 'MultiBookingItemsList',
	emits: ['remove-selected'],
	computed: {
		selectedCells(): Object
		{
			return this.$store.getters[`${Model.Interface}/selectedCells`];
		},
		selectedCellsCount(): number
		{
			return Object.keys(this.selectedCells).length;
		},
	},
	mounted()
	{
		this.ears = new Ears({
			container: this.$refs.wrapper,
			smallSize: true,
			className: 'booking--multi-booking--items-ears',
			noScrollbar: true,
		}).init();
	},
	watch: {
		selectedCellsCount: {
			handler(): void
			{
				setTimeout(() => this.ears.toggleEars(), 0);
			},
		},
	},
	components: {
		MultiBookingItem,
	},
	template: `
		<div class="booking--multi-booking--book-list">
			<div ref="wrapper" class="booking--multi-booking--books-wrapper">
				<MultiBookingItem
					v-for="cell in selectedCells"
					:key="cell.id"
					:id="cell.id"
					:from-ts="cell.fromTs"
					:to-ts="cell.toTs"
					:resource-id="cell.resourceId"
					@remove-selected="$emit('remove-selected', $event)"/>
			</div>
		</div>
	`,
};
