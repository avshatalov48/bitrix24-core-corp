import Text from '../text';
import {Loc} from 'main.core'

export default {
	props: {
		type: {
			type: String,
		},
		timeStart: {
			type: Number,
		},
		timeEnd: {
			type: Number,
		},
		slotLength: {
			type: Number,
		},
		isEditable: {
			type: Boolean,
		}
	},
	components: {
		Text,
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
			return this.formatType(this.type)
				+ ', '
				+ this.formatDateToTime(this.timeStart)
				+ '-'
				+ this.formatDateToTime(this.timeEnd)
				+ ', '
				+ this.formatSlotLength(this.slotLength)
			;
		},
		formatType(type)
		{
			switch (type) {
				case 'work_days':
					return Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_WORK_DAYS');
				default:
					return '';
			}
		},
		formatDateToTime(date)
		{
			return this.formatTimeValue(parseInt(date / 60)) + ':' + this.formatTimeValue(date % 60);
		},
		formatTimeValue(value)
		{
			if (parseInt(value) < 10)
			{
				value = '0' + value;
			}

			return value;
		},
		formatSlotLength(length)
		{
			const hours = parseInt(length / 60);
			const minutes = length % 60;

			let hint = `${minutes} ${Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_MINUTES')}`;
			if (hours > 0)
			{
				hint = `${hours} ${Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_HOUR')}`;
				if (minutes > 0)
				{
					hint += ` ${minutes} ${Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_MINUTES')}`;
				}
			}

			return Loc.getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_LENGTH', {
				'%SLOT_LENGTH%': hint,
			});
		},
	},
	template: `
		<div :class="[itemClassName]">
			<span>{{createItemText()}}</span>
		</div>
	`,
}