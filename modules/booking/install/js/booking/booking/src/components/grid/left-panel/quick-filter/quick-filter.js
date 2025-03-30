import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';
import 'ui.icon-set.actions';

import { Model } from 'booking.const';
import { HelpPopup } from './help-popup/help-popup';
import './quick-filter.css';

export const QuickFilter = {
	props: {
		hour: {
			type: Number,
			required: true,
		},
	},
	data(): Object
	{
		return {
			IconSet,
			isHelpPopupShown: false,
		};
	},
	computed: {
		active(): boolean
		{
			return this.hour in this.$store.getters[`${Model.Interface}/quickFilter`].active;
		},
		hovered(): boolean
		{
			return this.hour in this.$store.getters[`${Model.Interface}/quickFilter`].hovered;
		},
	},
	methods: {
		onClick(): void
		{
			this.closeHelpPopup();

			if (this.active)
			{
				void this.$store.dispatch(`${Model.Interface}/deactivateQuickFilter`, this.hour);
			}
			else
			{
				void this.$store.dispatch(`${Model.Interface}/activateQuickFilter`, this.hour);
			}
		},
		hover(): void
		{
			this.helpPopupTimeout = setTimeout(() => this.showHelpPopup(), 1000);

			void this.$store.dispatch(`${Model.Interface}/hoverQuickFilter`, this.hour);
		},
		flee(): void
		{
			this.closeHelpPopup();

			void this.$store.dispatch(`${Model.Interface}/fleeQuickFilter`, this.hour);
		},
		showHelpPopup(): void
		{
			this.isHelpPopupShown = true;
		},
		closeHelpPopup(): void
		{
			clearTimeout(this.helpPopupTimeout);
			this.isHelpPopupShown = false;
		},
	},
	components: {
		Icon,
		HelpPopup,
	},
	template: `
		<div
			class="booking-booking-quick-filter-container"
			:class="{'--hover': hovered || active, '--active': active}"
		>
			<div
				class="booking-booking-quick-filter"
				@mouseenter="hover"
				@mouseleave="flee"
				@click="onClick"
			>
				<Icon :name="active ? IconSet.CROSS_25 : IconSet.FUNNEL"/>
			</div>
			<HelpPopup
				v-if="isHelpPopupShown"
				:bindElement="$el"
				@close="closeHelpPopup"
			/>
		</div>
	`,
};
