/**
 * @module layout/ui/stage-slider/item
 */
jn.define('layout/ui/stage-slider/item', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { Text5, Text7 } = require('ui-system/typography');
	const { isLightColor } = require('utils/color');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');

	const STAGE_WIDTH = Math.round(device.screen.width * 0.48);
	const STAGE_HEIGHT = 32;
	const STAGE_MARGIN = Indent.M.toNumber();
	const REVERSED_ARROW_WIDTH = 17;
	const ARROW_WIDTH = 28;

	/**
	 * @class StageItemClass
	 */
	class StageItemClass extends LayoutComponent
	{
		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		get shouldShowMenu()
		{
			return BX.prop.getBoolean(this.props, 'showMenu', false);
		}

		get shouldAnimateOnLoading()
		{
			return BX.prop.getBoolean(this.props, 'shouldAnimateOnLoading', false);
		}

		get initialOpacity()
		{
			return BX.prop.getNumber(this.props, 'initialOpacity', null);
		}

		constructor(props)
		{
			super(props);
			this.animationTick = 0;
		}

		animateOnLoading(opacity)
		{
			if (this.stageRef && this.shouldAnimateOnLoading && this.animationTick < 6)
			{
				const nextOpacity = opacity === 1 ? 0.3 : 1;
				this.stageRef.animate({
					opacity,
					duration: 1000,
				}, () => {
					this.animationTick++;
					this.animateOnLoading(nextOpacity);
				});
			}
		}

		render()
		{
			const { stage = {}, index, activeIndex, onStageClick, onStageLongClick } = this.props;
			const { color } = stage;

			const preparedColor = this.prepareColor(color);
			const backgroundColor = index > activeIndex ? Color.bgContentTertiary.toHex() : preparedColor;

			return View(
				{
					testId: this.getTestId(index, activeIndex),
					ref: (ref) => this.stageRef = ref,
					style: {
						flexDirection: 'row',
						width: STAGE_WIDTH,
						opacity: this.initialOpacity ?? (this.isUnsuitable() ? 0.3 : 1),
						marginRight: STAGE_MARGIN,
					},
					onClick: onStageClick && (() => onStageClick(stage)),
					onLongClick: onStageLongClick && (() => onStageLongClick(stage)),
					onLayout: () => this.animateOnLoading(this.initialOpacity),
				},
				this.renderLeftArrow(backgroundColor, preparedColor),
				this.renderBody(backgroundColor, preparedColor),
				this.renderRightArrow(backgroundColor, preparedColor, this.getTextColor(backgroundColor, index, activeIndex)),
			);
		}

		/**
		 * @param {string} backgroundColor
		 * @param {string} borderColor
		 * @return {object}
		 */
		renderLeftArrow(backgroundColor, borderColor)
		{
			return this.isReversed && Image(
				{
					style: {
						width: REVERSED_ARROW_WIDTH,
						height: STAGE_HEIGHT,
						marginRight: -1,
					},
					resizeMode: 'contain',
					svg: {
						content: svgImages.reversedArrow(backgroundColor, borderColor),
					},
				},
			);
		}

		/**
		 * @param {string} backgroundColor
		 * @param {string} borderColor
		 * @param {Color} menuIconColor
		 * @return {false|BaseMethods}
		 */
		renderRightArrow(backgroundColor, borderColor, menuIconColor)
		{
			return !this.isReversed && View(
				{
					style: {
						width: ARROW_WIDTH,
						height: STAGE_HEIGHT,
						justifyContent: 'center',
						paddingLeft: 3,
						marginLeft: -1,
					},
				},
				Image(
					{
						style: {
							width: ARROW_WIDTH,
							height: STAGE_HEIGHT,
							resizeMode: 'contain',
							position: 'absolute',
							top: 0,
							left: 0,
						},
						svg: {
							content: svgImages.arrow(backgroundColor, borderColor),
						},
					},
				),
				this.renderMenu(backgroundColor, menuIconColor),
			);
		}

		/**
		 * @param {string} backgroundColor
		 * @param {string} borderColor
		 * @return {BaseMethods}
		 */
		renderBody(backgroundColor, borderColor)
		{
			const { stage = {}, index, activeIndex } = this.props;
			const { name, description, sum, count } = stage;
			const textColor = this.getTextColor(backgroundColor, index, activeIndex);

			return View(
				{
					style: {
						width: this.isReversed
							? STAGE_WIDTH - REVERSED_ARROW_WIDTH + 1
							: STAGE_WIDTH - ARROW_WIDTH + 1, // +1 to overlay icon and body (animation fix)
						height: STAGE_HEIGHT,
						backgroundColor: index > activeIndex ? Color.bgContentTertiary.toHex() : backgroundColor,
						flexDirection: 'row',
						alignItems: 'center',

						paddingLeft: this.isReversed ? 0 : Indent.M.toNumber(),
						paddingRight: this.isReversed ? Indent.XS.toNumber() : 0,

						borderBottomWidth: 2,
						borderBottomColor: borderColor,

						borderBottomRightRadius: this.isReversed ? Corner.XS.toNumber() : 0,
						borderTopRightRadius: this.isReversed ? Corner.XS.toNumber() : 0,
						borderBottomLeftRadius: this.isReversed ? 0 : Corner.XS.toNumber(),
						borderTopLeftRadius: this.isReversed ? 0 : Corner.XS.toNumber(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							alignItems: 'flex-start',
							flex: 2,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						name && Text5({
							text: name,
							color: textColor,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
						sum && Text7({
							text: sum,
							color: textColor,
							style: {
								opacity: 0.5,
								marginLeft: Indent.XS2.toNumber(),
							},
						}),
					),
					description && Text7({
						text: description,
						color: textColor,
						style: {
							opacity: 0.5,
							marginTop: Application.getPlatform() === 'ios' ? -1 : -2,
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				count && BadgeCounter({
					value: count,
					design: BadgeCounterDesign.ALERT,
					style: {
						marginRight: Indent.XS2.toNumber(),
					},
				}),
				this.isReversed && this.renderMenu(backgroundColor, textColor),
			);
		}

		renderMenu(backgroundColor, menuIconColor)
		{
			return this.shouldShowMenu && IconView({
				icon: Icon.CHEVRON_DOWN_SIZE_S,
				color: menuIconColor,
				size: 20,
				style: {
					opacity: 0.8,
				},
			});
		}

		isUnsuitable()
		{
			return false;
		}

		/**
		 * @param {string} color
		 * @return {string}
		 */
		prepareColor(color)
		{
			if (color && color.length === 6)
			{
				return `#${color}`;
			}

			return color;
		}

		/**
		 * @param {number} index
		 * @param {number} activeIndex
		 * @return {string}
		 */
		getTestId(index, activeIndex)
		{
			if (index === activeIndex)
			{
				return 'CURRENT_STAGE';
			}

			const diff = Math.abs(index - activeIndex);
			const postfix = diff > 1 ? `_${diff}` : '';

			return index > activeIndex ? `NEXT_STAGE${postfix}` : `PREV_STAGE${postfix}`;
		}

		/**
		 * @param {string} backgroundColor
		 * @param {number} index
		 * @param {number} activeIndex
		 * @return {Color}
		 */
		getTextColor(backgroundColor, index, activeIndex)
		{
			const textContrastColor = isLightColor(backgroundColor) ? Color.baseBlackFixed : Color.baseWhiteFixed;

			return index > activeIndex ? Color.base4 : textContrastColor;
		}
	}

	const svgImages = {
		arrow: (
			backgroundColor,
			borderColor,
		) => `<svg width="28" height="32" viewBox="0 0 28 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H17.9656C19.4941 0 20.8889 0.870997 21.5597 2.24434L27.1722 13.7338C27.6964 14.807 27.7136 16.0583 27.2192 17.1455L21.5295 29.656C20.8802 31.0835 19.4566 32 17.8883 32H0V0Z" fill="${backgroundColor}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18.1805 31.9894C18.0838 31.9964 17.9864 32 17.8883 32L0 32V30H21.3525C20.6917 31.1453 19.5033 31.8928 18.1805 31.9894Z" fill="${borderColor}"/></svg>`,
		reversedArrow: (
			backgroundColor,
			borderColor,
		) => `<svg width="17" height="32" viewBox="0 0 17 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.42081 2.30226C7.07938 0.897331 8.49102 0 10.0426 0L17 0V32H10.0426C8.49102 32 7.07938 31.1027 6.42081 29.6977L0.795814 17.6977C0.291595 16.6221 0.291595 15.3779 0.795814 14.3023L6.42081 2.30226Z" fill="${backgroundColor}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17 30V32H10.074C8.63691 32 7.31969 31.2303 6.60982 30H17Z" fill="${borderColor}"/></svg>`,
	};

	module.exports = {
		StageItemClass,
		STAGE_HEIGHT,
		STAGE_WIDTH,
		STAGE_MARGIN,
	};
});
