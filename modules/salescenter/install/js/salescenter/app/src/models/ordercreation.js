import {VuexBuilderModel} from 'ui.vue.vuex';

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
			isSenderSelected: true,
			isCompilationMode: false,
			errors: [],
			total: {
				sum: null,
				discount: null,
				result: null,
			},
			/**
			 * ID of selected pay systems available for order payment
			 */
			availablePaySystemsIds: [],
			paymentResponsibleId: null,
			isMobileInstalledForResponsible: false,
			responsiblePhoneNumbers: [],
		};
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
			setPaymentResponsibleId: ({ commit }, payload) =>
			{
				commit('setPaymentResponsibleId', payload);
			},
			setMobileInstalledForResponsible: ({ commit }, payload) =>
			{
				commit('setMobileInstalledForResponsible', payload);
			},
			setResponsiblePhoneNumbers: ({ commit }, payload) =>
			{
				commit('setResponsiblePhoneNumbers', payload);
			},
			setPersonTypeId: ({ commit }, payload) =>
			{
				commit('setPersonTypeId', payload);
			},
			setAvailablePaySystemsIds: ({ commit }, payload) =>
			{
				commit('setAvailablePaySystemsIds', payload);
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
				return state.isEnabledSubmit && state.isSenderSelected;
			},
			isCompilationMode: state =>
			{
				return state.isCompilationMode;
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
			getPaymentResponsibleId: state =>
			{
				return state.paymentResponsibleId;
			},
			isMobileInstalledForResponsible: state =>
			{
				return state.isMobileInstalledForResponsible;
			},
			getResponsiblePhoneNumbers: state =>
			{
				return state.responsiblePhoneNumbers;
			},
			getPersonTypeId: state =>
			{
				return state.personTypeId;
			},
			getAvailablePaySystemsIds: state =>
			{
				return state.availablePaySystemsIds;
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
			setPaymentResponsibleId: (state, paymentResponsibleId) =>
			{
				state.paymentResponsibleId = paymentResponsibleId;
			},
			setMobileInstalledForResponsible: (state, isMobileInstalledForResponsible) =>
			{
				state.isMobileInstalledForResponsible = isMobileInstalledForResponsible;
			},
			setResponsiblePhoneNumbers: (state, responsiblePhoneNumbers) =>
			{
				state.responsiblePhoneNumbers = responsiblePhoneNumbers;
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
			setAvailablePaySystemsIds: (state, payload) =>
			{
				state.availablePaySystemsIds = payload;
			},
			setIsSenderSelected: (state, isSelected) =>
			{
				state.isSenderSelected = isSelected;
			},
			enableSubmit: (state) =>
			{
				state.isEnabledSubmit = true;
			},
			disableSubmit: (state) =>
			{
				state.isEnabledSubmit = false;
			},
			enableCompilationMode: (state) =>
			{
				state.isCompilationMode = true;
			},
			disableCompilationMode: (state) =>
			{
				state.isCompilationMode = false;
			},
		}
	}
}
