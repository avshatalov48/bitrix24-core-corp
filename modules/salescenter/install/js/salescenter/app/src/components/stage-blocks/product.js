import { Block } from 'salescenter.component.stage-block';
import Product from '../../product';
import { StageMixin } from './stage-mixin';

export default {
	props: {
		status: {
			type: String,
			required: true,
		},
		counter: {
			type: Number,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		hintTitle: {
			type: String,
			required: true,
		},
		additionalContainerClasses: {
			type: Object,
			required: false,
			default()
			{
				return {};
			},
		},
	},
	mixins: [StageMixin],
	components:
		{
			'stage-block-item': Block,
			product: Product,
		},
	methods:
		{
			onItemHint(e)
			{
				BX.Salescenter.Manager.openHowToSell(e);
			},
			onProductFormModeChange(event)
			{
				this.$emit('on-product-form-mode-change');
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
				};
			},
			containerClasses()
			{
				return {
					...this.statusClassMixin,
					...this.additionalContainerClasses,
				};
			},
		},
	template: `
		<stage-block-item
			@on-item-hint.stop.prevent="onItemHint"
			:config="configForBlock"
			:class="containerClasses"
		>
			<template v-slot:block-title-title>{{ title }}</template>
			<template v-slot:block-hint-title>{{ hintTitle }}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<div class="salescenter-app-payment-by-sms-item-container-payment">
						<product
						@on-product-form-mode-change="onProductFormModeChange"
						/>
					</div>
				</div>
			</template>
		</stage-block-item>
	`,
};
