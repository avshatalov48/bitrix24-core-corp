import { Loc, Tag } from 'main.core';
import { Block } from 'salescenter.component.stage-block';
import { StageMixin } from '../../stage-blocks/stage-mixin';
import { CurrencyCore } from 'currency.currency-core';

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
	},
	mixins: [StageMixin],
	mounted()
	{
		// temporary fix; see the comment in the product block (product.js) for more details
		const editable = this.$root.$app.options.templateMode !== 'view';
		this.$root.$emit('on-change-editable', editable);
		this.$store.commit('orderCreation/enableSubmit');
	},
	components:
		{
			'stage-block-item': Block,
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
			formattedSum()
			{
				const defaultCurrency = this.$root.$app.options.currencyCode || '';
				const sum = CurrencyCore.currencyFormat(this.$root.$app.options.totals.result, defaultCurrency, false);
				const element = Tag.render`<span class="catalog-pf-text salescenter-amount-block-amount-result-text--total">${sum}</span>`;

				return CurrencyCore.getPriceControl(element, defaultCurrency);
			},
		},
	template: `
		<stage-block-item
			@on-item-hint.stop.prevent="onItemHint"
			:config="configForBlock"
			:class="statusClassMixin"
		>
			<template v-slot:block-title-title>{{ title }}</template>
			<template v-slot:block-hint-title>{{ hintTitle }}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<div class="salescenter-amount-block-stub-wrapper">
						<div class="salescenter-amount-block-stub-icon"></div>
						<div class="salescenter-amount-block-stub-text-wrapper">
							<h3 class="salescenter-amount-block-stub-title">${Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_STUB_TITLE')}</h3>
							<p class="salescenter-amount-block-stub-text">${Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_STUB_TEXT')}</p>
						</div>
					</div>
					<div class="salescenter-amount-block-amount-wrapper">
						<table class="salescenter-amount-block-amount-result">
							<tr>
								<td class="salescenter-amount-block-amount-result-cell">
									<span class="salescenter-amount-block-amount-result-text salescenter-amount-block-amount-result-text--total">${Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_TOTAL')}</span>
								</td>
								<td class="salescenter-amount-block-amount-result-cell">
									<span class="salescenter-amount-block-amount-result-symbol salescenter-amount-block-amount-result-symbol--total" v-html="formattedSum"></span>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</template>
		</stage-block-item>
	`,
};
