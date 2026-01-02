/**
 * @module navigator/base
 */
jn.define('navigator/base', (require, exports, module) => {
	const ACTION_DELAY = 300;

	/**
	 * @class BaseNavigator
	 */
	class BaseNavigator
	{
		constructor(props)
		{
			this.navigator = PageManager.getNavigator();
		}

		/**
		 * @return {boolean}
		 */
		isActiveTab()
		{
			return this.navigator.isActiveTab();
		}

		makeTabActive()
		{
			return new Promise((resolve) => {
				this.navigator.makeTabActive()
					.then(() => {
						resolve(true);
					})
					.catch((error) => {
						console.error('PageManager.makeTabActive error:', error);
						resolve(false);
					});
			});
		}

		/**
		 * @param {string} eventName
		 * @param {object} params
		 */
		onSubscribeToPushNotification(eventName, params = {})
		{
			BX.postComponentEvent(eventName, [params]);
		}
	}

	module.exports = {
		BaseNavigator,
		ACTION_DELAY,
	};
});
