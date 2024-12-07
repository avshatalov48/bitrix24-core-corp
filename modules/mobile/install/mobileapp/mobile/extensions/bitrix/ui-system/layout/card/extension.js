/**
 * @module ui-system/layout/card
 */
jn.define('ui-system/layout/card', (require, exports, module) => {
	const { Component, Indent, Color } = require('tokens');
	const { CardDesign } = require('ui-system/layout/card/src/card-design-enum');
	const { BadgeStatus, BadgeStatusMode } = require('ui-system/blocks/badges/status');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @function Card
	 * @param {object} props
	 * @param {string} props.testId
	 * @param {object=} [props.style={}]
	 * @param {object=} [props.excludePaddingSide={}]
	 * @param {boolean} [props.excludePaddingSide.left=false]
	 * @param {boolean} [props.excludePaddingSide.right=false]
	 * @param {boolean} [props.excludePaddingSide.top=false]
	 * @param {boolean} [props.excludePaddingSide.bottom=false]
	 * @param {boolean} [props.excludePaddingSide.horizontal=false]
	 * @param {boolean} [props.excludePaddingSide.vertical=false]
	 * @param {boolean} [props.excludePaddingSide.all]
	 * @param {boolean} [props.hideCross=true]
	 * @param {boolean} [props.selected=true]
	 * @param {boolean} [props.border=false]
	 * @param {BadgeStatusMode} [props.badgeMode=null]
	 * @param {function} [props.onClose=null]
	 * @param {function} [props.onClick=null]
	 * @param {CardDesign} [props.design=CardDesign.PRIMARY]
	 * @param children
	 * @return Card
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
			onClick = null,
			onClose = null,
			...restProps
		} = props;

		const { left, right, top, bottom, horizontal, vertical, all } = excludePaddingSide;

		const paddingLeft = left || all || horizontal ? 0 : Component.cardPaddingLr.toNumber();
		const paddingRight = right || all || horizontal ? 0 : Component.cardPaddingLr.toNumber();
		const paddingTop = top || all || vertical ? 0 : Component.cardPaddingT.toNumber();
		const paddingBottom = bottom || all || vertical ? 0 : Component.cardPaddingB.toNumber();

		const cardStyle = {
			borderRadius: Component.cardCorner.toNumber(),
			...CardDesign.resolve(design, CardDesign.PRIMARY).getStyle(),
		};

		if (border)
		{
			cardStyle.borderWidth = 1;
			cardStyle.borderColor = (selected ? Color.base3 : Color.bgSeparatorPrimary).toHex();
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
		excludePaddingSide: {},
		onClose: null,
		onClick: null,
	};

	Card.propTypes = {
		testId: PropTypes.string.isRequired,
		badgeMode: PropTypes.object,
		hideCross: PropTypes.bool,
		selected: PropTypes.bool,
		border: PropTypes.bool,
		design: PropTypes.object,
		excludePaddingSide: PropTypes.objectOf(PropTypes.bool),
		onClose: PropTypes.func,
		onClick: PropTypes.func,
	};

	module.exports = { Card, CardDesign, BadgeStatusMode };
});
