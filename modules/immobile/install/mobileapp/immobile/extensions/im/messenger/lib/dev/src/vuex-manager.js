/* eslint-disable no-console */
/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/lib/dev/vuex-manager
 */
jn.define('im/messenger/lib/dev/vuex-manager', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const testModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			getById: (state) => (id) => {
				return state.collection[id];
			},
		},
		actions: {
			add: (store, payload) => {
				console.log('action start');
				const {
					id,
					data,
				} = payload.data;

				store.commit('add', {
					actionName: 'set',
					data: {
						id,
						data,
					},
				});
				console.log('action finished');
			},
		},
		mutations: {
			add: (state, payload) => {
				console.log('mutation start');
				const {
					id,
					data,
				} = payload.data;

				state.collection[id] = data;
				console.log('mutation finished');
			},
		},
	};

	class VuexManagerPlayground
	{
		constructor()
		{
			this.titleParams = {
				text: 'Vuex playground',
				detailText: '',
				imageColor: '#3eaf7c',
				useLetterImage: true,
			};

			this.widget = null;
		}

		open()
		{
			PageManager.openWidget(
				'chat.dialog',
				{
					titleParams: this.titleParams,
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch((error) => {
					console.error(error);
				})
			;
		}

		onWidgetReady(widget)
		{
			this.widget = widget;
			this.initStore();
		}

		initStore()
		{
			this.store = createStore({
				modules: {
					testModel,
				},
			});

			this.storeManager = new VuexManager(this.store)
				.build()
			;

			this.storeManager.on('testModel/add', (mutation) => {
				const data = mutation.payload.data;

				console.log('render1', data);
			});

			this.storeManager.on('testModel/add', (mutation) => {
				const data = mutation.payload.data;

				console.log('render2', data);
			});

			this.storeManager.on('testModel/add', (mutation) => {
				const data = mutation.payload.data;

				console.log('render3', data);
			});
		}

		add()
		{
			this.store.dispatch('testModel/add', { id: 1, data: { title: 'test' } });
		}
	}

	module.exports = {
		VuexManagerPlayground,
	};
});
