import {Loc} 						from 'main.core';
import {Block} from 'salescenter.component.stage-block';
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
	methods:
	{
		onItemHint(e)
		{
			BX.Salescenter.Manager.openHowToSell(e);
		},
	},
	computed:
		{
			configForBlock()
			{
				return {
					counter: this.counter,
					checked: this.counterCheckedMixin,
					showHint: true,
				}
			}
		},
	template: `
		<stage-block-item
			@on-item-hint.stop.prevent="onItemHint"
			:config="configForBlock"
			:class="statusClassMixin"
		>
			<template v-slot:block-title-title>					
				${Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT')}	
			</template>
			<template v-slot:block-hint-title>${Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT')}</template>
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