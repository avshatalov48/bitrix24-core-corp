import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { calendar } from 'tasks.v2.lib.calendar';

export const DatePlanDate = {
	components: {
		HoverPill,
	},
	props: {
		dateTs: {
			type: Number,
			required: true,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		dateFormatted(): string
		{
			return calendar.formatDateTime(this.dateTs, { forceYear: true });
		},
	},
	template: `
		<div class="tasks-field-date-plan-date-container">
			<HoverPill :readonly="readonly">
				<div class="tasks-field-date-plan-date">{{ dateFormatted }}</div>
			</HoverPill>
		</div>
	`,
};
