/**
 * @module im/messenger-v2/controller/recent/service/select/common
 */
jn.define('im/messenger-v2/controller/recent/service/select/common', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { openDialog } = require('im/messenger-v2/controller/recent/service/select/lib/opener');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');

	/**
	 * @implements {ISelectService}
	 * @class CommonSelectService
	 */
	class CommonSelectService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async onUiReady(ui)
		{
			this.logger.log('onUiReady');
			ui?.on(EventType.recent.itemSelected, this.onItemSelected);
		}

		/**
		 * @param {ItemSelectedEventData} itemData
		 */
		onItemSelected = async (itemData) => {
			if (itemData.params.disableTap)
			{
				return;
			}

			if (itemData.params.type === 'call')
			{
				this.#processCallItem(itemData);

				return;
			}

			this.#openDialog(itemData.id);
		};

		/**
		 * @param {CallItem} itemData
		 */
		#processCallItem(itemData)
		{
			const { call, canJoin } = itemData.params;
			if (canJoin)
			{
				CallManager.getInstance().joinCall(
					call.id,
					call.uuid,
					call.associatedEntity,
				);

				return;
			}

			this.#openDialog(call.associatedEntity.id);
		}

		async #openDialog(dialogId)
		{
			try
			{
				await openDialog(dialogId);
			}
			catch (error)
			{
				this.logger.error('openDialog error', error);
			}
		}
	}

	module.exports = CommonSelectService;
});
