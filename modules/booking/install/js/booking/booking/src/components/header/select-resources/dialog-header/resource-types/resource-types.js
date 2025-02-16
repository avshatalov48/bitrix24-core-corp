import { mapGetters } from 'ui.vue3.vuex';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import './resource-types.css';

export const ResourceTypes = {
	emits: ['update:modelValue'],
	data(): Object
	{
		return {
			selectedTypes: {},
		};
	},
	computed: mapGetters({
		resourceTypes: 'resourceTypes/get',
	}),
	methods: {
		selectAll(): void
		{
			Object.keys(this.selectedTypes).forEach((typeId) => {
				this.selectedTypes[typeId] = true;
			});
		},
		deselectAll(): void
		{
			Object.keys(this.selectedTypes).forEach((typeId) => {
				this.selectedTypes[typeId] = false;
			});
		},
	},
	watch: {
		resourceTypes(resourceTypes: ResourceTypeModel[]): void
		{
			resourceTypes.forEach((resourceType: ResourceTypeModel) => {
				this.selectedTypes[resourceType.id] ??= true;
			});
		},
		selectedTypes: {
			handler(): void
			{
				this.$emit('update:modelValue', this.selectedTypes);
			},
			deep: true,
		},
	},
	template: `
		<div class="booking-booking-resources-dialog-header-types">
			<div class="booking-booking-resources-dialog-header-header">
				<div class="booking-booking-resources-dialog-header-title">
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESOURCE_TYPES') }}
				</div>
				<div
					class="booking-booking-resources-dialog-header-button"
					data-element="booking-resources-dialog-select-all-types-button"
					@click="selectAll"
				>
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_SELECT_ALL') }}
				</div>
				<div
					class="booking-booking-resources-dialog-header-button"
					data-element="booking-resources-dialog-deselect-all-types-button"
					@click="deselectAll"
				>
					{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_DESELECT_ALL') }}
				</div>
			</div>
			<div class="booking-booking-resources-dialog-header-items">
				<template v-for="resourceType of resourceTypes" :key="resourceType.id">
					<label
						class="booking-booking-resources-dialog-header-item"
						data-element="booking-resources-dialog-type"
						:data-id="resourceType.id"
						:data-selected="selectedTypes[resourceType.id]"
					>
						<span
							class="booking-booking-resources-dialog-header-item-text"
							data-element="booking-resources-dialog-type-name"
							:data-id="resourceType.id"
						>
							{{ resourceType.name }}
						</span>
						<input type="checkbox" v-model="selectedTypes[resourceType.id]">
					</label>
				</template>
			</div>
		</div>
	`,
};
