import { mapGetters } from 'ui.vue3.vuex';
import { ResourceTypes } from './resource-types/resource-types';
import { Resize } from './resize/resize';
import { Search } from './search/search';
import './dialog-header.css';

export const DialogHeader = {
	emits: ['update:modelValue', 'search', 'startResize', 'endResize', 'selectAll', 'deselectAll'],
	data(): Object
	{
		return {
			selectedTypes: {},
		};
	},
	computed: mapGetters({
		resources: 'resources/get',
	}),
	watch: {
		selectedTypes: {
			handler(): void
			{
				this.$emit('update:modelValue', this.selectedTypes);
			},
			deep: true,
		},
	},
	components: {
		ResourceTypes,
		Resize,
		Search,
	},
	template: `
		<div class="booking-booking-resources-dialog-header" ref="header">
			<ResourceTypes
				ref="resourceTypes"
				v-model="selectedTypes"
			/>
			<Resize
				:getNode="() => this.$refs.resourceTypes.$el"
				@startResize="$emit('startResize')"
				@endResize="$emit('endResize')"
			/>
			<div class="booking-booking-resources-dialog-header-resources">
				<div class="booking-booking-resources-dialog-header-header">
					<div class="booking-booking-resources-dialog-header-title">
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESOURCES') }}
					</div>
					<div
						class="booking-booking-resources-dialog-header-button"
						data-element="booking-resources-dialog-select-all-button"
						@click="$emit('selectAll')"
					>
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_SELECT_ALL') }}
					</div>
					<div
						class="booking-booking-resources-dialog-header-button"
						data-element="booking-resources-dialog-deselect-all-button"
						@click="$emit('deselectAll')"
					>
						{{ loc('BOOKING_BOOKING_RESOURCES_DIALOG_DESELECT_ALL') }}
					</div>
				</div>
				<Search @search="(query) => this.$emit('search', query)"/>
			</div>
		</div>
	`,
};
