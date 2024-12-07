/**
 * @module bizproc/task/buttons/button
 * */

jn.define('bizproc/task/buttons/button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { merge, isObjectLike } = require('utils/object');

	class Button extends PureComponent
	{
		/**
		 * @param {Partial<{
		 *     testId: string,
		 *     text: string,
		 *     icon: string,
		 *     onClick(): void,
		 *     style: styles & Partial<{ textColor: string, fontSize: number }>,
		 * }>} props
		 */
		constructor(props)
		{
			super(props);

			this.initStyle();
		}

		initStyle()
		{
			this.style = { ...DEFAULT_STYLE };

			if (isObjectLike(this.props.style))
			{
				merge(this.style, this.props.style);
			}
		}

		render()
		{
			return View(
				{
					style: this.style,
					testId: this.props.testId,
					onClick: this.onClick.bind(this),
				},
				this.props.icon && Image({
					style: {
						width: 28,
						height: 28,
						alignSelf: 'center',
					},
					svg: {
						content: this.props.icon,
					},
				}),
				Text({
					style: {
						fontWeight: '500',
						fontSize: BX.prop.getInteger(this.style, 'fontSize', 15),
						color: this.style.textColor,
					},
					text: this.props.text,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
			);
		}

		onClick()
		{
			const callback = BX.prop.getFunction(this.props, 'onClick', () => {});

			callback();
		}
	}

	const DEFAULT_STYLE = Object.freeze({
		flexGrow: 1,
		flexShrink: 1,
		flexDirection: 'row',
		justifyContent: 'center',
		height: 36,
		borderRadius: 8,
		borderWidth: 1,
		borderColor: AppTheme.colors.accentMainPrimary,
		padding: 8,
		paddingHorizontal: 16,
	});

	module.exports = {
		Button,
	};
});
