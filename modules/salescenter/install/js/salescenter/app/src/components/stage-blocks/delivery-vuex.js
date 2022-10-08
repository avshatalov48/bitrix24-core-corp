import {Vuex} 							from 'ui.vue.vuex';
import {Loc} 							from 'main.core';
import {Block} 			from 'salescenter.component.stage-block';
import {Uninstalled as TileUnInstalled} from "./tile-collection/uninstalled";
import DeliverySelector					from "../delivery-selector";
import ShipmentView						from "../shipment-view";
import {StageMixin} 					from "./stage-mixin";
import {MixinTemplatesType} 			from "../templates-type-mixin";
import {StatusTypes as Status} from 'salescenter.component.stage-block';

const DeliveryVuex = {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: Number,
			required: true
		},
		tiles: {
			type: Array,
			required: true
		},
		installed: {
			type: Boolean,
			required: true
		},
		isCollapsible: {
			type: Boolean,
			required: true
		},
		initialCollapseState: {
			type: Boolean,
			required: true
		},
	},
	data()
	{
		return {
			selectedDeliveryServiceName: null,
		}
	},
	mixins:[StageMixin, MixinTemplatesType],
	components:
		{
			'stage-block-item'				:	Block,
			'delivery-selector-block'		:	DeliverySelector,
			'shipment-view'					:	ShipmentView,
			'uninstalled-delivery-block'	:	TileUnInstalled,
		},
	computed:
		{
			statusClass()
			{
				return {
					'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false || this.status === Status.disabled
				}
			},
			productsPrice()
			{
				return this.order.total.result;
			},
			shipmentId()
			{
				return this.$root.$app.options.shipmentId;
			},
			configForBlock()
			{
				return {
					counter: this.counter,
					titleName: this.selectedDeliveryServiceName,
					installed: this.installed,
					collapsible: this.isCollapsible,
					checked: this.counterCheckedMixin,
					showHint: false,
					initialCollapseState: this.initialCollapseState,
				}
			},
			config()
			{
				let deliveryServiceId = null;
				if (this.$root.$app.options.hasOwnProperty('shipmentData')
					&& this.$root.$app.options.shipmentData.hasOwnProperty('deliveryServiceId')
				)
				{
					deliveryServiceId = this.$root.$app.options.shipmentData.deliveryServiceId;
				}

				let deliveryPrice = null;
				if (this.$root.$app.options.hasOwnProperty('shipmentData')
					&& this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')
				)
				{
					deliveryPrice = this.$root.$app.options.shipmentData.deliveryPrice;
				}

				let relatedPropsValues = {};
				if (this.$root.$app.options.hasOwnProperty('shipmentData')
					&& this.$root.$app.options.shipmentData.hasOwnProperty('propValues')
				)
				{
					relatedPropsValues = this.$root.$app.options.shipmentData.propValues;
				}

				let relatedServicesValues = {};
				if (this.$root.$app.options.hasOwnProperty('shipmentData')
					&& this.$root.$app.options.shipmentData.hasOwnProperty('extraServicesValues')
					&& !Array.isArray(this.$root.$app.options.shipmentData.extraServicesValues)
				)
				{
					relatedServicesValues = this.$root.$app.options.shipmentData.extraServicesValues;
				}

				let relatedPropsOptions = {};
				if (this.$root.$app.options.hasOwnProperty('deliveryOrderPropOptions')
					&& !Array.isArray(this.$root.$app.options.deliveryOrderPropOptions)
				)
				{
					relatedPropsOptions = this.$root.$app.options.deliveryOrderPropOptions;
				}

				return {
					personTypeId: this.$root.$app.options.personTypeId,
					basket: this.order.basket,
					currencySymbol: this.$root.$app.options.currencySymbol,
					currency: this.order.currency,
					ownerTypeId: this.$root.$app.options.ownerTypeId,
					ownerId: this.$root.$app.options.ownerId,
					sessionId: this.$root.$app.options.sessionId,
					relatedPropsValues,
					relatedPropsOptions,
					relatedServicesValues,
					deliveryServiceId,
					responsibleId: this.$root.$app.options.assignedById,
					deliveryPrice,
				};
			},
			isViewTemplateMode()
			{
				return this.$root.$app.options.templateMode === 'view';
			},

			...Vuex.mapState({
				order: state => state.orderCreation
			})
		},
	methods:{
		setTitleName(state)
		{
			this.selectedDeliveryServiceName = state.deliveryServiceName;
		},
		saveCollapsedOption(option)
		{
			this.$emit('on-save-collapsed-option', 'delivery', option);
		},
	},
	template: `
		<stage-block-item
			:config="configForBlock"
			:class="[statusClassMixin, statusClass]"
			@on-item-hint.stop.prevent="onItemHint"
			@on-adjust-collapsed="saveCollapsedOption"
		>
			<template v-slot:block-title-title>${Loc.getMessage('SALESCENTER_DELIVERY_BLOCK_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<template v-if="!installed">
						<uninstalled-delivery-block :tiles="tiles" 
								v-on:on-tile-slider-close="onSliderClose"/>
					</template>
					<template v-else>
						<div class="salescenter-app-payment-by-sms-item-container-select">
							<shipment-view
								v-if="isViewTemplateMode"
								:id="shipmentId"
								:productsPrice="productsPrice"
							>
							</shipment-view>
							<delivery-selector-block v-else
								:config="config" 
								@delivery-settings-changed="onSliderClose"
								@change="setTitleName" 
							/>
						</div>
					</template>
				</div>
			</template>
		</stage-block-item>
	`
};

export {
	DeliveryVuex
}