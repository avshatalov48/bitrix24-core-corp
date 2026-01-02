import { Loader } from 'main.loader';

import { locMixin } from 'booking.component.mixin.loc-mixin';

import { Occupancy, createOccupancy } from '../../occupancy';
import { ResourceSlotsUiBlock } from '../resource-slots-ui-block/resource-slots-ui-block';
import type { ResourceSlotsUiBlockSelectEventPayload } from '../resource-slots-ui-block/resource-slots-ui-block';
import './available-slots-block.css';

const DELAY = 300;

// @vue/component
export const AvailableSlotsBlock = {
	name: 'AvailableSlotsBlock',
	components: {
		ResourceSlotsUiBlock,
	},
	mixins: [locMixin],
	inject: ['isPreview'],
	props: {
		date: {
			type: Date,
			required: true,
		},
		resources: {
			type: Array,
			required: true,
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
	emits: ['update:form', 'update:resourceId'],
	data(): { fetching: boolean }
	{
		return {
			fetching: false,
			selectedResourceId: null,
			visibleResourcesCount: 3,
			resourceOccupancy: [],
			resourcesSlots: [],
		};
	},
	computed: {
		availableResource(): Array
		{
			return this.resourcesSlots.filter(({ slots }) => slots.length > 0);
		},
		visibleResources(): Array
		{
			return this.availableResource.slice(0, this.visibleResourcesCount);
		},
		emptyResourcesSlotsMessage(): string
		{
			return this.loc('BOOKING_CRM_FORMS_RESOURCE_NO_SLOTS_MESSAGE', {
				'#BR#': '<br />',
			});
		},
	},
	watch: {
		date: {
			handler(date: Date | null): void
			{
				if (date instanceof Date && !this.isPreview)
				{
					void this.fetchAvailableSlots();
				}
			},
			immediate: true,
		},
		fetching: {
			handler(fetching: boolean): void
			{
				if (fetching)
				{
					this.loader?.show(this.$refs.resources);

					return;
				}

				this.loader?.hide();
			},
			immediate: true,
		},
	},
	created(): void
	{
		this.initOccupancy();
	},
	beforeMount(): void
	{
		this.loader = new Loader({
			target: this.$refs.resources,
			size: 60,
		});
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
		async fetchAvailableSlots(): void
		{
			this.fetching = true;

			if (!this.occupancy)
			{
				this.initOccupancy();
			}

			try
			{
				const resourcesIds = this.resources.map((resource) => resource.id);
				const response = await this.occupancy.getOccupancy(resourcesIds, this.date.getTime());

				this.resourceOccupancy = response || [];
				this.setResourcesSlots();
			}
			catch (error)
			{
				console.error('Booking.CrmForms. GetOccupancy for resources error', error);
			}
			finally
			{
				this.fetching = false;
			}
		},
		setResourcesSlots(): void
		{
			this.resourcesSlots = this.resources
				.map((resource) => {
					const resourceId = resource.id;
					const resourceOccupancies = this.resourceOccupancy;

					return {
						resourceId,
						resource,
						slots: Occupancy.calcResourceSlots({
							date: this.date,
							slotRanges: resource.slotRanges,
							resourceOccupancy: resourceOccupancies,
							resourceId,
							timezone: this.timezone,
						}),
					};
				})
				.sort((a, b) => b.slots.length - a.slots.length);
		},
		showMore(): void
		{
			this.visibleResourcesCount += 3;
		},
		changeDate(resourceId: number): void
		{
			this.selectedResourceId = resourceId;

			setTimeout(() => {
				this.$emit('update:form', { resourceId });
			}, DELAY);
		},
		selectSlot({ resource, slot }: ResourceSlotsUiBlockSelectEventPayload): void
		{
			this.selectedResourceId = resource.id;

			setTimeout(() => {
				this.$emit('update:form', {
					resourceId: resource.id,
					slot,
				});
			}, DELAY);
		},
	},
	template: `
		<div ref="resources" class="booking--crm-forms--field-group">
			<template v-show="!fetching">
				<ResourceSlotsUiBlock
					v-for="resource in visibleResources"
					:key="resource.resourceId"
					:date="date"
					:resource="resource.resource"
					:resourceSlots="resource.slots"
					:class="{
						'--fade': selectedResourceId !== null && selectedResourceId !== resource.resourceId,
					}"
					@select="selectSlot"
				>
					<template #changeDateBtn>
						<button
							type="button"
							class="booking--crm-forms--change-date-btn"
							@click="changeDate(resource.resourceId)"
						>
							{{ loc('BOOKING_CRM_FORMS_CHANGE_DATE_BUTTON_CAPTION') }}
						</button>
					</template>
				</ResourceSlotsUiBlock>
			</template>
			<template v-if="!fetching && visibleResources.length === 0">
				<p
					class="booking--crm-forms--available-slots-block__empty-slots"
					v-html="emptyResourcesSlotsMessage"
				></p>
			</template>
			<template v-if="resources.length > 0 && visibleResources.length > 0 && visibleResourcesCount < availableResource.length">
				<div class="booking--crm-forms--field-group--available-slots-block__footer">
					<button
						type="button"
						class="booking--crm-forms--change-date-btn booking--crm-forms--field-group--available-slots-block__btn-show-more"
						@click="showMore"
					>
						{{ loc('BOOKING_CRM_FORMS_SHOW_MORE_SLOTS') }}
					</button>
				</div>
			</template>
		</div>
	`,
};
