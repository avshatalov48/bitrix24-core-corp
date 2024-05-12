/**
 * @module ui-system/blocks/icon
 */
jn.define('ui-system/blocks/icon', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const icons = require('assets/icons');
	const { OutlineIconTypes } = require('assets/icons/types');
	const { mergeImmutable } = require('utils/object');
	const DEFAULT_ICON_SIZE = {
		width: 20,
		height: 20,
	};

	/**
	 * @function IconView
	 * @params {object} props
	 * @params {string} [props.icon]
	 * @params {string} [props.color]
	 * @params {object | number} [props.size]
	 * @params {number} [props.size.height]
	 * @params {number} [props.size.width]
	 * @params {boolean} [props.disabled]
	 * @return Image
	 */
	const IconView = (props = {}) => {
		const {
			icon = null,
			color = null,
			size = null,
			iconSize = null,
			iconParams = {},
			disabled = false,
			...restProps
		} = props;

		let {
			iconColor = color,
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

		if (size || iconSize)
		{
			const iconViewSize = size || iconSize;
			const getBoxSize = (boxSize) => ({
				width: boxSize,
				height: boxSize,
			});

			iconStyle = typeof iconViewSize === 'number' ? getBoxSize(iconViewSize) : iconViewSize;
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
		color: PropTypes.string,
		disabled: PropTypes.bool,
		size: PropTypes.oneOfType([
			PropTypes.number,
			PropTypes.exact({
				height: PropTypes.number,
				width: PropTypes.number,
			}),
		]),
		iconParams: PropTypes.object,
	};

	IconView.defaultProps = {
		icon: OutlineIconTypes.attach1,
		disabled: false,
	};

	module.exports = {
		IconView,
		StorybookComponent: IconView,
		iconTypes: {
			outline: OutlineIconTypes,
		},
	};
});
