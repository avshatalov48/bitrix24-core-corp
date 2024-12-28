/**
 * @module calendar/ajax/settings
 */
jn.define('calendar/ajax/settings', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const SettingsAction = {
		SET_DENY_BUSY_INVITATION: 'setDenyBusyInvitation',
		SET_SHOW_WEEK_NUMBERS: 'setShowWeekNumbers',
		SET_SHOW_DECLINED: 'setShowDeclined',
	};

	class SettingsAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'calendarmobile.settings';
		}

		/**
		 *
		 * @param {String} denyBusyInvitation
		 * @returns {Promise<Object, void>}
		 */
		setDenyBusyInvitation(denyBusyInvitation)
		{
			return this.fetch(SettingsAction.SET_DENY_BUSY_INVITATION, { denyBusyInvitation });
		}

		/**
		 *
		 * @param {String} showWeekNumbers
		 * @returns {Promise<Object, void>}
		 */
		setShowWeekNumbers(showWeekNumbers)
		{
			return this.fetch(SettingsAction.SET_SHOW_WEEK_NUMBERS, { showWeekNumbers });
		}

		/**
		 *
		 * @param {String} showDeclined
		 * @returns {Promise<Object, void>}
		 */
		setShowDeclined(showDeclined)
		{
			return this.fetch(SettingsAction.SET_SHOW_DECLINED, { showDeclined });
		}
	}

	module.exports = {
		SettingsAjax: new SettingsAjax(),
	};
});
