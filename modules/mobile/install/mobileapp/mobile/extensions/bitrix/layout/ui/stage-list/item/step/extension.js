/**
 * @module layout/ui/stage-list/item/step
 */
jn.define('layout/ui/stage-list/item/step', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isLightColor } = require('utils/color');

	const DEFAULT_STAGE_BACKGROUND_COLOR = AppTheme.colors.accentSoftBlue1;
	const DISABLED_STAGE_BACKGROUND_COLOR = AppTheme.colors.bgSecondary;

	const STAGE_CONTAINER_HEIGHT = 48;
	const STAGE_SELECT_HEIGHT = 34;
	const STAGE_HEIGHT = 40;
	const LIST_MODE_HEIGHT = 55;

	class StageStep extends LayoutComponent
	{
		get isSelectView()
		{
			return BX.prop.getBoolean(this.props, 'isSelectView', false);
		}

		static calculateTextColor(baseColor)
		{
			return isLightColor(baseColor) ? AppTheme.colors.baseBlackFixed : AppTheme.colors.baseWhiteFixed;
		}

		get counter()
		{
			return BX.prop.getObject(this.props, 'counter', {});
		}

		renderStep()
		{
			const {
				stage: {
					id: stageId,
					color: stageColor,
					borderColor: stageBorderColor,
				},
				disabled,
				showTotal,
				unsuitable,
				showArrow,
				isReversed,
			} = this.props;

			const {
				total: stageTotal,
				currency: stageCurrency,
			} = this.counter;

			const preparedStageColor = stageColor || DEFAULT_STAGE_BACKGROUND_COLOR;
			const stageBackground = disabled ? DISABLED_STAGE_BACKGROUND_COLOR : preparedStageColor;
			const stageHeight = this.isSelectView ? STAGE_SELECT_HEIGHT : STAGE_HEIGHT;

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
						opacity: unsuitable ? 0.3 : 1,
					},
				},
				isReversed && !disabled && Image(
					{
						style: {
							width: 15,
							height: stageHeight,
							marginRight: -1,
						},
						svg: {
							content: this.getStageArrow(isReversed, this.isSelectView, stageBackground),
						},
					},
				),
				View(
					{
						style: {
							padding: 8,
							backgroundColor: stageBackground,
							borderColor: stageBorderColor,
							borderWidth: stageBorderColor ? 1 : 0,
							flexGrow: 2,
							height: stageHeight,
							justifyContent: 'center',
							borderTopLeftRadius: !isReversed && 6,
							borderBottomLeftRadius: !isReversed && 6,

							borderTopRightRadius: isReversed && 6,
							borderBottomRightRadius: isReversed && 6,
						},
					},
					this.renderContent(
						{
							color: preparedStageColor,
							id: stageId,
							disabled,
						},
						{
							amount: Math.round(stageTotal),
							currency: stageCurrency,
						},
						showTotal,
					),

					disabled && View({
						style: {
							position: 'absolute',
							width: '110%',
							height: 3,
							top: stageHeight - 3,
							backgroundColor: preparedStageColor,
						},
					}),
				),
				!isReversed && !disabled && Image(
					{
						style: {
							width: 15,
							height: stageHeight,
							marginLeft: -1,
						},
						svg: {
							content: this.getStageArrow(isReversed, this.isSelectView, stageBackground),
						},
					},
				),
				stageBorderColor ? Image(
					{
						style: {
							width: 16,
							height: stageHeight,
							marginLeft: -16,
						},
						svg: {
							content: svgImages.listModeStageArrow(stageBorderColor, stageBackground),
						},
					},
				) : null,
				showArrow ? Image({
					style: {
						position: 'absolute',
						width: 12,
						height: 6,
						top: stageHeight - 23,
						right: 16,
					},
					svg: {
						content: svgImages.selectArrow(),
					},
				}) : null,
			);
		}

		getStageArrow(isRevered, isSelectView, stageBackground)
		{
			if (isRevered)
			{
				if (isSelectView)
				{
					return svgImages.stageSelectArrowReversed(stageBackground);
				}

				return svgImages.stageArrowReversed(stageBackground);
			}

			if (isSelectView)
			{
				return svgImages.stageSelectArrow(stageBackground);
			}

			return svgImages.stageArrow(stageBackground);
		}

		getIsActiveFocus()
		{
			return this.props.active;
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
			const { showAllStagesItem } = this.props;
			const stageColor = disabled ? AppTheme.colors.base4 : StageStep.calculateTextColor(color);
			const content = showAllStagesItem ? [
				View(
					{
						style: {
							flexDirection: 'column',
							flexWrap: 'no-wrap',
							flex: 1,
						},
					},
					this.renderTitle(stageColor),
					isShowTotal && MoneyView({
						money: Money.create(money),
						renderAmount: (formattedAmount) => Text({
							text: formattedAmount,
							style: style.money(stageColor),
						}),
						renderCurrency: (formattedCurrency) => Text({
							text: formattedCurrency,
							style: style.money(stageColor),
						}),
					}),
				),
			] : [
				this.renderTitle(stageColor),
				this.renderMenu(stageColor),
			];

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				...content,
			);
		}

		renderTitle(stageColor)
		{
			const {
				stage: {
					id: stageId,
					name: stageName,
				},
				showAllStagesItem,
				showCount,
			} = this.props;

			const {
				count: stageCount,
			} = this.counter;

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: showAllStagesItem ? 0 : 1,
					},
				},
				Text(
					{
						testId: `Stage-${stageId}-Name`,
						text: stageName,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							fontSize: showAllStagesItem ? 13 : 15,
							fontWeight: showAllStagesItem ? '400' : '500',
							color: stageColor,
							flexShrink: 2,
							maxWidth: '100%',
						},
					},
				),
				showCount && Text(
					{
						testId: `Stage-${stageId}-Counter`,
						text: ` (${stageCount || 0})`,
						style: {
							fontSize: showAllStagesItem ? 13 : 15,
							fontWeight: showAllStagesItem ? '400' : '500',
							color: stageColor,
						},
					},
				),
			);
		}

		renderMenu(stageColor)
		{
			const { showMenu } = this.props;

			if (!showMenu)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image(
					{
						style: {
							width: 10,
							height: 6,
						},
						svg: {
							content: `<svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M8.89902 0L5.7787 3.06862L4.99259 3.82975L4.22138 3.06862L1.10107 0L0 1.08283L5 6L10 1.08283L8.89902 0Z" fill="${stageColor}"/></svg>`,
						},
					},
				),
			);
		}

		renderFocus()
		{
			if (!this.getIsActiveFocus())
			{
				return [];
			}

			const {
				stage: {
					listMode: stageListMode,
				},
				isReversed,
			} = this.props;

			const borderColor = AppTheme.colors.accentBrandBlue;

			const borderWrapperStyle = isReversed
				? {
					alignItems: 'flex-end',
					right: 0,
				}
				: {
					alignItems: 'flex-start',
					left: 0,
				};

			const borderStyle = isReversed
				? {
					borderTopRightRadius: 10,
					borderBottomRightRadius: 10,
					borderRightWidth: 2,
					borderRightColor: borderColor,
				}
				: {
					borderTopLeftRadius: 10,
					borderBottomLeftRadius: 10,
					borderLeftWidth: 2,
					borderLeftColor: borderColor,
				};

			const arrowStyle = isReversed
				? {
					left: 0,
				}
				: {
					right: 0,
				};

			return [
				Image(
					{
						style: {
							position: 'absolute',
							...arrowStyle,
							top: 0,
							width: stageListMode ? 25 : 21,
							height: stageListMode ? LIST_MODE_HEIGHT : STAGE_CONTAINER_HEIGHT,
						},
						svg: {
							content: this.getFocusArrow(isReversed, stageListMode, borderColor),
						},
					},
				),
				View(
					{
						style: {
							top: 0,
							position: 'absolute',
							width: '102%',
							...borderWrapperStyle,
						},
					},
					View({
						style: {
							height: stageListMode ? LIST_MODE_HEIGHT : STAGE_CONTAINER_HEIGHT,
							width: '95%',
							borderTopWidth: 2,
							borderTopColor: borderColor,
							borderBottomWidth: 2,
							borderBottomColor: borderColor,
							...borderStyle,
						},
					}),
				),
			];
		}

		getFocusArrow(isReversed, stageListMode, color)
		{
			if (isReversed)
			{
				if (stageListMode)
				{
					return svgImages.listModeFocusArrowReversed(color);
				}

				return svgImages.stageFocusArrowReversed(color);
			}

			if (stageListMode)
			{
				return svgImages.listModeStageFocus(color);
			}

			return svgImages.stageFocusArrow(color);
		}

		render()
		{
			const {
				stage: {
					listMode: stageListMode,
				},
				isReversed,
			} = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 2,
						height: this.stepHeight,
						padding: BX.type.isBoolean(this.getIsActiveFocus()) ? 4 : 0,
						paddingRight: BX.type.isBoolean(this.getIsActiveFocus()) && !isReversed ? 5 : 4,
						paddingLeft: BX.type.isBoolean(this.getIsActiveFocus()) && isReversed ? 5 : 4,
					},
				},
				...this.renderFocus(),
				stageListMode ? this.renderListView() : this.renderStep(),
			);
		}

		get stepHeight()
		{
			const {
				stage: {
					listMode: stageListMode,
				},
			} = this.props;

			if (stageListMode)
			{
				return LIST_MODE_HEIGHT;
			}

			return this.isSelectView ? STAGE_SELECT_HEIGHT : STAGE_CONTAINER_HEIGHT;
		}

		renderListView()
		{
			const {
				stage: {
					id: stageId,
				},
				showTotal,
			} = this.props;

			const {
				total: stageTotal,
				currency: stageCurrency,
			} = this.counter;

			return View(
				{
					style: style.listViewContainer,
				},
				this.renderListViewStep(
					{
						topOffset: 6,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderColor: AppTheme.colors.base7,
					},
				),
				this.renderListViewStep(
					{
						topOffset: 3,
						backgroundColor: AppTheme.colors.base7,
						borderColor: AppTheme.colors.base6,
					},
				),
				this.renderListViewStep(
					{
						topOffset: 0,
						backgroundColor: AppTheme.colors.base7,
						borderColor: AppTheme.colors.base5,
						data: {
							id: stageId,
							disabled: false,
							money: {
								amount: Math.round(stageTotal),
								currency: stageCurrency,
							},
							showTotal,
						},
					},
				),
			);
		}

		renderListViewStep(stage)
		{
			const {
				topOffset,
				backgroundColor,
				borderColor,
				data,
			} = stage;

			const {
				isReversed,
			} = this.props;

			return View(
				{
					style: {
						...style.listViewStepWrapper,
						top: topOffset,
					},
				},
				View(
					{
						style: style.listViewStepContainer,
					},
					isReversed && Image(
						{
							style: {
								width: 16,
								height: 40,
								marginRight: -2,
							},
							svg: {
								content: svgImages.listModeStageArrowReversed(borderColor, backgroundColor),
							},
						},
					),
					View(
						{
							style: {
								backgroundColor,
								flex: 1,
								height: 40,

								borderTopWidth: 1,
								borderTopColor: borderColor,
								borderBottomWidth: 1,
								borderBottomColor: borderColor,
								borderLeftWidth: !isReversed && 1,
								borderLeftColor: !isReversed && borderColor,
								borderRightWidth: isReversed && 1,
								borderRightColor: isReversed && borderColor,

								borderTopLeftRadius: !isReversed && 6,
								borderBottomLeftRadius: !isReversed && 6,
								borderTopRightRadius: isReversed && 6,
								borderBottomRightRadius: isReversed && 6,
								justifyContent: 'center',
							},
						},
						data ? View(
							{
								style: {
									padding: 8,
									flexGrow: 2,
									justifyContent: 'center',
								},
							},
							this.renderContent(
								{ id: data.id, color: backgroundColor, disabled: data.disabled },
								data.money,
								data.showTotal,
							),
						) : null,
					),
					!isReversed && Image(
						{
							style: {
								width: 16,
								height: 40,
								marginLeft: -2,
							},
							svg: {
								content: svgImages.listModeStageArrow(borderColor, backgroundColor),
							},
						},
					),
				),
			);
		}
	}

	const style = {
		money: (color) => ({
			fontSize: 13,
			fontWeight: '600',
			color,
		}),
		listViewContainer: {
			flexDirection: 'column',
			flex: 1,
		},
		listViewStepWrapper: {
			position: 'absolute',
			height: 40,
			width: '100%',
		},
		listViewStepContainer: {
			flexDirection: 'row',
			flex: 1,
			height: 40,
		},
	};

	const svgImages = {
		stageSelectArrow: (color) => {
			return `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H0.314926C2.30669 0 4.16862 0.9884 5.28463 2.63814L13.8629 15.3191C14.5498 16.3344 14.5498 17.6656 13.8629 18.6809L5.28463 31.3619C4.16863 33.0116 2.30669 34 0.314926 34H0V0Z" fill="${color.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/></svg>`;
		},
		stageSelectArrowReversed: (color) => {
			return `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 0H14.6851C12.6933 0 10.8314 0.9884 9.71537 2.63814L1.1371 15.3191C0.4502 16.3344 0.4502 17.6656 1.1371 18.6809L9.71537 31.3619C10.8314 33.0116 12.6933 34 14.6851 34H15V0Z"fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		stageArrow: (color) => {
			return `<svg width="15" height="40" viewBox="0 0 15 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0C2.14738 0 4.15987 1.1476 5.23027 3.00917L14.1401 18.5046C14.6725 19.4305 14.6725 20.5695 14.1401 21.4954L5.23027 36.9908C4.15987 38.8524 2.14738 40 0 40V0Z" fill="${color.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/></svg>`;
		},
		stageArrowReversed: (color) => {
			return `<svg width="15" height="40" viewBox="0 0 15 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5394 0C12.392 0 10.3795 1.1476 9.30913 3.00917L0.3993 18.5046C-0.133101 19.4305 -0.133101 20.5695 0.3993 21.4954L9.30913 36.9908C10.3795 38.8524 12.392 40 14.5394 40V0Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		listModeStageArrow: (borderColor, backgroundColor) => {
			return `<svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M-191 6C-191 2.68629 -188.314 0 -185 0H0.0288393C2.17622 0 4.15987 1.1476 5.23027 3.00917L14.1401 18.5046C14.6725 19.4305 14.6725 20.5695 14.1401 21.4954L5.23027 36.9908C4.15987 38.8524 2.17621 40 0.0288368 40H-185C-188.314 40 -191 37.3137 -191 34V6Z" fill="${backgroundColor.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/><path d="M-185 0.5H0.0288391C1.99727 0.5 3.81561 1.55197 4.79683 3.25841L13.7067 18.7538C14.1503 19.5254 14.1503 20.4746 13.7067 21.2462L4.79681 36.7416C3.81561 38.448 1.99727 39.5 0.0288391 39.5H-185C-188.038 39.5 -190.5 37.0376 -190.5 34V6C-190.5 2.96243 -188.038 0.5 -185 0.5Z" stroke="${borderColor.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/></svg>`;
		},
		listModeStageArrowReversed: (borderColor, backgroundColor) => {
			return `<svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M207 6C207 2.68629 204.314 0 201 0H15.9712C13.8238 0 11.8401 1.1476 10.7697 3.00917L1.85989 18.5046C1.32749 19.4305 1.32749 20.5695 1.85989 21.4954L10.7697 36.9908C11.8401 38.8524 13.8238 40 15.9712 40H201C204.314 40 207 37.3137 207 34V6Z" fill="${backgroundColor.replaceAll(/[^\d#A-Fa-f]/g, '')}"/><path d="M201 0.5H15.9712C14.0027 0.5 12.1844 1.55197 11.2032 3.25841L2.2933 18.7538C1.8497 19.5254 1.8497 20.4746 2.2933 21.2462L11.2032 36.7416C12.1844 38.448 14.0027 39.5 15.9712 39.5H201C204.038 39.5 206.5 37.0376 206.5 34V6C206.5 2.96243 204.038 0.5 201 0.5Z" stroke="${borderColor.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		stageFocusArrow: (color) => {
			return `<svg width="19" height="48" viewBox="0 0 19 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 47.9988H2.45416C5.06631 47.9988 7.46115 46.5443 8.66562 44.2264L17.4987 27.2277C18.5501 25.2043 18.5501 22.7957 17.4987 20.7723L8.6656 3.77358C7.46115 1.45568 5.06631 0.00125122 2.45416 0.00125122H0V2.00125H2.45416C4.31998 2.00125 6.03058 3.04013 6.89091 4.69577L15.724 21.6945C16.475 23.1398 16.475 24.8602 15.724 26.3055L6.89091 43.3042C6.03059 44.9599 4.31998 45.9988 2.45416 45.9988H0V47.9988Z" fill="${color.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/></svg>`;
		},
		stageFocusArrowReversed: (color) => {
			return `<svg width="19" height="48" viewBox="0 0 19 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19 47.9988H16.5458C13.9337 47.9988 11.5389 46.5443 10.3344 44.2264L1.5013 27.2277C0.449901 25.2043 0.449901 22.7957 1.5013 20.7723L10.3344 3.77358C11.5389 1.45568 13.9337 0.00125122 16.5458 0.00125122H19V2.00125H16.5458C14.68 2.00125 12.9694 3.04013 12.1091 4.69577L3.276 21.6945C2.525 23.1398 2.525 24.8602 3.276 26.3055L12.1091 43.3042C12.9694 44.9599 14.68 45.9988 16.5458 45.9988H19V47.9988Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		listModeStageFocus: (color) => {
			return `<svg width="25" height="56" viewBox="0 0 25 56" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.252197 56H5.89896C8.52547 56 10.931 54.5297 12.1287 52.1922L23.3553 30.2826C24.4183 28.2081 24.3789 25.7406 23.2502 23.701L12.1322 3.6106C10.8993 1.38269 8.55385 0 6.00754 0H0.252197V2H6.00754C7.82632 2 9.50166 2.98764 10.3823 4.579L21.5003 24.6694C22.3065 26.1262 22.3347 27.8888 21.5754 29.3706L10.3488 51.2801C9.49326 52.9498 7.77504 54 5.89896 54H0.252197V56Z" fill="${color.replaceAll(
				/[^\d#A-Fa-f]/g,
				'',
			)}"/></svg>`;
		},
		listModeFocusArrowReversed: (color) => {
			return `<svg width="25" height="56" viewBox="0 0 25 56" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.7478 56H19.101C16.4745 56 14.069 54.5297 12.8713 52.1922L1.6447 30.2826C0.581699 28.2081 0.6211 25.7406 1.7498 23.701L12.8678 3.6106C14.1007 1.38269 16.4461 0 18.9925 0H24.7478V2H18.9925C17.1737 2 15.4983 2.98764 14.6177 4.579L3.4997 24.6694C2.6935 26.1262 2.6653 27.8888 3.4246 29.3706L14.6512 51.2801C15.5067 52.9498 17.225 54 19.101 54H24.7478V56Z" fill="${color.replaceAll(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		selectArrow: () => {
			return '<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M7.48475 0.949596L3.94922 4.48513L0.413685 0.949596H7.48475Z" fill="#525C69"/></svg>';
		},
	};

	module.exports = { StageStep };
});
