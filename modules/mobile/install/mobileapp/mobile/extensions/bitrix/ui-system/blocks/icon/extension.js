/**
 * @module ui-system/blocks/icon
 */
jn.define('ui-system/blocks/icon', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const icons = require('assets/icons');
	const { mergeImmutable } = require('utils/object');
	const DEFAULT_ICON_SIZE = {
		width: 20,
		height: 20,
	};

	/**
	 * @function IconView
	 * @params {object} props
	 * @params {string} [props.icon]
	 * @params {string} [props.iconColor]
	 * @params {object | number} [props.iconSize]
	 * @params {number} [props.iconSize.height]
	 * @params {number} [props.iconSize.width]
	 * @params {boolean} [props.disabled]
	 * @return Image
	 */
	const IconView = (props = {}) => {
		const {
			icon = null,
			iconSize = null,
			iconParams = {},
			disabled = false,
			...restProps
		} = props;

		let {
			iconColor = null,
		} = props;

		let iconContent = null;

		if (disabled)
		{
			iconColor = Color.base5;
			iconParams.color = Color.base5;
		}

		Object.values(icons).forEach((folder) => {
			if (folder[icon])
			{
				iconContent = folder[icon](iconParams);
			}
		});

		if (!iconContent)
		{
			return null;
		}

		let iconStyle = DEFAULT_ICON_SIZE;

		if (iconSize)
		{
			const getBoxSize = (size) => ({
				width: size,
				height: size,
			});

			iconStyle = typeof iconSize === 'number' ? getBoxSize(iconSize) : iconSize;
		}

		const iconProps = { style: iconStyle };

		if (typeof iconColor === 'string')
		{
			iconProps.tintColor = iconColor;
		}

		const mergedProps = mergeImmutable(iconProps, restProps);

		return Image({
			svg: {
				content: iconContent,
			},
			...mergedProps,
		});
	};

	IconView.propTypes = {
		icon: PropTypes.string.isRequired,
		disabled: PropTypes.bool,
		iconColor: PropTypes.string,
		iconSize: PropTypes.oneOfType([
			PropTypes.number,
			PropTypes.exact({
				height: PropTypes.number,
				width: PropTypes.number,
			}),
		]),
		iconParams: PropTypes.object,
	};

	module.exports = { IconView };
});
