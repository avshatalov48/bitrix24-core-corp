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
			errors: [],
			total: {
				sum: null,
				discount: null,
				result: null
			}
		}
	}

	static getBasketItemState()
	{
		return {
			productId: null,
			code: null,
			name: '',
			sort: 0,
			basePrice: 0,
			catalogPrice: 0,
			quantity: 0,
			showDiscount: '',
			discount: 0,
			discountInfos: [],
			discountType: 'percent',
			module: null,
			formattedPrice: 0,
			formattedCatalogPrice: null,
			measureCode: 0,
			measureName: '',
			isCustomPrice: 'N',
			isCreatedProduct: 'N',
			encodedFields: null,
			errors: [],
		};
	}

	getActions()
	{
		return {
			refreshBasket({ commit, dispatch, state }, payload)
			{
				payload.timeout = payload.timeout || 300;
				if (this.updateTimer)
				{
					clearTimeout(this.updateTimer);
				}

				this.updateTimer = setTimeout(() => {
					const currentProcessingId = Math.random() * 100000;
					commit('setProcessingId', currentProcessingId);
					BX.ajax.runAction(
						"salescenter.api.order.refreshBasket",
						{ data: { basketItems: state.basket } }
					)
					.then((result) => {
						if (currentProcessingId === state.processingId)
						{
							const data = BX.prop.getObject(result,"data", {});
							dispatch('processRefreshRequest', {
								total: BX.prop.getObject(data,"total",{}),
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
				}, payload.timeout);
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
					dispatch('recalculate');
				}
				else
				{
					commit('clearErrors');
				}

				commit('setProcessingId', null);
			},
			recalculate({ commit, state })
			{
				commit('setProcessingId', null);
				let productCost = 0;
				let totalDiscount = 0;
				let resultSum = 0;
				state.basket.forEach((item, i) => {
					if (item.name === '')
					{
						return;
					}
					let currentPrice = item.basePrice;
					if (item.discount > 0)
					{
						let discountValue = item.discount;
						if (item.discountType === 'percent')
						{
							discountValue = (item.basePrice * item.discount) / 100
						}

						currentPrice -= discountValue;
						totalDiscount += (discountValue * item.quantity);
					}

					currentPrice = (currentPrice > 0) ? currentPrice : 0;
					resultSum += (currentPrice * item.quantity);
					productCost += (item.catalogPrice * item.quantity);

					commit('updateBasketItem', {
						index: i,
						fields: {
							formattedPrice: BX.Currency.currencyFormat(currentPrice, state.currency, true),
							formattedCatalogPrice: BX.Currency.currencyFormat(item.catalogPrice, state.currency, true),
						},
					});
				});
				totalDiscount = Math.min(totalDiscount, productCost);
				commit('setTotal', {
					sum: BX.Currency.currencyFormat(productCost, state.currency, true),
					discount: BX.Currency.currencyFormat(totalDiscount, state.currency, true),
					result: BX.Currency.currencyFormat(resultSum, state.currency, true)
				});
			},
			resetBasket ({ commit })
			{
				commit('clearBasket');
				commit('setTotal', {
					sum: null,
					discount: null,
					result: null
				});
				commit('addBasketItem');
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

				dispatch('recalculate');
			},
			changeBasketItem: ({ commit, dispatch }, payload) =>
			{
				commit('updateBasketItem', payload);
				commit('setSelectedProducts');
				dispatch('recalculate');
			},
			setCurrency: ({ commit }, payload) =>
			{
				const currency = payload || '';
				commit('setCurrency', currency);
			}
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