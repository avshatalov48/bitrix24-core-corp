import { DatetimeConverter } from 'crm.timeline.tools';

export const FormatDate = {
	name: 'FormatDate',
	props: {
		timestamp: {
			type: Number,
			required: true,
			default: 0,
		},

		datePlaceholder: {
			type: String,
			required: false,
			default: '',
		},

		useShortTimeFormat: {
			type: Boolean,
			required: false,
			default: false,
		},

		class: {
			type: [Array, Object, String],
			required: false,
			default: '',
		}
	},
	computed: {
		formattedDate() {
			if (!this.timestamp)
			{
				return this.datePlaceholder;
			}
			const converter = DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime();

			return this.useShortTimeFormat ? converter.toTimeString() : converter.toDatetimeString({delimiter: ', '});
		},
	},

	template: `
		<div :class="$props.class">{{ formattedDate }}</div>
	`
}