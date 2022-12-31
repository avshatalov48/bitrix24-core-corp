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
	},
	extends: Text,
	computed: {
		encodedText() {
			const dateInUserTimezone = (DatetimeConverter.createFromServerTimestamp(this.value)).toUserTime();

			return CoreTextHelper.encode(this.withTime
				? dateInUserTimezone.toDatetimeString({delimiter: ', '})
				: dateInUserTimezone.toDateString()
			);
		},
	},
	template: Text.template
}
