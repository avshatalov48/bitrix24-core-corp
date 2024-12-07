import { DatetimeConverter } from 'crm.timeline.tools';
import { Text as CoreTextHelper, Type } from 'main.core';
import Text from './text';

export default {
	props: {
		withTime: {
			type: Boolean,
			required: false,
			default: true,
		},
		format: {
			type: String,
			required: false,
			default: null,
		},
		duration: {
			type: Number,
			required: false,
			default: null,
		},
	},
	extends: Text,
	methods: {
		getFormattedDate(): string
		{
			const datetimeConverter = this.getDatetimeConverter();

			if (this.format)
			{
				return datetimeConverter.toFormatString(this.format);
			}

			const options = {
				delimiter: ', ',
				withDayOfWeek: true,
				withFullMonth: true,
			};

			return this.withTime
				? datetimeConverter.toDatetimeString(options)
				: datetimeConverter.toDateString()
			;
		},
		getDatetimeConverter(): DatetimeConverter
		{
			return (DatetimeConverter.createFromServerTimestamp(this.value)).toUserTime();
		},
		getDatetimeConverterWithDuration(): DatetimeConverter
		{
			return (DatetimeConverter.createFromServerTimestamp(this.value + this.duration)).toUserTime();
		},
	},
	computed: {
		encodedText(): string
		{
			const formattedDate = this.getFormattedDate();

			if (!Type.isNumber(this.duration))
			{
				return CoreTextHelper.encode(formattedDate);
			}

			const converterWithDuration = this.getDatetimeConverterWithDuration();

			return CoreTextHelper.encode(`${formattedDate}-${converterWithDuration.toTimeString()}`);
		},
	},
	template: Text.template,
};
