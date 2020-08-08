import {Vuex} from 'ui.vue.vuex';
import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import DeliverySelector from 'salescenter.deliveryselector';

export default {
	props: {
		config: {type: Object, required: true}
	},
	components: {
		'delivery-selector': DeliverySelector,
	},
	methods: {
		onChange(payload)
		{
			this.$store.dispatch('orderCreation/setDelivery', payload.deliveryPrice);

			this.$store.dispatch(
				'orderCreation/setDeliveryId',
				payload.deliveryServiceId
			);
			this.$store.dispatch(
				'orderCreation/setPropertyValues',
				payload.relatedPropsValues
			);
			this.$store.dispatch(
				'orderCreation/setDeliveryExtraServicesValues',
				payload.relatedServicesValues
			);
			this.$store.dispatch(
				'orderCreation/setExpectedDelivery',
				payload.estimatedDeliveryPrice
			);
			this.$store.dispatch(
				'orderCreation/setDeliveryResponsibleId',
				payload.responsibleUser ? payload.responsibleUser.id : null
			);

		},
		onSettingsChanged()
		{
			this.$emit('delivery-settings-changed');
		}
	},
	created()
	{
		this.$store.dispatch('orderCreation/setPersonTypeId', this.config.personTypeId);
	},
	computed: {
		localize()
		{
			return Vue.getFilteredPhrases('SALESCENTER_');
		},
		sumTitle()
		{
			return Loc.getMessage('SALESCENTER_PRODUCT_PRODUCTS_PRICE');
		},
		productsPriceFormatted()
		{
			return this.order.total.result;
		},
		productsPrice()
		{
			return this.order.total.resultNumeric;
		},
		delivery()
		{
			return this.order.delivery;
		},
		deliveryFormatted()
		{
			if (this.isDeliveryCalculated)
			{
				return BX.Currency.currencyFormat(this.delivery, this.config.currency, false);
			}

		},
		total()
		{
			if (this.productsPrice === null || this.delivery === null)
			{
				return null;
			}

			return this.productsPrice + this.delivery;
		},
		totalFormatted()
		{
			return BX.Currency.currencyFormat(this.total, this.config.currency, false);
		},
		isDeliveryCalculated()
		{
			return (this.order.delivery !== null);
		},

		actionData()
		{
			return {
				basketItems: this.config.basket,
				options: {
					sessionId: this.config.sessionId,
					ownerTypeId: this.config.ownerTypeId,
					ownerId: this.config.ownerId,
				},
			};
		},
		...Vuex.mapState({
			order: state => state.orderCreation,
		}),
	},
	template: `
		<delivery-selector
			:editable="this.config.editable"
			:init-is-calculated="config.isExistingItem"		
			:init-estimated-delivery-price="config.expectedDeliveryPrice"		
			:init-entered-delivery-price="config.deliveryPrice"
			:init-delivery-service-id="config.deliveryServiceId"
			:init-related-services-values="config.relatedServicesValues"
			:init-related-props-values="config.relatedPropsValues"
			:init-responsible-id="config.responsibleId"
			:person-type-id="config.personTypeId"
			:action="'salescenter.api.order.refreshDelivery'"
			:action-data="actionData"
			:external-sum="productsPrice"
			:external-sum-label="sumTitle"
			:currency="config.currency"
			:currency-symbol="config.currencySymbol"
			@change="onChange"
			@settings-changed="onSettingsChanged"
		></delivery-selector>
	`
};
