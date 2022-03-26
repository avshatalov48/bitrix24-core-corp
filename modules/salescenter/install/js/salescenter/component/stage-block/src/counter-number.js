import {Counter} from "./counter";

const CounterNumber = {
	props: {
		value: {
			type: Number,
			required: true
		},
		checked: {
			type: Boolean,
			required: true
		}
	},
	components:
		{
			'block-counter'	:	Counter
		},
	computed:
	{
		counterClass()
		{
			return {
				'salescenter-app-payment-by-sms-item-counter-number-checker': this.checked
			}
		}
	},
	template: `
		<block-counter>
			<template v-slot:block-counter-number>
				<div :class="counterClass"></div>
				<div class="salescenter-app-payment-by-sms-item-counter-number-text">{{value}}</div>
			</template>
		</block-counter>
	`
};

export {
	CounterNumber
}