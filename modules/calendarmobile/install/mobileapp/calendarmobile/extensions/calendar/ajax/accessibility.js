/**
 * @module calendar/ajax/accessibility
 */
jn.define('calendar/ajax/accessibility', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const AccessibilityActions = {
		GET: 'get',
		GET_LOCATION: 'getLocation',
	};

	/**
	 * @class Accessibility
	 */
	class AccessibilityAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'calendarmobile.accessibility';
		}

		/**
		 * @param userIds {array}
		 * @param timestampFrom {number}
		 * @param timestampTo {number}
		 * @returns {Promise<Object, void>}
		 */
		get({ userIds, timestampFrom, timestampTo })
		{
			return this.fetch(AccessibilityActions.GET, { userIds, timestampFrom, timestampTo });
		}

		/**
		 * @param locationIds {array}
		 * @param timestampFrom {number}
		 * @param timestampTo {number}
		 * @returns {Promise<Object, void>}
		 */
		getLocation({ locationIds, timestampFrom, timestampTo })
		{
			return this.fetch(AccessibilityActions.GET_LOCATION, { locationIds, timestampFrom, timestampTo });
		}
	}

	module.exports = { AccessibilityAjax: new AccessibilityAjax() };
});
