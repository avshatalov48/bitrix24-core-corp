/**
 * @module layout/ui/stage-list/item/step
 */
jn.define('layout/ui/stage-list/item/step', (require, exports, module) => {
	const { isLightColor } = require('utils/color');
	const { Color, Indent, Corner } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { MoneyView } = require('layout/ui/money');
	const { Money } = require('money');
	const { Text4, Text5, Text6 } = require('ui-system/typography/text');

	const FOCUS_HEIGHT = 53;
	const STAGE_HEIGHT = 44;

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class StageStep
	 */
	class StageStep extends LayoutComponent
	{
		get counter()
		{
			return BX.prop.getObject(this.props, 'counter', {});
		}

		render()
		{
			const {
				unsuitable,
				stage = {},
				active,
			} = this.props;

			const {
				listMode: stageListMode,
			} = stage;

			return View(
				{
					style: {
						height: active || stageListMode ? FOCUS_HEIGHT : STAGE_HEIGHT,
						opacity: unsuitable ? 0.3 : 1,
					},
				},
				this.renderFocus(),
				stageListMode ? this.renderListView() : this.renderStep(stage),
			);
		}

		renderFocus()
		{
			const {
				isReversed,
				active,
			} = this.props;

			if (!active)
			{
				return null;
			}

			const borderStyles = {
				borderColor: Color.bgContentPrimary.toHex(),
				borderBottomWidth: 1,
				borderBottomColor: Color.accentMainPrimary.toHex(),
				borderTopWidth: 1,
				borderTopColor: Color.accentMainPrimary.toHex(),
			};

			if (isReversed)
			{
				borderStyles.borderRightColor = Color.accentMainPrimary.toHex();
				borderStyles.borderRightWidth = 1;
			}
			else
			{
				borderStyles.borderLeftColor = Color.accentMainPrimary.toHex();
				borderStyles.borderLeftWidth = 1;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
						position: 'absolute',
						width: '100%',
					},
				},
				isReversed && Image(
					{
						svg: {
							content: focusArrowReversed(Color.accentMainPrimary.toHex()),
						},
						style: {
							width: 19,
							height: FOCUS_HEIGHT,
							marginRight: isAndroid ? -2 : 0,
						},
					},
				),
				View(
					{
						style: {
							height: FOCUS_HEIGHT,
							flex: 1,
							borderBottomLeftRadius: isReversed ? 0 : 14,
							borderTopLeftRadius: isReversed ? 0 : 14,
							borderBottomRightRadius: isReversed ? 14 : 0,
							borderTopRightRadius: isReversed ? 14 : 0,

							...borderStyles,
						},
					},

				),
				!isReversed && Image({
					svg: {
						content: focusArrow(Color.accentMainPrimary.toHex()),
					},
					style: {
						width: 19,
						height: FOCUS_HEIGHT,
						marginLeft: isAndroid ? -2 : 0,
					},
				}),
			);
		}

		/**
		 * @param {object} stage
		 * @param {?object} offset
		 * @param {?boolean} showContent
		 * @return {BaseMethods}
		 */
		renderStep(stage, offset, showContent = true)
		{
			const {
				disabled,
				showTotal,
				isReversed,
				active,
			} = this.props;

			const {
				id: stageId,
				name: stageName,
				color: stageColor,
				borderColor: stageBorderColor,
			} = stage;

			const {
				total: stageTotal,
				currency: stageCurrency,
			} = this.counter;

			const preparedColor = disabled ? Color.bgContentSecondary.toHex() : this.prepareColor(stageColor);
			const borderColor = stageBorderColor ?? preparedColor;
			const textColor = this.getTextColor(preparedColor);

			const offsetStyles = offset ? {
				position: 'absolute',
				top: offset.top,
				left: offset.left,
				right: offset.right,
			} : {};
			const borderStyles = {
				borderColor: preparedColor,
				borderBottomWidth: 1,
				borderBottomColor: borderColor,
				borderTopWidth: 1,
				borderTopColor: borderColor,
			};

			if (isReversed)
			{
				borderStyles.borderRightColor = borderColor;
				borderStyles.borderRightWidth = 1;
			}
			else
			{
				borderStyles.borderLeftColor = borderColor;
				borderStyles.borderLeftWidth = 1;
			}

			return View(
				{
					style: {
						height: STAGE_HEIGHT,
						flex: 1,
						margin: !offset && active && Indent.XS.toNumber(),
						flexDirection: 'row',
						...offsetStyles,
					},
				},
				isReversed && Image(
					{
						svg: {
							content: arrowReversed(preparedColor, borderColor),
						},
						style: {
							width: 28,
							height: 44,
							marginRight: -1,
						},
					},
				),
				View(
					{
						style: {
							height: STAGE_HEIGHT,
							flex: 1,
							backgroundColor: preparedColor,
							borderBottomLeftRadius: isReversed ? 0 : Corner.M.toNumber(),
							borderTopLeftRadius: isReversed ? 0 : Corner.M.toNumber(),
							borderBottomRightRadius: isReversed ? Corner.M.toNumber() : 0,
							borderTopRightRadius: isReversed ? Corner.M.toNumber() : 0,

							...borderStyles,

							justifyContent: 'center',
						},
					},
					showContent && this.renderContent(
						{
							id: stageId,
							name: stageName,
							color: preparedColor,
							disabled,
						},
						{
							amount: Math.round(stageTotal),
							currency: stageCurrency,
						},
						showTotal,
					),
				),
				!isReversed && View(
					{
						style: {
							width: 46,
							height: 44,
							alignItems: 'center',
							justifyContent: 'center',
							marginLeft: -1,
						},
					},
					Image({
						svg: {
							content: arrow(preparedColor, borderColor),
						},
						style: {
							width: 46,
							height: 44,
							position: 'absolute',
							top: 0,
							left: 0,
						},
					}),
					this.renderMenu(textColor),
				),
			);
		}

		renderMenu(menuIconColor)
		{
			return this.props.showArrow && IconView({
				icon: Icon.CHEVRON_DOWN,
				color: menuIconColor,
				size: 20,
				style: {
					opacity: 0.8,
				},
			});
		}

		/**
		 * @param {Object} stage
		 * @param {Object} money
		 * @param {Boolean} isShowTotal
		 * @returns {Object}
		 */
		renderContent(stage = {}, money = {}, isShowTotal = false)
		{
			const { disabled, color } = stage;
			const { isReversed, showAllStagesItem } = this.props;
			const textColor = disabled ? Color.base4 : this.getTextColor(color);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingLeft: isReversed ? 0 : Indent.XL3.toNumber(),
						paddingRight: isReversed ? Indent.XL3.toNumber() : 0,
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 2,
							justifyContent: 'center',
						},
					},
					this.renderTitle(textColor, stage),
					(isShowTotal && showAllStagesItem) && this.renderTotal(money, textColor),
				),
				isReversed && this.renderMenu(textColor),
			);
		}

		renderTitle(textColor, stage = {})
		{
			const {
				showCount,
			} = this.props;

			const {
				id: stageId,
				name: stageName,
			} = stage;

			const {
				count: stageCount,
			} = this.counter;

			return stageName && View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text4(
					{
						testId: `Stage-${stageId}-Name`,
						text: stageName,
						color: textColor,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							flexShrink: 2,
							maxWidth: '100%',
						},
					},
				),
				showCount && Text5(
					{
						testId: `Stage-${stageId}-Counter`,
						text: ` (${stageCount || 0})`,
						color: textColor,
						numberOfLines: 1,
						ellipsize: 'end',
					},
				),
			);
		}

		renderTotal(money, textColor)
		{
			return MoneyView({
				money: Money.create(money),
				renderAmount: (formattedAmount) => Text6({
					text: formattedAmount,
					color: textColor,
				}),
				renderCurrency: (formattedCurrency) => Text6({
					text: formattedCurrency,
					color: textColor,
				}),
			});
		}

		/**
		 * @param {string} color
		 * @return {string}
		 */
		prepareColor(color)
		{
			if (!color)
			{
				return Color.accentSoftBlue1.toHex();
			}

			if (color && color.length === 6)
			{
				return `#${color}`;
			}

			return color;
		}

		getTextColor(backgroundColor)
		{
			return isLightColor(backgroundColor) ? Color.baseBlackFixed : Color.baseWhiteFixed;
		}

		renderListView()
		{
			const {
				stage: {
					id: stageId,
					name,
				},
			} = this.props;

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
					},
				},
				this.renderStep(
					{
						id: stageId,
						color: Color.bgContentPrimary.toHex(),
						borderColor: Color.base5.toHex(),
					},
					{
						top: 6,
						left: 8,
						right: 4,
					},
					false,
				),
				this.renderStep(
					{
						id: stageId,
						color: Color.bgContentPrimary.toHex(),
						borderColor: Color.base5.toHex(),
					},
					{
						top: 4,
						left: 6,
						right: 6,
					},
					false,
				),
				this.renderStep(
					{
						id: stageId,
						name,
						color: Color.bgContentPrimary.toHex(),
						borderColor: Color.base5.toHex(),
					},
					{
						top: 2,
						left: 4,
						right: 8,
					},
				),
			);
		}
	}

	const focusArrow = (color) => {
		return `<svg width="16" height="53" viewBox="0 0 16 53" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 52H0.225449C4.01337 52 7.39586 49.6282 8.68672 46.067L14.3035 30.5717C15.0333 28.5583 15.0212 26.3506 14.2695 24.3453L7.70714 6.84069C6.47498 3.55397 3.45621 1.30496 0 1.0287L0 0.0258177C3.87361 0.30422 7.26493 2.8124 8.64349 6.48966L15.2058 23.9943C16.0411 26.2224 16.0545 28.6754 15.2436 30.9125L9.62686 46.4078C8.19258 50.3647 4.43425 53 0.225449 53H0L0 52Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
	};

	const focusArrowReversed = (color) => {
		return `<svg width="16" height="53" viewBox="0 0 16 53" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16 1H15.7746C11.9866 1 8.60414 3.37176 7.31328 6.93295L1.69652 22.4283C0.966705 24.4417 0.97876 26.6494 1.73053 28.6547L8.29286 46.1593C9.52502 49.446 12.5438 51.695 16 51.9713L16 52.9742C12.1264 52.6958 8.73507 50.1876 7.35651 46.5103L0.794174 29.0057C-0.0411377 26.7776 -0.0545349 24.3246 0.756378 22.0875L6.37314 6.59217C7.80742 2.63529 11.5658 -1.94455e-07 15.7746 -1.94455e-07H16L16 1Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
	};

	const arrow = (color, borderColor) => {
		return `<svg width="46" height="44" viewBox="0 0 46 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M39.2751 33.7919C36.4868 38.7709 35.0927 41.2603 32.7587 42.6302C30.4247 44 27.5772 44 21.8821 44H0V22V0H21.8821C27.5772 0 30.4247 0 32.7587 1.36983C35.0927 2.73966 36.4868 5.22913 39.2751 10.2081L42.5886 16.1248C44.1962 18.9954 45 20.4307 45 22C45 23.5693 44.1962 25.0046 42.5886 27.8752L39.2751 33.7919Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.0003e-06 43H21.9399C24.8146 43 26.8981 42.9989 28.5572 42.8337C30.1943 42.6707 31.3377 42.3539 32.34 41.7672C33.3424 41.1804 34.1785 40.3385 35.1221 38.9909C36.0784 37.6251 37.0995 35.809 38.5069 33.3023L41.8292 27.3856C42.6444 25.9337 43.2204 24.9056 43.5994 24.0461C43.9698 23.2063 44.1189 22.5975 44.1189 22C44.1189 21.4025 43.9698 20.7937 43.5994 19.9539C43.2204 19.0944 42.6444 18.0663 41.8292 16.6144L38.5069 10.6977C37.0995 8.19104 36.0784 6.37494 35.1221 5.00909C34.1785 3.66146 33.3424 2.81961 32.34 2.23285C31.3377 1.64609 30.1943 1.32928 28.5572 1.16631C26.8981 1.00114 24.8146 1 21.9399 1H9.0003e-06V43ZM32.8452 42.6302C35.1854 41.2603 36.5832 38.7709 39.3789 33.7919L42.7011 27.8752C44.313 25.0046 45.1189 23.5693 45.1189 22C45.1189 20.4307 44.313 18.9954 42.7011 16.1248L39.3789 10.2081C36.5832 5.22913 35.1854 2.73966 32.8452 1.36983C30.5051 0 27.65 0 21.9399 0H0L9.0003e-06 44H21.9399C27.65 44 30.5051 44 32.8452 42.6302Z" fill="${borderColor.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
	};

	const arrowReversed = (color, borderColor) => {
		return `<svg width="28" height="44" viewBox="0 0 28 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.84378 10.2081C8.63207 5.22913 10.0262 2.73966 12.3602 1.36983C14.6942 1.90735e-06 17.5417 0 23.2368 0H28L28 22V44H23.2368C17.5417 44 14.6942 44 12.3602 42.6302C10.0262 41.2603 8.63207 38.7709 5.84378 33.7919L2.53028 27.8752C0.922693 25.0046 0.1189 23.5693 0.1189 22C0.1189 20.4307 0.922693 18.9954 2.53028 16.1248L5.84378 10.2081Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M28 1H23.179C20.3043 1 18.2208 1.00114 16.5617 1.16631C14.9246 1.32928 13.7812 1.64609 12.7789 2.23285C11.7765 2.81961 10.9404 3.66146 9.99683 5.00909C9.04049 6.37495 8.01944 8.19104 6.61198 10.6977L3.28973 16.6144C2.4745 18.0663 1.89846 19.0944 1.51946 19.9539C1.14913 20.7937 1.00003 21.4025 1.00003 22C1.00003 22.5975 1.14913 23.2063 1.51946 24.0461C1.89846 24.9056 2.4745 25.9337 3.28973 27.3856L6.61198 33.3023C8.01944 35.809 9.04049 37.6251 9.99683 38.9909C10.9404 40.3385 11.7765 41.1804 12.7789 41.7672C13.7812 42.3539 14.9246 42.6707 16.5617 42.8337C18.2208 42.9989 20.3043 43 23.179 43H28V1ZM12.2737 1.36983C9.93353 2.73966 8.53569 5.22913 5.74004 10.2081L2.41778 16.1248C0.805946 18.9954 3.05176e-05 20.4307 3.05176e-05 22C3.05176e-05 23.5693 0.805946 25.0046 2.41778 27.8752L5.74004 33.7919C8.53569 38.7709 9.93353 41.2603 12.2737 42.6302C14.6138 44 17.4689 44 23.179 44H28V0H23.179C17.4689 0 14.6138 0 12.2737 1.36983Z" fill="${borderColor.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
	};

	module.exports = { StageStep };
});
