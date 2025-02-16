import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { Resource } from './resource/resource';
import { AddResourceButton } from './add-resource-button/add-resource-button';
import { SelectResources } from './select-resources/select-resources';
import './header.css';

export const Header = {
	computed: mapGetters({
		resourcesIds: `${Model.Interface}/resourcesIds`,
		scroll: `${Model.Interface}/scroll`,
		isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
	}),
	watch: {
		scroll(value): void
		{
			this.$refs.inner.scrollLeft = value;
		},
	},
	components: {
		Resource,
		AddResourceButton,
		SelectResources,
	},
	template: `
		<div class="booking-booking-header">
			<SelectResources/>
			<div
				class="booking-booking-header-inner"
				ref="inner"
				@scroll="$store.dispatch('interface/setScroll', $refs.inner.scrollLeft)"
			>
				<TransitionGroup name="booking-transition-resource">
					<template v-for="resourceId of resourcesIds" :key="resourceId">
						<Resource :resourceId="resourceId"/>
					</template>
				</TransitionGroup>
				<AddResourceButton v-if="!isEditingBookingMode"/>
			</div>
		</div>
	`,
};
