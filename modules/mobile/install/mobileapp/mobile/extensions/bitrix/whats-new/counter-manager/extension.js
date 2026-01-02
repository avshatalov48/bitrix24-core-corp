/**
 * @module whats-new/counter-manager
 */
jn.define('whats-new/counter-manager', (require, exports, module) => {
	const { AvaMenu, AvaMenuItemsIds } = require('ava-menu');
	const { WhatsNewService } = require('whats-new/service');

	/**
	 * @class WhatsNewCounter
	 */
	class WhatsNewCounter
	{
		/**
		 * @param {string} value
		 */
		static setAvaMenuCounter(value)
		{
			AvaMenu.setCounter({
				elemId: AvaMenuItemsIds.whatsNew,
				value,
			});
		}

		/**
		 * @param {string} value
		 */
		static initializeCounter(value)
		{
			WhatsNewCounter.setAvaMenuCounter(value);

			WhatsNewService.subscribeToNewCountChange((counter) => {
				WhatsNewCounter.setAvaMenuCounter(counter);
			});
		}
	}

	module.exports = {
		WhatsNewCounter,
	};
});
