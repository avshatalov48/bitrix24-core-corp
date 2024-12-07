/* eslint-disable no-console */
/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/lib/dev/menu/vuex-manager
 */
jn.define('im/messenger/lib/dev/menu/vuex-manager', (require, exports, module) => {
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

		async onWidgetReady(widget)
		{
			this.widget = widget;
			await this.initStore();
		}

		async initStore()
		{
			this.store = createStore({
				modules: {
					testModel,
				},
			});

			this.storeManager = new VuexManager(this.store);
			await this.storeManager.buildAsync();

			this.storeManager.on('testModel/add', async (mutation) => {
				const data = mutation.payload.data;

				return new Promise((resolve) => {
					console.log('render1', data);
					resolve();
				});
			});

			this.storeManager.on('testModel/add', async (mutation) => {
				const data = mutation.payload.data;

				return new Promise((resolve) => {
					console.log('render2', data);
					resolve();
				});
			});

			this.storeManager.on('testModel/add', async (mutation) => {
				const data = mutation.payload.data;

				return new Promise((resolve) => {
					console.log('render3', data);
					resolve();
				});
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
