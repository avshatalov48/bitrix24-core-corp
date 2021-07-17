import {Block} from 'salescenter.component.stage-block';
import Product from '../../product';
import {StageMixin} from './stage-mixin';

export default {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: String,
			required: true
		},
		title: {
			type: String,
			required: true
		},
		hintTitle: {
			type: String,
			required: true
		},
	},
	mixins:[StageMixin],
	components:
		{
			'stage-block-item':	Block,
			'product':	Product,
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
			<template v-slot:block-title-title>{{title}}</template>
			<template v-slot:block-hint-title>{{hintTitle}}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<div class="salescenter-app-payment-by-sms-item-container-payment">
						<product/>
					</div>
				</div>
			</template>
		</stage-block-item>
	`
}
