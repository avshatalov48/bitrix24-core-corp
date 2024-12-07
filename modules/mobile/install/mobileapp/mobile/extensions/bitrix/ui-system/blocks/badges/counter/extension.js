/**
 * @module ui-system/blocks/badges/counter
 */
jn.define('ui-system/blocks/badges/counter', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Component, Indent } = require('tokens');
	const { Type } = require('type');
	const { mergeImmutable } = require('utils/object');
	const { Text5 } = require('ui-system/typography/text');
	const { BadgeCounterDesign } = require('ui-system/blocks/badges/counter/src/design-enum');

	const MAX_COUNTS = 99;

	/**
	 * @typedef {Object} BadgeCounterProps
	 * @property {number} testId
	 * @property {number | string} [value=0]
	 * @property {boolean} [showRawValue]
	 * @property {BadgeCounterDesign} design=BadgeCounterDesign.SUCCESS
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
			...restProps
		} = { ...BadgeCounter.defaultProps, ...props };

		if (!BadgeCounterDesign.has(design))
		{
			console.warn('BadgeCounterDesign: counter design not selected');

			return null;
		}

		let badgeText = Type.isNil(value) || Number.isNaN(value) ? 0 : value;

		if (Type.isNumber(Number(badgeText)) && !showRawValue)
		{
			badgeText = badgeText > MAX_COUNTS ? `${MAX_COUNTS}+` : badgeText;
		}

		const viewProps = mergeImmutable({
			testId: `${testId}_${design.getName()}`,
			style: {
				height: 18,
				flexShrink: 1,
				alignItems: 'flex-start',
			},
		}, restProps);

		return View(
			viewProps,
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingHorizontal: Indent.S.toNumber(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: design.getBackgroundColor().toHex(),
					},
				},
				Text5({
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
		value: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
		testId: PropTypes.string.isRequired,
		showRawValue: PropTypes.bool,
		design: PropTypes.instanceOf(BadgeCounterDesign),
	};

	module.exports = { BadgeCounter, BadgeCounterDesign };
});
