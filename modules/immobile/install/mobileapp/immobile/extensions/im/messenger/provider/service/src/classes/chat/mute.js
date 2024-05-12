/**
 * @module im/messenger/provider/service/classes/chat/mute
 */
jn.define('im/messenger/provider/service/classes/chat/mute', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { restManager: generalRestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const/rest');
	const { Runtime } = require('runtime');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class MuteService
	 * @desc The class API provides requests mute dialog
	 * * rest manager created in two variants:
	 * * 1 - newRestManager ( this new manager with only yours actions )
	 * * 2 - generalRestManager ( this general/global manager, used by messenger )
	 */
	class MuteService
	{
		/**
		 * @constructor
		 * @param {object} store
		 * @param {object} [restManager=generalRestManager] - instance new RestManager()
		 * @param {Function} [errorCallback] - callback for handle error response
		 */
		constructor(restManager = generalRestManager, errorCallback)
		{
			this.store = serviceLocator.get('core').getStore();
			this.restManager = restManager;
			this.errorCallback = errorCallback;
			this.sendMuteRequestDebounced = Runtime.debounce(this.sendMuteRequest, 500);
		}

		muteChat(dialogId)
		{
			Logger.log('ChatService: muteChat', dialogId);
			this.store.dispatch('dialoguesModel/mute', { dialogId });
			const queryParams = { dialog_id: dialogId, action: 'Y' };

			this.sendMuteRequestDebounced(queryParams);
		}

		unmuteChat(dialogId)
		{
			Logger.log('ChatService: unmuteChat', dialogId);
			this.store.dispatch('dialoguesModel/unmute', { dialogId });
			const queryParams = { dialog_id: dialogId, action: 'N' };

			this.sendMuteRequestDebounced(queryParams);
		}

		/**
		 * @desc Send request with call method
		 * @param {object} queryParams
		 * @param {string} queryParams.dialog_id
		 * @param {string} queryParams.action - ('N','Y')
		 */
		async sendMuteRequest(queryParams)
		{
			const { action, dialog_id } = queryParams;
			BX.rest.callMethod(RestMethod.imChatMute, queryParams)
				.then((response) => {
					return this.handleRequestChatMute(action, dialog_id, response);
				})
				.catch((error) => {
					Logger.error('MuteService: error mute send request', error);
				});
		}

		handleRequestChatMute(action, dialog_id, response)
		{
			const isMute = action === 'Y';
			const error = response.error();
			if (error)
			{
				Logger.error('Sidebar.handleRequestChatMute error', error);
				if (this.errorCallback)
				{
					this.errorCallback(error, action, dialog_id);
				}

				return;
			}

			const data = {
				dialogId: dialog_id,
				isMute,
			};

			const dialogMuteAction = isMute ? 'dialoguesModel/mute' : 'dialoguesModel/unmute';
			this.store.dispatch(dialogMuteAction, data);
			this.store.dispatch('sidebarModel/changeMute', { data });
		}
	}

	module.exports = {
		MuteService,
	};
});
