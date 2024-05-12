/**
 * @module im/messenger/lib/ui/base/buttons/icon-button
 */
jn.define('im/messenger/lib/ui/base/buttons/icon-button', (require, exports, module) => {
	/* global View */
	const { withPressed } = require('utils/color');
	const AppTheme = require('apptheme');

	/**
	 * @class IconButton
	 * @desc Returns button layoutComponent with icon and text into border.
	 * * By default, used hexColor:#11A9D9, and border 1 solid.
	 * * Style may be changed with instance options
	 */
	class IconButton extends LayoutComponent
	{
		/**
		 * @param options
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
		 */
		constructor(options)
		{
			super({ disable: false, ...options });
		}

		render()
		{
			const backgroundColor = this.props.style?.backgroundColor || AppTheme.colors.bgContentPrimary;
			const borderColor = this.props.disable ? AppTheme.colors.base7 : this.props.style?.border?.color;
			const backgroundColorChange = this.props.disable ? backgroundColor : withPressed(backgroundColor);
			const textStyle = {
				alignSelf: 'center',
				color: AppTheme.colors.accentMainPrimaryalt,
				fontSize: 12,
				fontWeight: 500,
				marginBottom: 12,
				...this.props.style?.text,
			};

			textStyle.color = this.props.disable ? AppTheme.colors.base6 : textStyle.color;

			return View(
				{
					style: {
						marginHorizontal: 2,
						width: this.props.style?.width || 83,
						backgroundColor: backgroundColorChange,
						borderRadius: 16,
						flexDirection: 'column',
						justifyContent: 'center',
						borderWidth: this.props.style?.border?.width || 1,
						borderColor: borderColor || AppTheme.colors.accentSoftBlue2,
					},
					clickable: !this.props.disable,
					onClick: () => this.onClick(),
				},
				Image({
					style: this.props.style?.icon || {
						width: 32,
						height: 32,
						alignSelf: 'center',
						marginTop: 10,
					},
					svg: { content: this.props.icon },
				}),
				Text(
					{
						text: this.props.text || '',
						style: textStyle,
					},
				),
			);
		}

		onClick()
		{
			this.props.callback();
		}
	}

	module.exports = { IconButton };
});
