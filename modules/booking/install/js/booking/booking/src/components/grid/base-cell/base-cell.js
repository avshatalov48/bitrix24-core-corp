import { Event } from 'main.core';
import { DateTimeFormat } from 'main.date';

import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import { limit } from 'booking.lib.limit';
import { grid } from 'booking.lib.grid';
import './base-cell.css';

/**
 * @typedef {Object} Cell
 * @property {string} id
 * @property {number} fromTs
 * @property {number} toTs
 * @property {number} resourceId
 * @property {boolean} boundedToBottom
 */
export const BaseCell = {
	props: {
		/** @type {Cell} */
		cell: {
			type: Object,
			required: true,
		},
	},
	components: {
		Icon,
	},
	data(): Object
	{
		return {
			IconSet,
		};
	},
	computed: {
		...mapGetters({
			selectedCells: `${Model.Interface}/selectedCells`,
			zoom: `${Model.Interface}/zoom`,
			intersections: `${Model.Interface}/intersections`,
			timezone: `${Model.Interface}/timezone`,
			offset: `${Model.Interface}/offset`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			draggedBookingId: `${Model.Interface}/draggedBookingId`,
		}),
		selected(): boolean
		{
			return this.cell.id in this.selectedCells;
		},
		hasSelectedCells(): boolean
		{
			return Object.keys(this.selectedCells).length > 0;
		},
		timeFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return this.loc('BOOKING_BOOKING_TIME_RANGE', {
				'#FROM#': DateTimeFormat.format(timeFormat, (this.cell.fromTs + this.offset) / 1000),
				'#TO#': DateTimeFormat.format(timeFormat, (this.cell.toTs + this.offset) / 1000),
			});
		},
		height(): number
		{
			return grid.calculateRealHeight(this.cell.fromTs, this.cell.toTs);
		},
	},
	methods: {
		onCellSelected({ target: { checked } }): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			if (checked)
			{
				this.$store.dispatch(`${Model.Interface}/addSelectedCell`, this.cell);
			}
			else
			{
				this.$store.dispatch(`${Model.Interface}/removeSelectedCell`, this.cell);
			}
		},
		onMouseDown(): void
		{
			if (!this.isFeatureEnabled)
			{
				void limit.show();

				return;
			}

			void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, null);

			this.creatingBookingId = `tmp-id-${Date.now()}-${Math.random()}`;

			void this.$store.dispatch(`${Model.Interface}/addQuickFilterIgnoredBookingId`, this.creatingBookingId);
			void this.$store.dispatch(`${Model.Bookings}/add`, {
				id: this.creatingBookingId,
				dateFromTs: this.cell.fromTs,
				dateToTs: this.cell.toTs,
				name: this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME'),
				resourcesIds: [...new Set([
					this.cell.resourceId,
					...(this.intersections[0] ?? []),
					...(this.intersections[this.cell.resourceId] ?? []),
				])],
				timezoneFrom: this.timezone,
				timezoneTo: this.timezone,
			});

			Event.bind(window, 'mouseup', this.addBooking);
		},
		addBooking(): void
		{
			Event.unbind(window, 'mouseup', this.addBooking);

			if (!this.isFeatureEnabled)
			{
				void limit.show();

				return;
			}

			setTimeout(() => {
				const creatingBooking = this.$store.getters[`${Model.Bookings}/getById`](this.creatingBookingId);

				void bookingService.add(creatingBooking);
			});
		},
	},
	template: `
		<div
			class="booking-booking-base-cell"
			:class="{
				'--selected': selected,
				'--bounded-to-bottom': cell.boundedToBottom,
				'--height-is-less-than-40': height < 40,
				'--compact-mode': height < 40 || zoom < 0.8,
				'--small': height <= 12.5,
			}"
			:style="{
				'--height': height + 'px',
			}"
			data-element="booking-base-cell"
			:data-resource-id="cell.resourceId"
			:data-from="cell.fromTs"
			:data-to="cell.toTs"
			:data-selected="selected"
		>
			<div class="booking-booking-grid-cell-padding">
				<div class="booking-booking-grid-cell-inner">
					<label
						class="booking-booking-grid-cell-time"
						data-element="booking-grid-cell-select-label"
					>
						<span class="booking-booking-grid-cell-time-inner">
							<input
								v-if="!draggedBookingId"
								class="booking-booking-grid-cell-checkbox"
								type="checkbox"
								:checked="selected"
								@change="onCellSelected"
							>
							<span data-element="booking-grid-cell-time">
								{{ timeFormatted }}
							</span>
						</span>
					</label>
					<div
						v-if="!hasSelectedCells && !draggedBookingId"
						class="booking-booking-grid-cell-select-button-container"
						ref="button"
					>
						<div
							class="booking-booking-grid-cell-select-button"
							:class="{'--lock': !isFeatureEnabled}"
							data-element="booking-grid-cell-add-button"
							@mousedown="onMouseDown"
						>
							<div class="booking-booking-grid-cell-select-button-text">
								{{ loc('BOOKING_BOOKING_SELECT') }}
								<Icon v-if="!isFeatureEnabled" :name="IconSet.LOCK" />
							</div>
							<div class="ui-icon-set --chevron-right"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
