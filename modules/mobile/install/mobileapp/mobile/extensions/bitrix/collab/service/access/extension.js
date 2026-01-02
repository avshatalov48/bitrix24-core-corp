/**
 * @module collab/service/access
 */
jn.define('collab/service/access', (require, exports, module) => {
	const { CollabDisabled } = require('layout/ui/collab-disabled-opener');
	const { selectIsCollabToolEnabled } = require('statemanager/redux/slices/settings/selector');
	const store = require('statemanager/redux/store');
	const { loadIsCollabToolEnabled } = require('statemanager/redux/slices/settings/tools');

	/**
	 * Service to manage collab accessibility
	 * @class CollabAccessServiceClass
	 */
	class CollabAccessServiceClass
	{
		constructor()
		{
			this.isInitialized = false;
		}

		/**
		 * @private
		 * @returns {Promise<void>}
		 */
		async #ensureInitialized()
		{
			if (!this.isInitialized)
			{
				await loadIsCollabToolEnabled();
				this.isInitialized = true;
			}
		}

		/**
		 * @returns {Promise<boolean>}
		 */
		async checkAccess()
		{
			await this.#ensureInitialized();

			return this.#isCollabToolEnabled();
		}

		/**
		 * @public
		 * @returns {*}
		 */
		#isCollabToolEnabled()
		{
			const state = store.getState();

			return selectIsCollabToolEnabled(state);
		}

		/**
		 * @public
		 */
		openAccessDeniedBox()
		{
			CollabDisabled.open({});
		}
	}

	const CollabAccessService = new CollabAccessServiceClass();

	module.exports = {
		CollabAccessService,
	};
});
