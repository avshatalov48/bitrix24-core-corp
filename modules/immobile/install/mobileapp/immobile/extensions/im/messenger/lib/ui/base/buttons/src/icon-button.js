/**
 * @module im/messenger/lib/ui/base/buttons/icon-button
 */
jn.define('im/messenger/lib/ui/base/buttons/icon-button', (require, exports, module) => {
	const { withPressed } = require('utils/color');
	const { Theme } = require('im/lib/theme');

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
		 * @param {boolean} [options.disableClick=false]
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
			super({ disable: false, disableClick: false, ...options });
		}

		render()
		{
			const backgroundColor = this.props.style?.backgroundColor || Theme.colors.bgContentPrimary;
			const borderColor = this.props.disable ? Theme.colors.base7 : this.props.style?.border?.color;
			const backgroundColorChange = this.props.disable ? backgroundColor : withPressed(backgroundColor);
			const textStyle = {
				alignSelf: 'center',
				color: Theme.colors.accentMainPrimaryalt,
				fontSize: 13,
				fontWeight: '400',
				marginTop: 4,
				marginBottom: 12,
				...this.props.style?.text,
			};

			textStyle.color = this.props.disable ? Theme.colors.base6 : textStyle.color;

			return View(
				{
					style: {
						marginHorizontal: 2,
						width: this.props.style?.width || null,
						backgroundColor: backgroundColorChange,
						maxWidth: 86,
						borderRadius: 16,
						flexDirection: 'column',
						justifyContent: 'center',
						flex: 1,
						borderWidth: this.props.style?.border?.width || 1,
						borderColor: borderColor || Theme.colors.bgSeparatorPrimary,
						paddingHorizontal: 10,
					},
					clickable: !this.props.disableClick,
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
						numberOfLines: 1,
						ellipsize: 'end',
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
