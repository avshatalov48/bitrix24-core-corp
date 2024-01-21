import {Loc} from 'main.core';
import {Util} from 'calendar.util';

export default {
	props: {
		isEditable: Boolean,
		rule: {
			from: Number,
			to: Number,
			weekdays: Array,
			slotSize: Number,
		},
		durationFormatted: String,
		weekdaysFormatted: String,
	},
	computed: {
		itemClassName(): string
		{
			return 'crm-timeline-calendar-sharing-item-' + (this.isEditable ? 'editable' : 'non-editable');
		},
	},
	methods: {
		createItemText()
		{
			return Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_V2', {
				'#WEEKDAYS#': this.weekdaysFormatted,
				'#FROM_TIME#': this.formatMinutes(this.rule.from),
				'#TO_TIME#': this.formatMinutes(this.rule.to),
				'#DURATION#': this.durationFormatted,
			});
		},
		formatMinutes(minutes)
		{
			const date = new Date(Util.parseDate('01.01.2000').getTime() + minutes * 60 * 1000);
			return Util.formatTime(date);
		},
	},
	template: `
		<div :class="[itemClassName]">
			<span :title="createItemText()">{{createItemText()}}</span>
		</div>
	`,
}