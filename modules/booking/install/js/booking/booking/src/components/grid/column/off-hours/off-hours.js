import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { expandOffHours } from '../../../../lib/expand-off-hours/expand-off-hours';
import './off-hours.css';

export const OffHours = {
	props: {
		bottom: {
			type: Boolean,
			default: false,
		},
	},
	computed: mapGetters({
		offHoursHover: `${Model.Interface}/offHoursHover`,
		offHoursExpanded: `${Model.Interface}/offHoursExpanded`,
	}),
	methods: {
		animateOffHours({ keepScroll }): void
		{
			if (this.offHoursExpanded)
			{
				expandOffHours.collapse();
			}
			else
			{
				expandOffHours.expand({ keepScroll });
			}

			void this.$store.dispatch(`${Model.Interface}/setOffHoursExpanded`, !this.offHoursExpanded);
		},
	},
	template: `
		<div class="booking-booking-grid-padding">
			<div
				class="booking-booking-column-off-hours"
				:class="{'--bottom': bottom, '--hover': offHoursHover}"
				@click="animateOffHours({ keepScroll: bottom })"
				@mouseenter="$store.dispatch('interface/setOffHoursHover', true)"
				@mouseleave="$store.dispatch('interface/setOffHoursHover', false)"
			></div>
		</div>
	`,
};
