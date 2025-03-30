import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { expandOffHours } from '../../../../lib/expand-off-hours/expand-off-hours';
import './off-hours.css';

export const OffHours = {
	props: {
		bottom: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		...mapGetters({
			offHoursHover: 'interface/offHoursHover',
			offHoursExpanded: 'interface/offHoursExpanded',
			fromHour: 'interface/fromHour',
			toHour: 'interface/toHour',
		}),
		fromFormatted(): string
		{
			if (this.bottom)
			{
				return this.formatHour(this.toHour);
			}

			return this.formatHour(0);
		},
		toFormatted(): string
		{
			if (this.bottom)
			{
				return this.formatHour(24);
			}

			return this.formatHour(this.fromHour);
		},
	},
	methods: {
		formatHour(hour: number): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
			const timestamp = new Date().setHours(hour, 0) / 1000;

			return DateTimeFormat.format(timeFormat, timestamp);
		},
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

			void this.$store.dispatch('interface/setOffHoursExpanded', !this.offHoursExpanded);
		},
	},
	template: `
		<div
			class="booking-booking-off-hours"
			:class="{'--hover': offHoursHover, '--bottom': bottom, '--top': !bottom}"
			@click="animateOffHours({ keepScroll: bottom })"
			@mouseenter="$store.dispatch('interface/setOffHoursHover', true)"
			@mouseleave="$store.dispatch('interface/setOffHoursHover', false)"
		>
			<div class="booking-booking-off-hours-border"></div>
			<span>{{ fromFormatted }}</span>
			<span>{{ toFormatted }}</span>
		</div>
	`,
};
