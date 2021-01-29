import {Loc} 						from 'main.core';
import {BlockNumberTitle as Block} from 'salescenter.component.stage-block';
import "../../../bx-salescenter-app-add-payment";
import {StageMixin} from "./stage-mixin";

const Product = {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: String,
			required: true
		}
	},
	mixins:[StageMixin],
	components:
	{
		'stage-block-item'	:	Block
	},
	template: `
		<stage-block-item
			:counter="counter"
			:class="statusClassMixin"
			:checked="counterCheckedMixin"
		>
			<template v-slot:block-title-title>${Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<div class="salescenter-app-payment-by-sms-item-container-payment">
						<bx-salescenter-app-add-payment/>
					</div>
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	Product
}