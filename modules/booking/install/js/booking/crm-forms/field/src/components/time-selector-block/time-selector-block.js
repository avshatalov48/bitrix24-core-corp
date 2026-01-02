import { locMixin } from 'booking.component.mixin.loc-mixin';

import { Occupancy, createOccupancy } from '../../occupancy';
import { ResourceSlotsUiBlock } from '../resource-slots-ui-block/resource-slots-ui-block';
// eslint-disable-next-line no-unused-vars
import type { Resource, ResourceSlot, ResourceOccupancy } from '../../types';
import './time-selector-block.css';

type TimeSelectorBlockData = {
	resourceOccupancy: ResourceOccupancy,
}

// @vue/component
export const TimeSelectorBlock = {
	name: 'TimeSelectorBlock',
	components: {
		ResourceSlotsUiBlock,
	},
	mixins: [locMixin],
	inject: ['isPreview'],
	props: {
		slot: {
			type: Object,
			default: null,
		},
		/**
		 * @type {Resource}
		 */
		resource: {
			type: Object,
			default: null,
		},
		/**
		 * @type {Resource[]}
		 */
		resources: {
			type: Array,
			default: () => [],
		},
		date: {
			type: Date,
			required: true,
		},
		fetching: {
			type: Boolean,
			default: false,
		},
		showChangeDateButton: {
			type: Boolean,
			default: false,
		},
		runAction: {
			type: Function,
			required: true,
		},
		timezone: {
			type: String,
			required: true,
		},
	},
	emits: ['update:slot', 'update:fetching', 'showCalendar'],
	data(): TimeSelectorBlockData
	{
		return {
			resourceOccupancy: [],
		};
	},
	computed: {
		resourceSlots(): ResourceSlot[]
		{
			return Occupancy.calcResourceSlots({
				date: this.date,
				slotRanges: this.resource?.slotRanges || [],
				resourceOccupancy: this.resourceOccupancy || [],
				timezone: this.timezone,
			});
		},
	},
	watch: {
		date: {
			handler(date: Date | null): void
			{
				if (date instanceof Date && !this.isPreview)
				{
					void this.fetchOccupancy();
				}
			},
			immediate: true,
		},
		resource: {
			handler(): void
			{
				if (!this.isPreview)
				{
					void this.fetchOccupancy();
				}
			},
		},
	},
	created(): void
	{
		this.initOccupancy();
	},
	methods: {
		initOccupancy(): void
		{
			if (this.occupancy instanceof Occupancy)
			{
				return;
			}

			this.occupancy = createOccupancy(this.runAction);
			this.occupancy.setResources(this.resources);
			this.occupancy.setTimezone(this.timezone);
		},
		async fetchOccupancy(): Promise<void>
		{
			if (!this.date || this.fetching || this.resource === null)
			{
				return;
			}

			if (!this.occupancy)
			{
				this.initOccupancy();
			}

			this.$emit('update:fetching', true);

			try
			{
				const response = await this.occupancy.getOccupancy([this.resource.id], this.date.getTime());
				this.resourceOccupancy = response || [];
			}
			catch (error)
			{
				console.error('Booking.CrmForms. GetOccupancy error', error);
			}
			finally
			{
				this.$emit('update:fetching', false);
			}
		},
		changeSlot({ slot }: { slot: ResourceSlot }): void
		{
			this.$emit('update:slot', slot);
		},
		changeDate(): void
		{
			this.$emit('showCalendar');
		},
	},
	template: `
		<ResourceSlotsUiBlock
			:slot="slot"
			:resource="resource"
			:date="date"
			:resourceSlots="resourceSlots"
			:loading="fetching"
			@select="changeSlot"
		>
			<template #changeDateBtn>
				<button
					v-if="showChangeDateButton"
					type="button"
					class="booking--crm-forms--change-date-btn"
					@click="changeDate"
				>
					{{ loc('BOOKING_CRM_FORMS_CHANGE_DATE_BUTTON_CAPTION') }}
				</button>
			</template>
		</ResourceSlotsUiBlock>
	`,
};
