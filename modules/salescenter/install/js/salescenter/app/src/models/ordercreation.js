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
			propertyValues: [],
			deliveryExtraServicesValues: [],
			expectedDelivery: null,
			deliveryResponsibleId: null,
			personTypeId: null,
			deliveryId: null,
			delivery: null,
			errors: [],
			total: {
				sum: null,
				discount: null,
				result: null,
				resultNumeric: null,
			},
		}
	}

	static getBasketItemState()
	{
		return {
			productId: null,
			code: null,
			name: '',
			sort: 0,
			price: 0,
			basePrice: 0,
			quantity: 0,
			showDiscount: '',
			discount: 0,
			discountInfos: [],
			discountType: 'percent',
			module: null,
			measureCode: 0,
			measureName: '',
			taxRate: 0,
			taxIncluded: 'N',
			isCustomPrice: 'N',
			isCreatedProduct: 'N',
			encodedFields: null,
			image: [],
			errors: [],
		};
	}

	getActions()
	{
		return {
			refreshBasket({ commit, dispatch, state }, payload)
			{
				if (this.updateTimer)
				{
					clearTimeout(this.updateTimer);
				}

				this.updateTimer = setTimeout(() => {
					const currentProcessingId = Math.random() * 100000;
					commit('setProcessingId', currentProcessingId);

					BX.ajax.runAction(
						"salescenter.api.order.refreshBasket",
						{
							data: {basketItems: state.basket}
						}
					)
					.then((result) => {
						if (currentProcessingId === state.processingId)
						{
							const data = BX.prop.getObject(result,"data", {});
							dispatch('processRefreshRequest', {
								total: BX.prop.getObject(
									data,
									"total",
									{
										sum: 0,
										discount: 0,
										result: 0,
										resultNumeric: 0,
									}
								),
								basket: BX.prop.get(data,"items",[])
							});
							if (payload.onsuccess)
							{
								payload.onsuccess();
							}
						}
					})
					.catch((result) => {
						if (currentProcessingId === state.processingId)
						{
							const data = BX.prop.getObject(result,"data", {});
							dispatch('processRefreshRequest', {
								errors: BX.prop.get(result,"errors", []),
								basket: BX.prop.get(data,"items",[])
							});
							if (payload.onfailure)
							{
								payload.onfailure();
							}
						}
					});
				}, 0);
			},
			processRefreshRequest({ commit, dispatch }, payload)
			{
				if (BX.type.isArray(payload.basket))
				{
					payload.basket.forEach((basketItem) => {
						commit('updateBasketItem', {
							index: basketItem.sort,
							fields: basketItem,
						});
					});

					commit('setSelectedProducts');
				}

				if (BX.type.isObject(payload.total))
				{
					commit('setTotal', payload.total);
				}

				if (BX.type.isArray(payload.errors))
				{
					commit('setErrors', payload.errors);
				}
				else
				{
					commit('clearErrors');
				}

				commit('setProcessingId', null);
			},
			resetBasket ({ commit })
			{
				commit('clearBasket');
				commit('setTotal', {
					sum: null,
					discount: null,
					result: null,
					resultNumeric: null,
				});
				commit('addBasketItem');
			},
			deleteBasketItem({ commit, state, dispatch }, payload)
			{
				commit('deleteBasketItem', payload);

				if (state.basket.length > 0)
				{
					state.basket.forEach((item, i) => {
						commit('updateBasketItem', {
							index: i,
							fields: {sort: i}
						});
					});
				}

				dispatch('refreshBasket');
			},
			removeItem({ commit, state, dispatch }, payload)
			{
				commit('deleteBasketItem', payload);
				if (state.basket.length === 0)
				{
					commit('addBasketItem');
				}
				else
				{
					state.basket.forEach((item, i) => {
						commit('updateBasketItem', {
							index: i,
							fields: {sort: i}
						});
					});
				}

				dispatch('refreshBasket');
			},
			changeBasketItem: ({ commit, dispatch }, payload) =>
			{
				commit('updateBasketItem', payload);
				commit('setSelectedProducts');
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
				return state.basket.filter( basketItem => (
					(basketItem.module === 'catalog' && parseInt(basketItem.productId) > 0)
					|| (basketItem.module !== 'catalog' && BX.type.isNotEmptyString(basketItem.name) && parseFloat(basketItem.quantity) > 0)
				)).length > 0;
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
			addBasketItem: (state, payload) =>
			{
				let item = OrderCreationModel.getBasketItemState();
				item.sort = state.basket.length;
				state.basket.push(item);
			},
			updateBasketItem: (state, payload) =>
			{
				if (typeof state.basket[payload.index] === 'undefined')
				{
					Vue.set(state.basket, payload.index, OrderCreationModel.getBasketItemState());
				}

				state.basket[payload.index] = Object.assign(
					state.basket[payload.index],
					payload.fields
				);
			},
			clearBasket: (state) =>
			{
				state.basket = [];
			},
			deleteBasketItem: (state, payload) =>
			{
				state.basket.splice(payload.index, 1);
			},
			setSelectedProducts: (state) =>
			{
				state.selectedProducts = state.basket
					.filter( basketItem => (basketItem.module === 'catalog' && parseInt(basketItem.productId) > 0))
					.map( filtered => filtered.productId);
			},
			setTotal: (state, payload) =>
			{
				state.total = Object.assign(
					state.total,
					payload
				);
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
		}
	}
}