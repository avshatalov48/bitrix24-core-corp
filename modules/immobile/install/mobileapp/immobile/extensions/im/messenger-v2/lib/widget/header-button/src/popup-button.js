/**
 * @module im/messenger-v2/lib/widget/header-button/popup-button
 */
jn.define('im/messenger-v2/lib/widget/header-button/popup-button', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class PopupButton
	 */
	class PopupButton
	{
		/**
		 * @param {HeaderPopupButtonConfig} options
		 * @return {PopupButton}
		 */
		static create(options)
		{
			return new this(options);
		}

		/**
		 * @param {HeaderPopupButtonConfig} options
		 */
		constructor(options)
		{
			this.id = options.id;
			this.title = options.title;
			this.callback = options.callback;

			this.sectionCode = Type.isStringFilled(options.sectionCode) ? options.sectionCode : 'general';
			this.iconName = Type.isStringFilled(options.iconName) ? options.iconName : '';
			this.shouldShow = Type.isFunction(options.shouldShow) ? options.shouldShow : async () => true;
		}

		toPopupMenuButton()
		{
			return {
				id: this.id,
				title: this.title,
				iconName: this.iconName,
				sectionCode: this.sectionCode,
				callback: this.callback,
			};
		}
	}

	module.exports = { PopupButton };
});
