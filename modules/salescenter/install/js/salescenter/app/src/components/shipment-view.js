import 'ui.fonts.ruble';
import 'currency';
import {ajax, Loc} from 'main.core';

export default {
	props: {
		id: {
			type: Number,
			required: true,
		},
		productsPrice: {
			type: Number,
			required: true,
		},
	},
	data()
	{
		return {
			shipment: {
				priceDelivery: null,
				basePriceDelivery: null,
				currency: null,
				deliveryService: {
					name: null,
					logo: null,
					parent: {
						name: null,
						logo: null,
					},
				},
				extraServices: []
			}
		};
	},
	created()
	{
		ajax.runAction(
			'salescenter.deliveryselector.getShipmentData',
			{data: {
					id: this.id
				}}
		).then((result) => {
			this.shipment = result.data.shipment;
		});
	},
	methods: {
		getFormattedPrice(price)
		{
			return BX.Currency.currencyFormat(price, this.currency, true);
		}
	},
	computed: {
		hasParent()
		{
			return (
				this.shipment.hasOwnProperty('deliveryService')
				&& this.shipment.deliveryService.hasOwnProperty('parent')
				&& this.shipment.deliveryService.parent
			);
		},
		deliveryServiceLogo()
		{
			return this.shipment.deliveryService.logo ? this.shipment.deliveryService.logo : null;
		},
		deliveryServiceProfileLogo()
		{
			return (this.hasParent && this.shipment.deliveryService.parent.logo)
				? this.shipment.deliveryService.parent.logo
				: null;
		},
		paymentPrice()
		{
			return this.productsPrice + this.priceDelivery;
		},
		deliveryServiceName()
		{
			return this.hasParent ? this.shipment.deliveryService.parent.name : this.shipment.deliveryService.name;
		},
		deliveryServiceProfileName()
		{
			return this.hasParent ? this.shipment.deliveryService.name : null;
		},
		basePriceDelivery()
		{
			return this.shipment ? this.shipment.basePriceDelivery : null;
		},
		priceDelivery()
		{
			return this.shipment ? this.shipment.priceDelivery : null;
		},
		currency()
		{
			return this.shipment ? this.shipment.currency : null;
		},
		extraServices()
		{
			return this.shipment.extraServices ? this.shipment.extraServices : [];
		},
		isExtraServicesVisible()
		{
			return this.extraServices.length > 0;
		},
		basePriceDeliveryFormatted()
		{
			return this.getFormattedPrice(this.basePriceDelivery);
		},
		priceDeliveryFormatted()
		{
			return this.getFormattedPrice(this.priceDelivery);
		},
		productsPriceFormatted()
		{
			return this.getFormattedPrice(this.productsPrice);
		},
		paymentPriceFormatted()
		{
			return this.getFormattedPrice(this.paymentPrice);
		},
	},
	template: `
		<div style="width: 100%;" xmlns="http://www.w3.org/1999/html">
			<div class="salescenter-delivery-selector-head">
				<div
					v-if="hasParent && deliveryServiceLogo"
					:style="{ backgroundImage: 'url(' + deliveryServiceLogo + ')' }"
					class="salescenter-delivery-selector-logo"
				>
				</div>
				<div class="salescenter-delivery-selector-info">
					<div
						v-if="deliveryServiceProfileLogo"
						:style="{ backgroundImage: 'url(' + deliveryServiceProfileLogo + ')' }"
						class="salescenter-delivery-selector-logo"
					>
					</div>
					<div class="salescenter-delivery-selector-content">
						<div class="salescenter-delivery-selector-text-light">{{deliveryServiceName}}</div>
						<div
							v-if="deliveryServiceProfileName"
							class="salescenter-delivery-selector-text-dark"
						>
							{{deliveryServiceProfileName}}
						</div>
					</div>
				</div>
			</div>
			<div v-if="isExtraServicesVisible" class="salescenter-delivery-selector-main">
				<div class="salescenter-delivery-selector-text-light">
					${Loc.getMessage('SALESCENTER_SHIPMENT_EXTRA_SERVICES')}:
				</div>
				<ul class="salescenter-delivery-selector-list">
					<li
						v-for="extraService in extraServices"
						class="salescenter-delivery-selector-list-item salescenter-delivery-selector-text-dark"
					>
						{{extraService.name}}: {{extraService.value}} 
					</li>
				</ul>
			</div>
			<div class="salescenter-delivery-selector-bottom salescenter-delivery-selector-text-dark">
				${Loc.getMessage('SALESCENTER_SHIPMENT_DELIVERY_PRICE_RECEIVED')}:
				<span v-html="basePriceDeliveryFormatted"></span>
			</div>
			<div class="salescenter-delivery-selector-line"></div>
			<div class="catalog-pf-result-wrapper">
				<table class="catalog-pf-result">
					<tr>
						<td>
							<span class="catalog-pf-text">
								${Loc.getMessage('SALESCENTER_PRODUCT_PRODUCTS_PRICE')}:
							</span>
						</td> 
						<td>
							<span v-html="productsPriceFormatted" class="catalog-pf-text"></span> 
						</td>
					</tr>
					<tr>
						<td class="catalog-pf-result-padding-bottom">
							<span class="catalog-pf-text catalog-pf-text--tax">
								${Loc.getMessage('SALESCENTER_SHIPMENT_PRODUCT_BLOCK_DELIVERY_PRICE')}: 
							</span>
						</td> 
						<td class="catalog-pf-result-padding-bottom"> 
							<span class="catalog-pf-text catalog-pf-text--tax" v-html="priceDeliveryFormatted"></span>
						</td>
					</tr> 
					<tr>
						<td class="catalog-pf-result-padding">
							<span class="catalog-pf-text catalog-pf-text--total catalog-pf-text--border">
								${Loc.getMessage('SALESCENTER_PRODUCT_TOTAL_RESULT')}: 
							</span>
						</td> 
						<td class="catalog-pf-result-padding">
							<span v-html="paymentPriceFormatted" class="catalog-pf-text catalog-pf-text--total"></span> 
						</td>
					</tr>
				</table>
			</div>
		</div>
	`
};
