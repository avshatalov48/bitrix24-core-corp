/**
 * @module im/messenger/lib/ui/base/buttons
 */
jn.define('im/messenger/lib/ui/base/buttons', (require, exports, module) => {

	const { FullWidthButton } = require('im/messenger/lib/ui/base/buttons/full-width-button');
	const { InviteButton } = require('im/messenger/lib/ui/base/buttons/invite-button');
	const { IconButton } = require('im/messenger/lib/ui/base/buttons/icon-button');

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

		/**
		 *
		 * @param {Object} options
		 * @param {string} options.icon
		 * @param {string} options.text
		 * @param {Function} options.callback
		 * @param {boolean} [options.disable=false]
		 * @param {object} [options.style]
		 * @param {string} [options.style.icon]
		 * @param {string} [options.style.text]
		 * @param {number} [options.style.width]
		 * @param {string} [options.style.backgroundColor]
		 * @param {string} [options.style.border.color]
		 * @param {number} [options.style.border.width]
		 * @return {IconButton}
		 */
		static createIconButton(options)
		{
			return new IconButton(options);
		}
	}

	module.exports = { ButtonFactory };
});