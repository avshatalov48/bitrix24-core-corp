import { DateTimeFormat } from 'main.date';
import { Loader } from 'main.loader';

import { locMixin } from 'booking.component.mixin.loc-mixin';
// eslint-disable-next-line no-unused-vars
import type { Resource, ResourceSlot } from '../../types';
import './resource-slots.css';

export type ResourceSlotsUiBlockSelectEventPayload = {
	date: Date,
	resource: Resource,
	slot: ResourceSlot,
}

// @vue/component
export const ResourceSlotsUiBlock = {
	name: 'ResourceSlotsUiBlock',
	mixins: [locMixin],
	props: {
		/**
		 * @type {ResourceSlot|null}
		 */
		slot: {
			type: Object,
			default: null,
		},
		date: {
			type: Date,
			required: true,
		},
		/**
		 * @type {Resource}
		 */
		resource: {
			type: Object,
			required: true,
		},
		resourceSlots: {
			type: Array,
			default: () => [],
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['select'],
	computed: {
		formatDate(): string
		{
			return DateTimeFormat.format(this.loc('DAY_MONTH_FORMAT'), this.date);
		},
		title(): string
		{
			return this.loc('BOOKING_CRM_FORMS_FIELD_TIME_TITLE', {
				'#DATE#': this.formatDate,
			});
		},
		emptySlotsMessage(): string
		{
			const day = this.date.getDay();
			if (this.resource.slotRanges.every(({ weekDays }) => !weekDays.includes(day)))
			{
				return this.loc('BOOKING_CRM_FORMS_RESOURCE_RESOURCE_NOT_WORKING_MESSAGE');
			}

			return this.loc('BOOKING_CRM_FORMS_RESOURCE_NO_SLOTS_MESSAGE', {
				'#BR#': '<br />',
			});
		},
	},
	watch: {
		loading: {
			handler(loading: boolean): void
			{
				if (loading)
				{
					this.loader?.show?.(this.$refs.slotsContainer);
				}
				else
				{
					this.loader?.hide?.();
				}
			},
			immediate: true,
		},
	},
	beforeMount(): void
	{
		this.loader = new Loader({
			target: this.$refs.slotsContainer,
			size: 60,
		});
	},
	methods: {
		selectSlot(slot: ResourceSlot): void
		{
			const payload: ResourceSlotsUiBlockSelectEventPayload = {
				date: this.date,
				resource: this.resource,
				slot,
			};
			this.$emit('select', payload);
		},
	},
	template: `
		<div class="booking-crm-forms-field booking--crm-forms--resource-slots">
			<slot name="title"/>
			<div class="booking--crm-forms--time-selector-block-header">
				<div class="booking--crm-forms--time-selector-block-resource">
					<div class="booking--crm-forms--time-selector-block-resource-name">
						{{ resource.name }}
					</div>
					<div class="booking--crm-forms--time-selector-block-resource-type-name">
						{{ resource.typeName }}
					</div>
				</div>
				<div class="booking--crm-forms--time-selector-block-header__button">
					<slot name="changeDateBtn" :date="date" :resource="resource"/>
				</div>
			</div>
			<div ref="slotsContainer" class="booking--crm-forms--resource-slots__slot-list-wrapper">
				<div v-show="!loading" class="booking--crm-forms--resource-slots__slot-list">
					<div
						v-for="resourceSlot in resourceSlots"
						:key="resourceSlot"
						class="booking--crm-forms-time-selector-block-time-list-item booking--crm-forms--resource-slots__slot"
						:class="{
							'--selected': slot !== null && resourceSlot.fromTs === slot.fromTs
						}"
						@click="selectSlot(resourceSlot)"
					>
						<span>{{ resourceSlot.label }}</span>
					</div>
					<template v-if="resourceSlots.length === 0">
						<div class="booking--crm-forms--resource-slots__empty-slots">
							<div
								class="booking--crm-forms--resource-slots__empty-slots-message"
								v-html="emptySlotsMessage"
							>
							</div>
						</div>
					</template>
				</div>
			</div>
		</div>
	`,
};
