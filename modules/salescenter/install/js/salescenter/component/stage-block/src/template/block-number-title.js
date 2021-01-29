import {Title} 			from "../title";
import {CounterNumber} 	from "../counter-number";

const BlockNumberTitle = {
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
		'block-title'			: 	Title,
		'block-counter-number'	: 	CounterNumber
	},
	template: `
		<div>
			<block-counter-number :value="counter" :checked="checked" />
			<block-title>
				<template v-slot:default>
					<slot name="block-title-title"></slot>
				</template>
			</block-title>
			<slot name="block-container"></slot>
		</div>
	`
};

export {
	BlockNumberTitle
}