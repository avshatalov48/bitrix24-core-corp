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
		addIcon: String,
		addIconType: String,
	},
	computed: {
		addIconClassname() {
			return [
				'crm-timeline__card-logo_add-icon',
				`--type-${this.addIconType}`,
				`--icon-${this.addIcon}`
			]
		},
	},
	template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<CalendarIcon :timestamp="timestamp" />
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
		</div>
	`
});