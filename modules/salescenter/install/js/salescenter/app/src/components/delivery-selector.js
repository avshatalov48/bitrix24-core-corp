import {Vuex} from 'ui.vue.vuex';
import {Vue} from 'ui.vue';
import {ajax, Loc} from 'main.core';
import DeliverySelector from 'salescenter.deliveryselector';

export default {
	props: {
		config: {type: Object, required: true}
	},
	components: {
		'delivery-selector': DeliverySelector,
	},
	data()
	{
		return {
			availableServices: [],
		};
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

			this.$emit('change', payload);
		},
		onAddressFromChanged()
		{
			this.refreshAvailableServices();
		},
		onDeliveryServiceChanged()
		{
			this.refreshAvailableServices();
		},
		onSettingsChanged()
		{
			this.$emit('delivery-settings-changed');
		},
		refreshAvailableServices()
		{
			ajax.runAction(
				'salescenter.order.getCompatibleDeliverySystems',
				{
					data: {
						basketItems: this.config.basket ? this.config.basket : [],
						options: {
							sessionId: this.config.sessionId,
							ownerTypeId: this.config.ownerTypeId,
							ownerId: this.config.ownerId,
						},
						deliveryServiceId: this.order.deliveryId,
						shipmentPropValues: this.order.propertyValues,
						deliveryRelatedServiceValues: this.order.deliveryExtraServicesValues,
						deliveryResponsibleId: this.order.deliveryResponsibleId,
					}
				}
			).then((result) => {
				let data = BX.prop.getObject(result, "data", {});
				this.availableServices = data.availableServices ? data.availableServices : {};
			}).catch((result) => {
				this.availableServices = {};
			});
		}
	},
	created()
	{
		this.$store.dispatch('orderCreation/setPersonTypeId', this.config.personTypeId);

		this.refreshAvailableServices();
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
		productsPrice()
		{
			return this.order.total.result;
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
		excludedServiceIds()
		{
			return this.$root.$app.options.mode === 'delivery' ? [this.$root.$app.options.emptyDeliveryServiceId] : [];
		},
		actionData()
		{
			return {
				basketItems: this.config.basket,
				options: {
					orderId : this.$root.$app.orderId,
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
			:available-services="availableServices"
			:excluded-service-ids="excludedServiceIds"				
			:init-entered-delivery-price="config.deliveryPrice"
			:init-delivery-service-id="config.deliveryServiceId"
			:init-related-services-values="config.relatedServicesValues"
			:init-related-props-values="config.relatedPropsValues"
			:init-related-props-options="config.relatedPropsOptions"
			:init-responsible-id="config.responsibleId"
			:person-type-id="config.personTypeId"
			:action="'salescenter.api.order.refreshDelivery'"
			:action-data="actionData"
			:external-sum="productsPrice"
			:external-sum-label="sumTitle"
			:currency="config.currency"
			:currency-symbol="config.currencySymbol"
			@change="onChange"
			@address-from-changed="onAddressFromChanged"
			@delivery-service-changed="onDeliveryServiceChanged"
			@settings-changed="onSettingsChanged"
		></delivery-selector>
	`
};
