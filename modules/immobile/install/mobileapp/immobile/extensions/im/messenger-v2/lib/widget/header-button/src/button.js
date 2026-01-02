/**
 * @module im/messenger-v2/lib/widget/header-button/button
 */
jn.define('im/messenger-v2/lib/widget/header-button/button', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class Button
	 */
	class Button
	{
		/**
		 * @param {HeaderButtonConfig} options
		 * @return {Button}
		 */
		static create(options)
		{
			return new this(options);
		}

		/**
		 * @param {HeaderButtonConfig} options
		 */
		constructor(options)
		{
			this.id = options.id;
			this.type = options.type;
			this.callback = options.callback;

			this.testId = Type.isStringFilled(options.testId) ? options.testId : `${options.id}_button`;
			this.badgeCode = Type.isStringFilled(options.badgeCode) ? options.badgeCode : null;
			this.shouldShow = Type.isFunction(options.shouldShow) ? options.shouldShow : async () => true;
		}

		toWidgetHeaderButton()
		{
			return {
				id: this.id,
				testId: this.testId,
				type: this.type,
				callback: this.callback,
				badgeCode: this.badgeCode,
			};
		}
	}

	module.exports = { Button };
});
