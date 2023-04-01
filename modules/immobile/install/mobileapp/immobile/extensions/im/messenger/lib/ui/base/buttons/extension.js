/**
 * @module im/messenger/lib/ui/base/buttons
 */
jn.define('im/messenger/lib/ui/base/buttons', (require, exports, module) => {

	const { FullWidthButton } = require('im/messenger/lib/ui/base/buttons/full-width-button');
	const { InviteButton } = require('im/messenger/lib/ui/base/buttons/invite-button');

	class ButtonFactory
	{
		/**
		 *
		 * @param {Object} options
		 * @param {string} options.text
		 * @param {Function} options.callback
		 * @param {string} [options.icon]
		 * @return {FullWidthButton}
		 */
		static createFullWidthButton(options)
		{
			return new FullWidthButton(options);
		}

		/**
		 *
		 * @param {Object} options
		 * @param {string} options.text
		 * @param {Function} options.callback
		 * @return {InviteButton}
		 */
		static createInviteButton(options)
		{
			return new InviteButton(options);
		}
	}

	module.exports = { ButtonFactory };
});