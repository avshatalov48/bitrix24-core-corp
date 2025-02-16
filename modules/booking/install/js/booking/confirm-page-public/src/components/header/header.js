import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import { Mixin } from '../mixin';

import 'ui.icon-set.main';
import 'ui.icon-set.actions';
import './header.css';

export const Header = {
	name: 'Header',
	mixins: [Mixin],
	components: {
		Icon,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
		company: {
			type: String,
			required: true,
		},
		context: {
			type: String,
			required: true,
		},
	},
	data(): Object
	{
		return {};
	},
	computed: {
		iconColor(): string
		{
			if (this.context === 'delayed.pub.page')
			{
				return 'var(--ui-color-palette-gray-60)';
			}

			return this.isBookingCanceled
				? 'var(--ui-color-palette-red-60)'
				: 'var(--ui-color-palette-green-60)'
			;
		},
		iconSize(): number
		{
			return 45;
		},
		iconName(): string
		{
			if (this.context === 'delayed.pub.page')
			{
				return IconSet.HOURGLASS_SANDGLASS;
			}

			return this.isBookingCanceled
				? IconSet.CROSS_CIRCLE_70
				: IconSet.CIRCLE_CHECK
			;
		},
		titleClass(): string
		{
			if (this.context === 'delayed.pub.page')
			{
				return '--delayed';
			}

			return this.isBookingCanceled
				? '--canceled'
				: ''
			;
		},
		title(): string
		{
			if (this.context === 'delayed.pub.page')
			{
				return this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CONFIRMATION_WAITING');
			}

			return this.isBookingCanceled
				? this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CANCELED')
				: this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CONFIRMED')
			;
		},
	},
	template: `
		<div class="confirm-page-header">
			<div class="confirm-page-header-status">
				<div :class="['confirm-page-header-status-icon', titleClass]">
					<Icon :name="iconName" :size="iconSize" :color="iconColor" />
				</div>
				<div :class="['confirm-page-header-status-title', titleClass]">{{ title }}</div>
			</div>
			<div class="confirm-page-header-company">
				<div class="confirm-page-header-company-title">{{ company }}</div>
			</div>
		</div>
	`,
};
