import { Loc } from 'main.core';
import { Block } from 'salescenter.component.stage-block';
import { StageList } from 'salescenter.component.stage-block.automation';
import * as AutomationStage from 'salescenter.automation-stage';
import { StageMixin } from './stage-mixin';
import { MixinTemplatesType } from '../templates-type-mixin';

const Automation = {
	props: {
		status: {
			type: String,
			required: true,
		},
		counter: {
			type: Number,
			required: true,
		},
		stageOnOrderPaid: {
			type: String,
			required: false,
		},
		stageOnDeliveryFinished: {
			type: String,
			required: false,
		},
		items: {
			type: Array,
			required: true,
		},
		initialCollapseState: {
			type: Boolean,
			required: true,
		},
		isDeliveryStageVisible: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	mixins: [StageMixin, MixinTemplatesType],
	data()
	{
		return {
			paymentStages: [],
			shipmentStages: [],
		};
	},
	components:
		{
			'stage-block-item':	Block,
			'stage-item-list':	StageList,
		},
	methods:
		{
			saveCollapsedOption(option)
			{
				this.$emit('on-save-collapsed-option', 'automation', option);
			},

			updatePaymentStage(e)
			{
				const newStageId = e.data;
				this.paymentStages.forEach((stage) => {
					stage.selected = stage.id === newStageId;
				});

				this.$root.$app.stageOnOrderPaid = e.data;
			},

			updateShipmentStage(e)
			{
				const newStageId = e.data;
				this.shipmentStages.forEach((stage) => {
					stage.selected = stage.id === newStageId;
				});

				this.$root.$app.stageOnDeliveryFinished = e.data;
			},
			initStages(stages, currentValue)
			{
				Object.values(this.items).forEach((options) => {
					options.selected = (
						(!currentValue && !options.hasOwnProperty('id'))
						|| (options.id === currentValue)
					);
					stages.push(AutomationStage.Factory.create(options));
				});
			},
		},
	computed:
		{
			configForBlock()
			{
				return {
					counter: this.counter,
					checked: this.counterCheckedMixin,
					collapsible: true,
					initialCollapseState: this.initialCollapseState,
					titleName: this.selectedStage.name,
				};
			},
			selectedStage()
			{
				const stages = this.isPayment ? this.paymentStages : this.shipmentStages;

				return stages.find((stage) => {
					return stage.selected;
				});
			},
			isPayment()
			{
				return (
					this.$root.$app.options.mode === 'payment_delivery'
					|| this.$root.$app.options.mode === 'payment'
					|| this.$root.$app.options.mode === 'terminal_payment'
				);
			},
			isHideDeliveryStage()
			{
				return !this.isDeliveryStageVisible;
			},
		},
	created()
	{
		if (this.isPayment)
		{
			this.initStages(this.paymentStages, this.stageOnOrderPaid);
		}

		this.initStages(this.shipmentStages, this.stageOnDeliveryFinished);
	},
	template: `
		<stage-block-item
			:config="configForBlock"
			:class="statusClassMixin"
			@on-adjust-collapsed="saveCollapsedOption"
		>
			<template v-slot:block-title-title>${Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<div v-if="isPayment">
						<stage-item-list
							v-on:on-choose-select-option="updatePaymentStage($event)"
							:stages="paymentStages"
							:editable="editable"
						>
							<template v-slot:stage-list-text>${Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT')}</template>
						</stage-item-list>
					</div>

					<div v-if="!isHideDeliveryStage">
						<stage-item-list
							v-on:on-choose-select-option="updateShipmentStage($event)"
							:stages="shipmentStages"
							:editable="editable"
						>
							<template v-slot:stage-list-text>${Loc.getMessage('SALESCENTER_AUTOMATION_DELIVERY_FINISHED')}</template>
						</stage-item-list>
					</div>
				</div>
			</template>
		</stage-block-item>
	`,
};
export {
	Automation,
};
