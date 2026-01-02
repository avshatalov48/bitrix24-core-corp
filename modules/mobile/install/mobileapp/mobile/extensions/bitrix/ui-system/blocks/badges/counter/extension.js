/**
 * @module ui-system/blocks/badges/counter
 */
jn.define('ui-system/blocks/badges/counter', (require, exports, module) => {
	const { Type } = require('type');
	const { Component } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { BadgeCounterDesign } = require('ui-system/blocks/badges/counter/src/design-enum');
	const { BadgeCounterSize } = require('ui-system/blocks/badges/counter/src/size-enum');

	const MAX_COUNTS = 99;

	/**
	 * @typedef {Object} BadgeCounterProps
	 * @property {number} testId
	 * @property {number | string} [value=0]
	 * @property {boolean} [showRawValue]
	 * @property {BadgeCounterDesign} design=BadgeCounterDesign.SUCCESS
	 * @property {BadgeCounterSize} size=BadgeCounterSize.M
	 * @property {Color} [color=Color.baseWhiteFixed]
	 * @property {Color} [backgroundColor=Color.accentMainPrimary]
	 *
	 * @param {BadgeCounterProps} props
	 * @function BadgeCounter
	 */
	function BadgeCounter(props = {})
	{
		PropTypes.validate(BadgeCounter.propTypes, props, 'BadgeCounter');

		const {
			testId,
			value,
			showRawValue,
			design = BadgeCounterDesign.SUCCESS,
			size = BadgeCounterSize.M,
			...restProps
		} = { ...BadgeCounter.defaultProps, ...props };

		if (!BadgeCounterDesign.has(design))
		{
			console.warn('BadgeCounterDesign: counter design not selected');

			return null;
		}

		const isDot = size.isDot();
		const viewProps = ({ style }) => mergeImmutable({
			style,
			testId: `${testId}_${design.getName()}`,
		}, restProps);

		if (isDot)
		{
			const dotSize = size.getHeight();

			return View(
				viewProps({
					style: {
						height: dotSize,
						width: dotSize,
						backgroundColor: design.getBackgroundColor().toHex(),
						borderRadius: Component.elementAccentCorner.toNumber(),
					},
				}),
			);
		}

		let badgeText = Type.isNil(value) || Number.isNaN(value) ? 0 : value;
		if (Type.isNumber(Number(badgeText)) && !showRawValue)
		{
			badgeText = badgeText > MAX_COUNTS ? `${MAX_COUNTS}+` : badgeText;
		}

		const isRoundedBadge = value < 10 && !showRawValue;
		const Text = size.getTypography();

		return View(
			viewProps({
				style: {
					flexShrink: 1,
					alignItems: 'flex-start',
				},
			}),
			View(
				{
					style: {
						height: size.getHeight(),
						width: isRoundedBadge ? size.getHeight() : null,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						paddingHorizontal: isRoundedBadge ? null : size.getPaddingHorizontal().toNumber(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: design.getBackgroundColor().toHex(),
					},
				},
				Text({
					accent: true,
					text: String(badgeText),
					color: design.getColor(),
				}),
			),
		);
	}

	BadgeCounter.defaultProps = {
		value: 0,
		showRawValue: false,
	};

	BadgeCounter.propTypes = {
		testId: PropTypes.string.isRequired,
		showRawValue: PropTypes.bool,
		design: PropTypes.instanceOf(BadgeCounterDesign),
		size: PropTypes.instanceOf(BadgeCounterSize),
		value: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
	};

	module.exports = {
		BadgeCounter,
		BadgeCounterDesign,
		BadgeCounterSize,
	};
});
