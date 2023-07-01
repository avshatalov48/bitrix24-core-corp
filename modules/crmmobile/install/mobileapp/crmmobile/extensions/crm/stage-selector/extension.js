/**
 * @module crm/stage-selector
 */
jn.define('crm/stage-selector', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { NavigationLoader } = require('navigation-loader');
	const { isLightColor } = require('utils/color');
	const { isEqual } = require('utils/object');
	const { throttle } = require('utils/function');
	const { getStageIcon } = require('crm/assets/stage');
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { CategoryStorage } = require('crm/storage/category');
	const { StageListView } = require('crm/stage-list-view');
	const { actionCheckChangeStage } = require('crm/entity-actions/check-change-stage');

	const STAGE_WIDTH = device.screen.width * 0.48;
	const STAGE_MARGIN = 8;
	const FIRST_STAGE_VIEW_WIDTH = 16;

	const AnimationMode = {
		UPDATE_BEFORE_ANIMATION: 'updateBeforeAnimation',
		ANIMATE_BEFORE_UPDATE: 'animateBeforeUpdate',
	};

	const STAGES_SEMANTICS = {
		PROCESS: 'P',
		FAILED: 'F',
		SUCCESS: 'S',
	};

	/**
	 * @class StageSelector
	 */
	class StageSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = this.getUid();
			this.state = {
				category: this.getCategoryByProps(props),
				activeStageId: props.activeStageId,
			};

			this.handleStageClick = throttle(this.handleStageClick, 500, this);
		}

		componentWillReceiveProps(newProps)
		{
			this.state.activeStageId = newProps.activeStageId;
			this.state.category = this.getCategoryByProps(newProps);
		}

		componentDidMount()
		{
			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;
		}

		getUid()
		{
			return this.props.uid || Random.getString();
		}

		get animationMode()
		{
			return BX.prop.getString(this.props, 'animationMode', AnimationMode.UPDATE_BEFORE_ANIMATION);
		}

		getCategoryByProps(props)
		{
			const { entityTypeId, categoryId } = props;
			const category = CategoryStorage.getCategory(entityTypeId, categoryId);

			if (this.isNewLead())
			{
				category.successStages = [];
			}

			return category;
		}

		isNewLead()
		{
			const { entityTypeId, isNewEntity } = this.props;

			return isNewEntity && TypeId.Lead === entityTypeId;
		}

		reloadCategory()
		{
			const category = this.getCategoryByProps(this.props);
			if (!isEqual(this.state.category, category))
			{
				this.setState({ category });
			}
		}

		isReadonly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		isReadonlyNotificationEnabled()
		{
			return BX.prop.getBoolean(this.props, 'showReadonlyNotification', false);
		}

		showBorder()
		{
			return BX.prop.getBoolean(this.props, 'showBorder', false);
		}

		getCategoryToSlider()
		{
			const { category } = this.state;

			if (!category)
			{
				return [];
			}

			const { successStages, failedStages, processStages } = category;

			return [...processStages, ...successStages, ...failedStages];
		}

		getStageIndexById(stages, stageId)
		{
			return Math.max(stages.findIndex(({ id }) => id === stageId), 0);
		}

		getCurrentSliderPosition(stages, activeIndex)
		{
			if (activeIndex === 0)
			{
				return this.props.hasHiddenEmptyView ? FIRST_STAGE_VIEW_WIDTH : STAGE_MARGIN + FIRST_STAGE_VIEW_WIDTH;
			}

			return activeIndex * (-STAGE_WIDTH - STAGE_MARGIN) + FIRST_STAGE_VIEW_WIDTH + 2 * STAGE_MARGIN;
		}

		openStageList(activeStageId)
		{
			const { entityTypeId, categoryId, data, isNewEntity } = this.props;

			return StageListView.open({
				entityTypeId,
				categoryId,
				activeStageId,
				data,
				isNewEntity,
				readOnly: true,
				canMoveStages: false,
				enableStageSelect: true,
				onStageSelect: ({ id }, category, data) => this.changeActiveStageId(id, category, data),
				uid: this.uid,
			});
		}

		handleStageClick(first, id, category, data)
		{
			if (this.isReadonly())
			{
				return;
			}

			Keyboard.dismiss();

			if (first)
			{
				void this.openStageList(id);
			}
			else
			{
				Haptics.impactLight();
				this.changeActiveStageId(id, category, data);
			}
		}

		openStageChangeMenu(stage, category, data)
		{
			const { id, name, color } = stage;

			const menu = new ContextMenu({
				actions: [
					{
						id: 'change-stage',
						title: name,
						data: {
							svgIcon: getStageIcon(color),
						},
						onClickCallback: () => {
							menu.close(() => {
								this.handleStageClick(false, id, category, data);
							});
						},
					},
				],
				params: {
					title: BX.message('CRM_STAGE_SELECTOR_CHANGE_STAGE_TITLE'),
					showCancelButton: true,
					showActionLoader: false,
					isCustomIconColor: true,
				},
			});

			menu.show();
		}

		notifyAboutReadOnlyStatus()
		{
			if (this.isReadonlyNotificationEnabled())
			{
				Notify.showUniqueMessage(
					getEntityMessage('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TEXT2', this.props.entityTypeId),
					BX.message('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TITLE'),
					{ time: 4 },
				);
			}
		}

		changeActiveStageId(selectedStageId, activeCategory, data)
		{
			const { activeStageId } = this.state;
			const category = activeCategory || this.getCategoryByProps(this.props);
			const { onStageSelect, entityTypeId, entityId } = this.props;

			if (this.isReadonly() || activeStageId === selectedStageId || !onStageSelect)
			{
				return;
			}

			const actionParams = {
				uid: this.getUid(),
				category,
				entityId,
				entityTypeId,
				activeStageId,
				selectedStageId,
			};

			actionCheckChangeStage(actionParams).then(() => {
				if (this.animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
				{
					onStageSelect(selectedStageId, category, data)
						.then(({ action } = {}) => {
							if (action === 'delete')
							{
								return Promise.reject();
							}

							return this.updateBeforeAnimation(selectedStageId);
						})
						.then(() => {
							if (this.props.forceUpdate)
							{
								this.props.forceUpdate({
									data,
									columnId: selectedStageId,
								});
							}
						});
				}
				else
				{
					this.prevActiveStageId = activeStageId;

					onStageSelect(selectedStageId, category, data).then(() => this.animate(selectedStageId));
				}
			});
		}

		updateBeforeAnimation(selectedStageId)
		{
			return new Promise((resolve) => {
				this.setState({ nextStageId: selectedStageId }, () => this.animate(selectedStageId, resolve));
			});
		}

		animate(stageId, resolve)
		{
			const activeIndex = this.getStageIndexById(this.currentStages, stageId);
			const offset = this.getCurrentSliderPosition(this.currentStages, activeIndex);

			this.stageSliderRef.animate(
				{
					duration: 500,
					left: offset,
				},
				() => {
					if (this.animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
					{
						this.setState({
							activeStageId: this.state.nextStageId,
							nextStageId: null,
						}, () => resolve && resolve());
					}
					else if (resolve)
					{
						resolve();
					}
				},
			);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							width: '100%',
							flexDirection: 'row',
							height: 50,
						},
					},
					this.renderStageSlider(),
				),
				View(
					{
						style: {
							marginHorizontal: 16,
							padding: 4,
							flexDirection: 'row',
							flex: 1,
							borderBottomWidth: 1,
							borderBottomColor: this.showBorder() ? this.props.borderColor : null,
						},
					},
				),
			);
		}

		renderStageSlider()
		{
			const { activeStageId, nextStageId } = this.state;

			this.currentStages = this.getSliderStages();

			let activeIndex;
			let prevIndex;

			if (this.animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
			{
				activeIndex = this.getStageIndexById(this.currentStages, nextStageId || activeStageId);
				prevIndex = this.getStageIndexById(this.currentStages, activeStageId);
			}
			else
			{
				activeIndex = this.getStageIndexById(this.currentStages, activeStageId);
				prevIndex = this.getStageIndexById(this.currentStages, this.prevActiveStageId || activeStageId);
			}

			const currentPosition = this.getCurrentSliderPosition(this.currentStages, prevIndex);
			const renderedStages = this.currentStages.map((stage, index) => this.renderStage(stage, index, activeIndex, activeStageId));

			return View(
				{
					style: {
						width: (STAGE_WIDTH + STAGE_MARGIN) * this.currentStages.length + FIRST_STAGE_VIEW_WIDTH,
						flexDirection: 'row',
						height: 50,
						position: 'absolute',
						top: 0,
						// animation fix (create new object on each render)
						left: activeStageId % 2 ? currentPosition - Math.random() : currentPosition + Math.random(),
					},
					ref: (ref) => this.stageSliderRef = ref,
				},
				...renderedStages,
			);
		}

		getSliderStages()
		{
			const { activeStageId, nextStageId } = this.state;
			const stages = this.getCategoryToSlider();

			let activeIndex;
			let prevIndex;

			if (this.animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
			{
				const prevStage = nextStageId || activeStageId;

				activeIndex = this.getStageIndexById(stages, activeStageId);
				prevIndex = this.getStageIndexById(stages, prevStage);
			}
			else
			{
				activeIndex = this.getStageIndexById(stages, activeStageId);
				prevIndex = this.getStageIndexById(stages, this.prevActiveStageId);
			}

			if (prevIndex === activeIndex)
			{
				return this.getInitialSliderStages(stages, activeIndex);
			}

			const { category } = this.state;
			let start = Math.min(activeIndex, prevIndex);
			const activeStage = stages[activeIndex];
			const prevStage = stages[prevIndex];
			if (start !== 0)
			{
				start -= 1;
			}

			if (activeStage.semantics !== STAGES_SEMANTICS.PROCESS)
			{
				if (prevStage.semantics === STAGES_SEMANTICS.PROCESS)
				{
					return [...category.processStages.slice(start), activeStage];
				}

				return [category.processStages[category.processStages.length - 1], activeStage];
			}

			if (prevStage.semantics === STAGES_SEMANTICS.SUCCESS)
			{
				return [...category.processStages.slice(start), prevStage];
			}

			if (prevStage.semantics === STAGES_SEMANTICS.FAILED)
			{
				return [...category.processStages.slice(start), ...category.successStages];
			}

			const maxIndex = Math.max(prevIndex, activeIndex);

			return this.filterFailedStages(stages).slice(start, maxIndex + 2);
		}

		filterFailedStages(stages)
		{
			return stages.filter(({ semantics }) => semantics !== STAGES_SEMANTICS.FAILED);
		}

		getInitialSliderStages(stages, activeIndex)
		{
			let start = 0;
			let end = 0;
			if (activeIndex === 0)
			{
				return stages.slice(activeIndex, 2);
			}

			if (stages[activeIndex - 1])
			{
				start = activeIndex - 1;
			}

			if (stages[activeIndex].semantics !== 'P')
			{
				return [this.state.category.processStages[this.state.category.processStages.length - 1], stages[activeIndex]];
			}
			if (stages[activeIndex + 1])
			{
				end = stages[activeIndex + 1].semantics === 'P' ? 3 : 2;
			}

			return stages.slice(start, activeIndex + end);
		}

		renderStage(stage, index, activeIndex, activeStageId)
		{
			const { color, name, id } = stage;
			const { category, data, useStageChangeMenu } = this.props;
			const backgroundColor = index > activeIndex ? '#eef2f4' : color;
			const readOnly = this.isReadonly();

			const textContrastColor = this.calculateTextColor(backgroundColor);

			return View(
				{
					testId: this.getTestId(index, activeIndex),
					style: {
						height: 50,
						width: STAGE_WIDTH,
						marginRight: 8,
						paddingTop: 8,
						paddingBottom: 8,
						flexDirection: 'row',
					},
					onClick: () => {
						if (readOnly)
						{
							this.notifyAboutReadOnlyStatus();
						}
						else if (useStageChangeMenu && activeStageId !== id)
						{
							this.openStageChangeMenu(stage, category, data);
						}
						else
						{
							this.handleStageClick(activeStageId === id, id, category, data);
						}
					},
					onLongClick: () => {
						if (readOnly)
						{
							this.notifyAboutReadOnlyStatus();
						}
						else if (useStageChangeMenu && activeStageId !== id)
						{
							this.handleStageClick(activeStageId === id, id, category, data);
						}
					},
				},
				View(
					{
						style: {
							width: STAGE_WIDTH - 10,
							height: '100%',
							borderRadius: 5,
							backgroundColor: index > activeIndex ? '#eef2f4' : backgroundColor,
							flexDirection: 'column',
							borderBottomWidth: 3,
							borderBottomColor: color,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								justifyContent: 'space-between',
								height: '100%',
								paddingLeft: 8,
								paddingRight: index < activeIndex ? 10 : 0,
							},
						},
						Text(
							{
								numberOfLines: 1,
								ellipsize: 'end',
								style: {
									height: 'auto',
									color: index > activeIndex ? '#a8adb4' : textContrastColor,
									fontWeight: '500',
									flexShrink: 2,
								},
								text: name,
							},
						),
						!readOnly && activeStageId === id && Image(
							{
								style: {
									width: 8,
									height: 5,
									marginHorizontal: 5,
									marginTop: 6,
									marginBottom: 4,
								},
								svg: {
									content: svgImages.stageSelectArrow(textContrastColor),
								},
							},
						),
					),
				),
				Image(
					{
						style: {
							width: 15,
							height: 34,
							marginLeft: -5,
						},
						resizeMode: 'contain',
						svg: {
							content: svgImages.arrow(backgroundColor, color),
						},
					},
				),
			);
		}

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

		calculateTextColor(baseColor)
		{
			if (isLightColor(baseColor))
			{
				return '#333333';
			}

			return '#ffffff';
		}
	}

	const svgImages = {
		stageSelectArrow: (color) => `<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M7.48524 0.949718L3.94971 4.48525L0.414173 0.949718H7.48524Z" fill="${color}"/></svg>`,

		arrow: (backgroundColor, borderColor) => `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H0.314926C2.30669 0 4.16862 0.9884 5.28463 2.63814L13.8629 15.3191C14.5498 16.3344 14.5498 17.6656 13.8629 18.6809L5.28463 31.3619C4.16863 33.0116 2.30669 34 0.314926 34H0V0Z" fill="${backgroundColor}"/><path d="M0 31H5.5L5.2812 31.3282C4.1684 32.9974 2.29502 34 0.288897 34H0V31Z" fill="${borderColor}"/></svg>`,
	};
	module.exports = { StageSelector, AnimationMode };
});
