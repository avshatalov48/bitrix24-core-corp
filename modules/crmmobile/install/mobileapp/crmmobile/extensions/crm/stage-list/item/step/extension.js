(() => {
	const { isLightColor } = jn.require('utils/color');

	const DEFAULT_STAGE_BACKGROUND_COLOR = '#c3f0ff';
	const DISABLED_STAGE_BACKGROUND_COLOR = '#eef2f4';

	const STAGE_CONTAINER_HEIGHT = 48;
	const STAGE_SELECT_HEIGHT = 34;
	const STAGE_HEIGHT = 40;
	const LIST_MODE_HEIGHT = 55;

	/**
	 * @class StageStep
	 */
	class StageStep extends LayoutComponent
	{
		get isSelectView()
		{
			return BX.prop.getBoolean(this.props, 'isSelectView', false);
		}

		calculateTextColor(baseColor)
		{
			if (isLightColor(baseColor))
			{
				return '#333333';
			}

			return '#ffffff';
		}

		renderStep()
		{
			const {
				stage: {
					color: stageColor,
					total: stageTotal,
					currency: stageCurrency,
					id: stageId,
					borderColor: stageBorderColor,
				},
				disabled,
				showTotal,
				unsuitable,
				showArrow,
			} = this.props;

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
				View(
					{
						style: {
							padding: 8,
							backgroundColor: stageBackground,
							borderColor: stageBorderColor,
							borderWidth: stageBorderColor ? 1 : 0,
							flexGrow: 2,
							height: stageHeight,
							borderTopLeftRadius: 6,
							borderBottomLeftRadius: 6,
							justifyContent: 'center',
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
				!disabled && Image(
					{
						style: {
							width: 15,
							height: stageHeight,
							marginLeft: -1,
						},
						svg: {
							content: this.isSelectView ? svgImages.stageSelectArrow(stageBackground) : svgImages.stageArrow(stageBackground),
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
							content: svgImages.listModeStageArrow(stageBorderColor, '#ffffff'),
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

		getIsActiveFocus()
		{
			return this.props.stage.active;
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
			const stageColor = disabled ? '#a8adb4' : this.calculateTextColor(color);
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
					name: stageName,
					count: stageCount,
				},
				showAllStagesItem,
				showCount,
			} = this.props;
			return View(
				{
					style: {
						flexDirection: 'row',
						flex: showAllStagesItem ? 0 : 1,
					},
				},
				Text(
					{
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
			} = this.props;

			const borderColor = '#2fc6f6';
			const border = ['Left', 'Top', 'Bottom'].reduce((acc, position) => ({
				...acc,
				[`border${position}Width`]: 2,
				[`border${position}Color`]: borderColor,
			}), {});
			return [
				Image(
					{
						style: {
							position: 'absolute',
							right: 0,
							top: 0,
							width: stageListMode ? 25 : 21,
							height: stageListMode ? LIST_MODE_HEIGHT : STAGE_CONTAINER_HEIGHT,
						},
						svg: {
							content: stageListMode ? svgImages.listModeStageFocus(borderColor) : svgImages.stageFocusArrow(borderColor),
						},
					},
				),
				View(
					{
						style: {
							left: 0,
							top: 0,
							position: 'absolute',
							width: '102%',
						},
					},
					View({
						style: {
							height: stageListMode ? LIST_MODE_HEIGHT : STAGE_CONTAINER_HEIGHT,
							width: '95%',
							borderTopLeftRadius: 10,
							borderBottomLeftRadius: 10,
							...border,
						},
					}),
				)];
		}

		render()
		{
			const {
				stage: {
					listMode: stageListMode,
				},
			} = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 2,
						height: this.stepHeight,
						padding: BX.type.isBoolean(this.getIsActiveFocus()) ? 4 : 0,
						paddingRight: BX.type.isBoolean(this.getIsActiveFocus()) ? 5 : 0,
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
					total: stageTotal,
					currency: stageCurrency,
					id: stageId,
				},
				showTotal,
			} = this.props;

			return View(
				{
					style: style.listViewContainer,
				},
				this.renderListViewStep(
					{
						topOffset: 6,
						backgroundColor: '#fff',
						borderColor: '#e6e7e9',
					},
				),
				this.renderListViewStep(
					{
						topOffset: 3,
						backgroundColor: '#f1f4f6',
						borderColor: '#dfe0e3',
					},
				),
				this.renderListViewStep(
					{
						topOffset: 0,
						borderColor: '#d5d7db',
						backgroundColor: '#e6e7e9',
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
					View(
						{
							style: {
								backgroundColor,
								borderColor,
								...style.listViewStep,
							},
						},
						data ? View(
							{
								style: style.listViewStepContent,
							},
							this.renderContent(
								{ id: data.id, color: backgroundColor, disabled: data.disabled },
								data.money,
								data.showTotal,
							),
						) : null,
					),
					Image(
						{
							style: style.listModeStageArrow,
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
		listViewStep: {
			flex: 1,
			height: 40,
			borderWidth: 1,
			borderTopLeftRadius: 6,
			borderBottomLeftRadius: 6,
			justifyContent: 'center',
		},
		listViewStepContent: {
			padding: 8,
			flexGrow: 2,
		},
		listModeStageArrow: {
			width: 16,
			height: 40,
			marginLeft: -2,
		},
	};

	const svgImages = {
		stageSelectArrow: (color) => {
			return `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H0.314926C2.30669 0 4.16862 0.9884 5.28463 2.63814L13.8629 15.3191C14.5498 16.3344 14.5498 17.6656 13.8629 18.6809L5.28463 31.3619C4.16863 33.0116 2.30669 34 0.314926 34H0V0Z" fill="${color.replace(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		stageArrow: (color) => {
			return `<svg width="15" height="40" viewBox="0 0 15 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0C2.14738 0 4.15987 1.1476 5.23027 3.00917L14.1401 18.5046C14.6725 19.4305 14.6725 20.5695 14.1401 21.4954L5.23027 36.9908C4.15987 38.8524 2.14738 40 0 40V0Z" fill="${color.replace(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		listModeStageArrow: (borderColor, backgroundColor) => {
			return `<svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M-191 6C-191 2.68629 -188.314 0 -185 0H0.0288393C2.17622 0 4.15987 1.1476 5.23027 3.00917L14.1401 18.5046C14.6725 19.4305 14.6725 20.5695 14.1401 21.4954L5.23027 36.9908C4.15987 38.8524 2.17621 40 0.0288368 40H-185C-188.314 40 -191 37.3137 -191 34V6Z" fill="${backgroundColor.replace(/[^\d#A-Fa-f]/g, '')}"/><path d="M-185 0.5H0.0288391C1.99727 0.5 3.81561 1.55197 4.79683 3.25841L13.7067 18.7538C14.1503 19.5254 14.1503 20.4746 13.7067 21.2462L4.79681 36.7416C3.81561 38.448 1.99727 39.5 0.0288391 39.5H-185C-188.038 39.5 -190.5 37.0376 -190.5 34V6C-190.5 2.96243 -188.038 0.5 -185 0.5Z" stroke="${borderColor.replace(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		stageFocusArrow: (color) => {
			return `<svg width="19" height="48" viewBox="0 0 19 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 47.9988H2.45416C5.06631 47.9988 7.46115 46.5443 8.66562 44.2264L17.4987 27.2277C18.5501 25.2043 18.5501 22.7957 17.4987 20.7723L8.6656 3.77358C7.46115 1.45568 5.06631 0.00125122 2.45416 0.00125122H0V2.00125H2.45416C4.31998 2.00125 6.03058 3.04013 6.89091 4.69577L15.724 21.6945C16.475 23.1398 16.475 24.8602 15.724 26.3055L6.89091 43.3042C6.03059 44.9599 4.31998 45.9988 2.45416 45.9988H0V47.9988Z" fill="${color.replace(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		listModeStageFocus: (color) => {
			return `<svg width="25" height="56" viewBox="0 0 25 56" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.252197 56H5.89896C8.52547 56 10.931 54.5297 12.1287 52.1922L23.3553 30.2826C24.4183 28.2081 24.3789 25.7406 23.2502 23.701L12.1322 3.6106C10.8993 1.38269 8.55385 0 6.00754 0H0.252197V2H6.00754C7.82632 2 9.50166 2.98764 10.3823 4.579L21.5003 24.6694C22.3065 26.1262 22.3347 27.8888 21.5754 29.3706L10.3488 51.2801C9.49326 52.9498 7.77504 54 5.89896 54H0.252197V56Z" fill="${color.replace(/[^\d#A-Fa-f]/g, '')}"/></svg>`;
		},
		selectArrow: () => {
			return `<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M7.48475 0.949596L3.94922 4.48513L0.413685 0.949596H7.48475Z" fill="#525C69"/></svg>`;
		},
	};

	this.Crm = this.Crm || {};
	this.Crm.StageStep = StageStep;
})();
