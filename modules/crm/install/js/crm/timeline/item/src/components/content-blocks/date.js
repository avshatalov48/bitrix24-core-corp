import Text from "./text";
import { DatetimeConverter } from "crm.timeline.tools";
import {Text as CoreTextHelper} from "main.core";

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
	},
	extends: Text,
	computed: {
		encodedText() {
			const dateInUserTimezone = (DatetimeConverter.createFromServerTimestamp(this.value)).toUserTime();

			return CoreTextHelper.encode(
				this.format
					? dateInUserTimezone.toFormatString(this.format)
					: (
						this.withTime
							? dateInUserTimezone.toDatetimeString({delimiter: ', '})
							: dateInUserTimezone.toDateString()
					)
			);
		},
	},
	template: Text.template
}
