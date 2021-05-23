import {Vue} from 'ui.vue';
import {VuexBuilderModel} from 'ui.vue.vuex';
import "currency";

export class OrderCreationModel extends VuexBuilderModel
{
	/**
	 * @inheritDoc
	 */
	getName()
	{
		return 'orderCreation';
	}

	getState()
	{
		return {
			currency: '',
			processingId: null,
			showPaySystemSettingBanner: false,
			selectedProducts: [],
			basket: [],
			basketVersion: 0,
			propertyValues: [],
			deliveryExtraServicesValues: [],
			expectedDelivery: null,
			deliveryResponsibleId: null,
			personTypeId: null,
			deliveryId: null,
			delivery: null,
			isEnabledSubmit: false,
			errors: [],
			total: {
				sum: null,
				discount: null,
				result: null,
				resultNumeric: null,
			},
		}
	}

	getActions()
	{
		return {
			resetBasket ({ commit })
			{
				commit('clearBasket');
			},
			setCurrency: ({ commit }, payload) =>
			{
				const currency = payload || '';
				commit('setCurrency', currency);
			},
			enableSubmitButton: ({ commit }, payload) =>
			{
				commit('setSubmitButtonStatus', true);
			},
			disableSubmitButton: ({ commit }, payload) =>
			{
				commit('setSubmitButtonStatus', false);
			},
			setDeliveryId: ({ commit }, payload) =>
			{
				commit('setDeliveryId', payload);
			},
			setDelivery: ({ commit }, payload) =>
			{
				commit('setDelivery', payload);
			},
			setPropertyValues: ({ commit }, payload) =>
			{
				commit('setPropertyValues', payload);
			},
			setDeliveryExtraServicesValues: ({ commit }, payload) =>
			{
				commit('setDeliveryExtraServicesValues', payload);
			},
			setExpectedDelivery: ({ commit }, payload) =>
			{
				commit('setExpectedDelivery', payload);
			},
			setDeliveryResponsibleId: ({ commit }, payload) =>
			{
				commit('setDeliveryResponsibleId', payload);
			},
			setPersonTypeId: ({ commit }, payload) =>
			{
				commit('setPersonTypeId', payload);
			},
		}
	}

	getGetters()
	{
		return {
			getBasket: state => index =>
			{
				return state.basket;
			},
			isAllowedSubmit: state =>
			{
				return state.isEnabledSubmit;
			},
			getTotal: state =>
			{
				return state.total;
			},
			getDelivery: state =>
			{
				return state.delivery;
			},
			getDeliveryId: state =>
			{
				return state.deliveryId;
			},
			getPropertyValues: state =>
			{
				return state.propertyValues;
			},
			getDeliveryExtraServicesValues: state =>
			{
				return state.deliveryExtraServicesValues;
			},
			getExpectedDelivery: state =>
			{
				return state.expectedDelivery;
			},
			getDeliveryResponsibleId: state =>
			{
				return state.deliveryResponsibleId;
			},
			getPersonTypeId: state =>
			{
				return state.personTypeId;
			},
		}
	}

	getMutations()
	{
		return {
			setBasket: (state, payload) =>
			{
				state.basket = payload;
			},
			setTotal: (state, payload) =>
			{
				state.total = Object.assign(state.total, payload);
			},
			clearBasket: (state, payload) =>
			{
				state.basket = [];
				state.basketVersion++ ;
			},
			setErrors: (state, payload) =>
			{
				state.errors = payload;
			},
			setDeliveryId: (state, deliveryId) =>
			{
				state.deliveryId = deliveryId;
			},
			setDelivery: (state, delivery) =>
			{
				state.delivery = delivery;
			},
			setPropertyValues: (state, propertyValues) =>
			{
				state.propertyValues = propertyValues;
			},
			setDeliveryExtraServicesValues: (state, deliveryExtraServicesValues) =>
			{
				state.deliveryExtraServicesValues = deliveryExtraServicesValues;
			},
			setExpectedDelivery: (state, expectedDelivery) =>
			{
				state.expectedDelivery = expectedDelivery;
			},
			setDeliveryResponsibleId: (state, deliveryResponsibleId) =>
			{
				state.deliveryResponsibleId = deliveryResponsibleId;
			},
			clearErrors: (state) =>
			{
				state.errors = [];
			},
			setProcessingId: (state, payload) =>
			{
				state.processingId = payload;
			},
			setCurrency: (state, payload) =>
			{
				state.currency = payload;
			},
			setPersonTypeId: (state, payload) =>
			{
				state.personTypeId = payload;
			},
			showBanner: (state) =>
			{
				state.showPaySystemSettingBanner = true;
			},
			hideBanner: (state) =>
			{
				state.showPaySystemSettingBanner = false;
			},
			enableSubmit: (state) =>
			{
				state.isEnabledSubmit = true;
			},
			disableSubmit: (state) =>
			{
				state.isEnabledSubmit = false;
			},
		}
	}
}