import {BitrixVue} from 'ui.vue3';

import {Logo} from './logo';
import {CalendarIcon} from './calendar-icon';

export const LogoCalendar = BitrixVue.cloneComponent(Logo, {
	components: {
		CalendarIcon,
	},
	props: {
		timestamp: {
			type: Number,
			required: false,
			default: 0,
		},
	},
	template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<CalendarIcon :timestamp="timestamp" />
			</div>
		</div>
	`
});