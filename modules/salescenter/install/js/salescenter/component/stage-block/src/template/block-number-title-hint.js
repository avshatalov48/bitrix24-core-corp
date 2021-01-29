import {Hint} 			from "../hint";
import {Title} 			from "../title";
import {CounterNumber} 	from "../counter-number";

const BlockNumberTitleHint = {
	props: {
		counter: {
			type: String,
			required: true
		},
		checked: {
			type: Boolean,
			required: true
		}
	},
	components: {
		'block-hint'			: 	Hint,
		'block-title'			: 	Title,
		'block-counter-number'	: 	CounterNumber
	},
	methods:
		{
			onHint(e)
			{
				this.$emit('on-item-hint', e);
			}
		},
	template: `
		<div>
			<block-counter-number :value="counter" :checked="checked"/>
			<block-title>
				<template v-slot:default>
					<slot name="block-title-title"></slot>
				</template>
				<template v-slot:item-hint>
					<block-hint v-on:on-hint="onHint">
						<template v-slot:default>
							<slot name="block-hint-title"></slot>
						</template>
					</block-hint>
				</template>
			</block-title>
			<slot name="block-container"></slot>
		</div>
	`
};

export {
	BlockNumberTitleHint
}