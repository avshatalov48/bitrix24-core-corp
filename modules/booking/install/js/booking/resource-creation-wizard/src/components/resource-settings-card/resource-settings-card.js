import { createNamespacedHelpers, mapGetters } from 'ui.vue3.vuex';
import { Type } from 'main.core';
import { Model } from 'booking.const';
import { resourceCreationWizardService } from 'booking.provider.service.resource-creation-wizard-service';
import { BaseFields } from './base-fields/base-fields';
import { ScheduleTypes } from './schedule-types/schedule-types';
import { WorkTime } from './work-time/work-time';
import { SlotLength } from './slot-length/slot-length';

import type { SlotRange } from 'booking.model.resources';
import type { ResourceTypeModel } from 'booking.model.resource-types';

import 'ui.forms';
import 'ui.layout-form';
import 'ui.icon-set.main';

import './resource-settings-card.css';

const { mapGetters: mapResourceGetters, mapActions, mapMutations } = createNamespacedHelpers('resource-creation-wizard');

export const ResourceSettingsCard = {
	name: 'ResourceSettingsCard',
	components: {
		BaseFields,
		ScheduleTypes,
		WorkTime,
		SlotLength,
	},
	created(): void
	{
		this.initialTimezone = this.getInitialSlotTimeZone();
		this.selectedSlotLength = this.resource.slotRanges?.[0]?.slotSize ?? 60;

		const slotRanges = (
			Type.isArrayFilled(this.resource.slotRanges)
				? this.resource.slotRanges
				: this.companyScheduleSlots
		);

		this.updateSlotRanges(slotRanges);
	},
	data(): Object
	{
		return {
			selectedSlotLength: 60,
			initialTimezone: '',
		};
	},
	methods: {
		...mapActions([
			'updateResource',
			'setInvalidResourceName',
			'setInvalidResourceType',
		]),
		...mapMutations([
			'setGlobalSchedule',
		]),
		updateResourceName(name): void
		{
			this.updateResource({ name });
			if (name)
			{
				this.setInvalidResourceName(false);
			}
		},
		updateResourceType(typeId): void
		{
			this.updateResource({ typeId });
			if (typeId)
			{
				this.setInvalidResourceType(false);
			}
		},
		updateSlotRanges(slotRanges: SlotRange[]): void
		{
			this.updateResource({ slotRanges: this.updateSlotDataForAllRanges(slotRanges) });
		},
		updateSlotLength(value): void
		{
			this.selectedSlotLength = value;

			if (this.resource.slotRanges.length === 0)
			{
				return;
			}

			this.updateSlotRanges(this.resource.slotRanges);
		},
		updateSlotDataForAllRanges(inputSlotRanges: SlotRange[]): SlotRange[]
		{
			const slotRanges = inputSlotRanges || this.resource.slotRanges;

			return slotRanges.map((slotRange: SlotRange) => {
				return {
					...slotRange,
					slotSize: this.selectedSlotLength,
					timezone: this.timezone,
				};
			});
		},
		updateGlobalSchedule(checked: boolean): void
		{
			this.setGlobalSchedule(checked);
		},
		async fetchData(): void
		{
			await resourceCreationWizardService.fetchData();

			if (!this.isEditForm)
			{
				this.updateSlotRanges(this.companyScheduleSlots);
			}
		},
		getInitialSlotTimeZone(): string
		{
			const slotRanges = this.isEditForm ? this.resource.slotRanges : this.companyScheduleSlots;

			return slotRanges[0]?.timezone;
		},
	},
	computed: {
		...mapGetters({
			timezone: `${Model.Interface}/timezone`,
		}),
		...mapResourceGetters({
			resource: 'getResource',
			companyScheduleSlots: 'getCompanyScheduleSlots',
			isCompanyScheduleAccess: 'isCompanyScheduleAccess',
			isGlobalSchedule: 'isGlobalSchedule',
		}),
		resourceName(): string
		{
			return this.resource.name;
		},
		resourceType(): Object
		{
			const resourceType: ?ResourceTypeModel = this.$store
				.getters[`${Model.ResourceTypes}/getById`](this.resource.typeId)
			;

			return {
				typeId: this.resource.typeId,
				typeName: resourceType?.name,
			};
		},
		slotRanges(): SlotRange[]
		{
			return this.resource.slotRanges;
		},
		defaultSlotRange(): SlotRange
		{
			return this.companyScheduleSlots[0];
		},
		slotSize(): number
		{
			const slotRange: SlotRange = this.resource.slotRanges?.[0];

			return slotRange?.slotSize ?? 60;
		},
		isMain: {
			get(): boolean
			{
				return this.resource.isMain;
			},
			set(isMain: boolean)
			{
				this.updateResource({ isMain });
			},
		},
		isEditForm(): boolean
		{
			return this.resource.id !== null;
		},
	},
	template: `
		<div class="resource-settings-card">
			<BaseFields
				data-id="brcw-resource-settings-base"
				:initialResourceName="resourceName"
				:initialResourceType="resourceType"
				@nameUpdate="updateResourceName"
				@typeUpdate="updateResourceType"
			/>
			<ScheduleTypes
				data-id="brcw-resource-settings-schedule-types"
				v-model="isMain"
			/>
			<WorkTime
				data-id="brcw-resource-settings-work-time"
				:initialSlotRanges="slotRanges"
				:defaultSlotRange="defaultSlotRange"
				:isGlobalSchedule="isGlobalSchedule"
				:initialTimezone="initialTimezone"
				:currentTimezone="timezone"
				:isCompanyScheduleAccess="isCompanyScheduleAccess"
				@update="updateSlotRanges"
				@updateGlobalSchedule="updateGlobalSchedule"
				@getGlobalSchedule="fetchData"
			/>
			<SlotLength
				data-id="brcw-resource-settings-slot-length"
				:initialSelectedValue="slotSize"
				@select="updateSlotLength"
			/>
		</div>
	`,
};
