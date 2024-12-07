/**
 * @module ui-system/blocks/stage-selector
 */
jn.define('ui-system/blocks/stage-selector', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Type } = require('type');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text3, Text5 } = require('ui-system/typography/text');
	const { IconView, Icon, iconTypes } = require('ui-system/blocks/icon');

	/**
	 * @function StageSelector
	 * @param {string} [props.testId=null]
	 * @param {string|object} [props.title]
	 * @param {string|object} [props.subtitle='']
	 * @param {?number} [props.numberOfLinesTitle=1]
	 * @param {?number} [props.numberOfLinesSubtitle=null]
	 * @param {string} [props.leftIcon=null]
	 * @param {Color} [props.leftIconColor=Color.base2]
	 * @param {string} [props.extraIcon=null]
	 * @param {Icon} [props.rightIcon=Icon.CHEVRON_DOWN]
	 * @param {Color} [props.rightIconColor=Color.base2]
	 * @param {CardDesign} [props.cardDesign=CardDesign.PRIMARY]
	 * @param {boolean} [props.cardBorder=false]
	 * @return StageSelector
	 */
	function StageSelector(props = {})
	{
		PropTypes.validate(StageSelector.propTypes, props, 'StageSelector');

		const preparedProps = { ...StageSelector.defaultProps, ...props };

		const {
			testId,
			title,
			subtitle,
			numberOfLinesTitle,
			numberOfLinesSubtitle,
			leftIcon,
			leftIconColor,
			extraIcon,
			rightIcon,
			rightIconColor,
			cardDesign,
			cardBorder,
			...restProps
		} = preparedProps;

		const titleView = Type.isStringFilled(title) ? Text3({
			text: title,
			color: Color.base1,
			numberOfLines: numberOfLinesTitle,
			ellipsize: 'end',
		}) : title;

		const subtitleView = Type.isStringFilled(subtitle) ? Text5({
			text: subtitle,
			color: Color.base4,
			numberOfLines: numberOfLinesSubtitle,
			ellipsize: 'end',
		}) : subtitle;

		return Card(
			{
				testId,
				design: cardDesign,
				border: cardBorder,
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				...restProps,
			},
			leftIcon && IconView({
				icon: leftIcon,
				size: 28,
				color: leftIconColor,
				style: {
					marginRight: Indent.XL.toNumber(),
				},
			}),
			View(
				{
					style: {
						flex: 1,
						marginRight: Indent.XL.toNumber(),
					},
				},
				titleView,
				subtitle && subtitleView,
			),
			extraIcon && IconView({
				icon: extraIcon,
				size: 24,
				color: Color.base4,
				style: {
					marginRight: Indent.XL.toNumber(),
				},
			}),
			rightIcon && IconView({
				icon: rightIcon,
				size: 24,
				color: rightIconColor,
			}),
		);
	}

	StageSelector.defaultProps = {
		testId: null,
		subtitle: '',
		numberOfLinesTitle: 1,
		numberOfLinesSubtitle: null,
		leftIcon: null,
		leftIconColor: Color.base2,
		extraIcon: null,
		rightIcon: Icon.CHEVRON_DOWN,
		rightIconColor: Color.base4,
		cardDesign: CardDesign.PRIMARY,
		cardBorder: false,
	};

	StageSelector.propTypes = {
		testId: PropTypes.string,
		title: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
		subtitle: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
		numberOfLinesTitle: PropTypes.number,
		numberOfLinesSubtitle: PropTypes.number,
		leftIcon: PropTypes.object,
		leftIconColor: PropTypes.object,
		extraIcon: PropTypes.object,
		rightIcon: PropTypes.object,
		rightIconColor: PropTypes.object,
		cardDesign: PropTypes.object,
		cardBorder: PropTypes.bool,
	};

	module.exports = { StageSelector, CardDesign, Icon, iconTypes };
});
