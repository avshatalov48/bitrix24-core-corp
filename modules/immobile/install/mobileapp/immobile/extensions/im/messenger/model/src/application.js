/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/application
 */
jn.define('im/messenger/model/application', (require, exports, module) => {

	const { DialogHelper } = jn.require('im/messenger/lib/helper');

	const applicationModel = {
		namespaced: true,
		state: () => ({
			dialog: {
				id: 0,
			},
		}),
		getters: {
			getDialogId: (state) => {
				const chatSettings = Application.storage.getObject('settings.chat', {
					nativeDialogEnable: false,
				});

				if (chatSettings.nativeDialogEnable)
				{
					return state.dialog.id;
				}

				const page = PageManager.getNavigator().getVisible();
				if (page.type === 'Web' && page.pageId === 'im-' + state.dialog.id)
				{
					return state.dialog.id;
				}

				return 0;
			},
			isDialogOpen: (state, getters) => {
				return getters.getDialogId !== 0;
			},
		},
		actions: {
			setDialogId: (store, payload) =>
			{
				if (DialogHelper.isDialogId(payload))
				{
					store.commit('setDialogId', payload);
					return;
				}

				if (DialogHelper.isChatId(payload))
				{
					store.commit('setDialogId', Number(payload));
				}
			},
		},
		mutations: {
			setDialogId: (state, payload) => {
				state.dialog.id = payload;
			},
		}
	};

	module.exports = { applicationModel };
});
