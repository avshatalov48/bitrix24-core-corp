/**
 * @module ui-system/blocks/plain-view
 */
jn.define('ui-system/blocks/plain-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { IconView } = require('ui-system/blocks/icon');

	/**
	 * @function PlainView
	 * @params {object} props
	 * @params {string} [props.text]
	 * @params {function} [props.onClick]
	 * @params {string} [props.icon]
	 * @params {string} [props.iconColor]
	 * @params {object} [props.iconSize]
	 * @params {number} [props.iconSize.height]
	 * @params {number} [props.iconSize.width]
	 * @return PlainView
	 */
	const PlainView = (props) => {
		const {
			text = '',
			color = AppTheme.colors.base2,
			icon = null,
			iconColor = AppTheme.colors.base2,
			iconSize = null,
			onClick = null,
		} = props;

		if (!text && !icon)
		{
			return null;
		}

		const isText = Boolean(text.trim());

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				onClick,
			},
			IconView({ icon, iconColor, iconSize }),
			isText && Text({
				style: {
					color,
					fontSize: 16,
				},
				text,
			}),
		);
	};

	module.exports = { PlainView };
});
