/**
 * @module ui-system/layout/card
 */
jn.define('ui-system/layout/card', (require, exports, module) => {
	const { Component, Indent, Color } = require('tokens');
	const { CardDesign } = require('ui-system/layout/card/src/card-design-enum');
	const { BadgeStatus, BadgeStatusMode } = require('ui-system/blocks/badges/status');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @typedef {Object} CardProps
	 * @property {string} testId
	 * @property {Object} [style={}]
	 * @property {Object} [excludePaddingSide={}]
	 * @property {boolean} [excludePaddingSide.left=false]
	 * @property {boolean} [excludePaddingSide.right=false]
	 * @property {boolean} [excludePaddingSide.top=false]
	 * @property {boolean} [excludePaddingSide.bottom=false]
	 * @property {boolean} [excludePaddingSide.horizontal=false]
	 * @property {boolean} [excludePaddingSide.vertical=false]
	 * @property {boolean} [excludePaddingSide.all]
	 * @property {boolean} [hideCross=true]
	 * @property {boolean} [selected=true]
	 * @property {boolean} [accent=false]
	 * @property {boolean} [border=false]
	 * @property {BadgeStatusMode} [badgeMode=null]
	 * @property {function} [onClose=null]
	 * @property {function} [onClick=null]
	 * @property {CardDesign} [design=CardDesign.PRIMARY]
	 *
	 * @function Card
	 * @param {CardProps} props
	 * @param {Array} children
	 */
	function Card(props = {}, ...children)
	{
		PropTypes.validate(Card.propTypes, props, 'Card');

		const {
			testId,
			excludePaddingSide = {},
			style = {},
			border = false,
			design = CardDesign.PRIMARY,
			badgeMode = null,
			hideCross = true,
			selected = false,
			accent = false,
			onClick = null,
			onClose = null,
			...restProps
		} = props;

		const { left, right, top, bottom, horizontal, vertical, all } = excludePaddingSide;

		const paddingLeft = left || all || horizontal ? 0 : Component.cardPaddingLr.toNumber();
		const paddingRight = right || all || horizontal ? 0 : Component.cardPaddingLr.toNumber();
		const paddingTop = top || all || vertical ? 0 : Component.cardPaddingT.toNumber();
		const paddingBottom = bottom || all || vertical ? 0 : Component.cardPaddingB.toNumber();

		const {
			backgroundColor: designBackgroundColor,
			accentColor: designAccentColor,
		} = CardDesign.resolve(design, CardDesign.PRIMARY).getValue();

		const cardStyle = {
			borderRadius: Component.cardCorner.toNumber(),
			backgroundColor: designBackgroundColor.toHex(),
		};

		if (border)
		{
			cardStyle.borderWidth = 1;
			cardStyle.borderColor = Color.bgSeparatorPrimary.toHex();
		}

		if (selected)
		{
			cardStyle.borderWidth = 1;
			cardStyle.borderColor = Color.base3.toHex();
		}

		if (accent)
		{
			cardStyle.borderWidth = 2;
			cardStyle.borderColor = designAccentColor?.toHex();
		}

		const status = BadgeStatusMode.has(badgeMode)
			? BadgeStatus({ testId, mode: badgeMode })
			: null;

		const crossIcon = hideCross
			? null
			: IconView({
				testId: testId ? `${testId}_close` : null,
				icon: Icon.CROSS,
				color: Color.base1,
				opacity: 0.3,
				size: 20,
			});

		const topRightView = View(
			{
				style: {
					position: 'absolute',
					top: Indent.S.toNumber(),
					right: Indent.S.toNumber(),
				},
				onClick: () => {
					if (onClose && !hideCross && !status)
					{
						onClose();
					}
				},
			},
			status || crossIcon,
		);

		return View(
			{
				...restProps,
				testId,
				onClick: () => {
					if (onClick)
					{
						onClick();
					}
				},
				style: {
					position: 'relative',
					paddingLeft,
					paddingRight,
					paddingTop,
					paddingBottom,
					...style,
					...cardStyle,
				},
			},
			...children,
			topRightView,
		);
	}

	Card.defaultProps = {
		badgeMode: null,
		hideCross: true,
		selected: true,
		border: false,
		accent: false,
		excludePaddingSide: {},
		onClose: null,
		onClick: null,
	};

	Card.propTypes = {
		testId: PropTypes.string.isRequired,
		hideCross: PropTypes.bool,
		selected: PropTypes.bool,
		border: PropTypes.bool,
		design: PropTypes.instanceOf(CardDesign),
		badgeMode: PropTypes.instanceOf(BadgeStatusMode),
		excludePaddingSide: PropTypes.objectOf(PropTypes.bool),
		onClose: PropTypes.func,
		onClick: PropTypes.func,
	};

	module.exports = {
		Card,
		CardDesign,
		BadgeStatusMode,
	};
});
