/**
 * @module ui-system/blocks/badges/status
 */
jn.define('ui-system/blocks/badges/status', (require, exports, module) => {
	const { Color, Component } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { BadgeStatusSize } = require('ui-system/blocks/badges/status/src/size-enum');
	const { BadgeStatusMode } = require('ui-system/blocks/badges/status/src/mode-enum');

	/**
	 * @param {Object} props
	 * @param {Number} [props.testId]
	 * @param {BadgeStatusMode} [props.mode=BadgeStatusMode.SUCCESS]
	 * @param {Boolean} [props.outline=false]
	 * @param {BadgeStatusSize} [props.size=BadgeSize.NORMAL]
	 * @param {Color} [props.backgroundColor=]
	 * @function BadgeStatus
	 */
	function BadgeStatus(props = {})
	{
		PropTypes.validate(BadgeStatus.propTypes, props, 'BadgeStatus');

		const {
			testId = '',
			mode = BadgeStatusMode.SUCCESS,
			size = BadgeStatusSize.NORMAL,
			outline = false,
			backgroundColor,
		} = props;

		if (!BadgeStatusMode.has(mode))
		{
			console.warn('BadgeStatusMode: status mode not selected');

			return null;
		}

		const statusMode = BadgeStatusMode.resolve(mode, BadgeStatusMode.SUCCESS);
		const statusSize = BadgeStatusSize.resolve(size, BadgeStatusSize.NORMAL);
		const iconSize = statusSize.getIconSize(outline);
		const backgroundSize = statusSize.getBackgroundSize();

		const icon = Image({
			testId: testId && `${testId}_${mode.getName()}`,
			style: {
				width: iconSize,
				height: iconSize,
			},
			resizeMode: 'contain',
			svg: {
				content: statusMode.getIcon(),
			},
		});

		const outlineIcon = View(
			{
				style: {
					width: backgroundSize,
					height: backgroundSize,
					alignItems: 'center',
					justifyContent: 'center',
					borderRadius: Component.elementAccentCorner.toNumber(),
					backgroundColor: Color.resolve(backgroundColor, Color.bgContentPrimary).toHex(),
				},
			},
			icon,
		);

		return outline ? outlineIcon : icon;
	}

	BadgeStatus.defaultProps = {
		outline: false,
	};

	BadgeStatus.propTypes = {
		testId: PropTypes.string.isRequired,
		mode: PropTypes.object.isRequired,
		outline: PropTypes.bool,
		size: PropTypes.object,
		backgroundColor: PropTypes.object,
	};

	module.exports = { BadgeStatus, BadgeStatusMode, BadgeStatusSize };
});
