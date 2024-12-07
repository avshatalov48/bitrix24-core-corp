import { Text, Type } from 'main.core';
import { BitrixVue } from 'ui.vue3';
import { CalendarIcon } from './calendar-icon';

import { Logo } from './logo';

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
		calendarEventId: {
			type: Number,
			required: false,
			default: null,
		},
		backgroundColor: {
			type: String,
			required: false,
			default: null,
		},
	},

	computed: {
		addIconClassname(): string[]
		{
			return [
				'crm-timeline__card-logo_add-icon',
				`--type-${this.addIconType}`,
				`--icon-${this.addIcon}`,
			];
		},
		logoStyle(): Object
		{
			if (Type.isStringFilled(this.backgroundColor))
			{
				return {
					'--crm-timeline__logo-background': Text.encode(this.backgroundColor),
				};
			}

			return {};
		},
	},

	template: `
		<div 
			:class="className"
			:style="logoStyle"
			@click="executeAction"
		>
			<div class="crm-timeline__card-logo_content">
				<CalendarIcon :timestamp="timestamp" :calendar-event-id="calendarEventId" />
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
		</div>
	`,
});
