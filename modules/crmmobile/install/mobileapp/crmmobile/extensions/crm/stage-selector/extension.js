/**
 * @module crm/stage-selector
 */
jn.define('crm/stage-selector', (require, exports, module) => {

	const { Haptics } = require('haptics');
	const { NavigationLoader } = require('navigation-loader');
	const { isEqual } = require('utils/object');
	const { throttle } = require('utils/function');
	const { getStageIcon } = require('crm/assets/stage');
	const { CategoryStorage } = require('crm/storage/category');
	const { StageListView } = require('crm/stage-list-view');

	const STAGE_WIDTH = device.screen.width * 0.48;
	const STAGE_MARGIN = 8;
	const FIRST_STAGE_VIEW_WIDTH = 16;

	const AnimationMode = {
		UPDATE_BEFORE_ANIMATION: 'updateBeforeAnimation',
		ANIMATE_BEFORE_UPDATE: 'animateBeforeUpdate',
	};

	/**
	 * @class StageSelector
	 */
	class StageSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.randomUid = Random.getString(10);

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

		get uid()
		{
			return this.props.uid || this.randomUid;
		}

		get animationMode()
		{
			return BX.prop.getString(this.props, 'animationMode', AnimationMode.UPDATE_BEFORE_ANIMATION);
		}

		getCategoryByProps(props)
		{
			const { entityTypeId, categoryId } = props;

			return CategoryStorage.getCategory(entityTypeId, categoryId);
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

		getStagesFromCategory()
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
			const { entityTypeId, categoryId, data } = this.props;

			return StageListView.open({
				entityTypeId,
				categoryId,
				activeStageId,
				data,
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
						onClickCallback: () => this.handleStageClick(false, id, category, data),
					},
				],
				params: {
					title: BX.message('CRM_STAGE_SELECTOR_CHANGE_STAGE_TITLE'),
					showCancelButton: true,
					showActionLoader: false,
				},
			});

			menu.show();
		}

		notifyAboutReadOnlyStatus()
		{
			if (this.isReadonlyNotificationEnabled())
			{
				Notify.showUniqueMessage(
					BX.message('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TEXT'),
					BX.message('CRM_STAGE_SELECTOR_NOTIFY_READONLY_TITLE'),
					{ time: 4 },
				);
			}
		}

		changeActiveStageId(selectedStageId, category, data)
		{
			const { activeStageId } = this.state;

			if (this.isReadonly() || activeStageId === selectedStageId)
			{
				return;
			}

			const { onStageSelect } = this.props;
			if (typeof onStageSelect === 'function')
			{
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
						})
					;
				}
				else
				{
					this.prevActiveStageId = activeStageId;

					onStageSelect(selectedStageId, category, data)
						.then(() => this.animate(selectedStageId))
					;
				}
			}
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
					else
					{
						resolve && resolve();
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
				View({
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
			const renderedStages = this.currentStages.map((stage, index) => {
				return this.renderStage(
					stage,
					index,
					activeIndex,
					activeStageId,
				);
			});

			return View(
				{
					style: {
						width: (STAGE_WIDTH + STAGE_MARGIN) * this.currentStages.length + FIRST_STAGE_VIEW_WIDTH,
						flexDirection: 'row',
						height: 50,
						position: 'absolute',
						top: 0,
						//animation fix (create new object on each render)
						left: activeStageId % 2 ? currentPosition - Math.random() : currentPosition + Math.random(),
					},
					ref: (ref) => this.stageSliderRef = ref,
				},
				...renderedStages,
			);
		}

		getSliderStages()
		{
			const stages = this.getStagesFromCategory();
			let activeIndex, prevIndex;

			if (this.animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
			{
				const prevStage = this.state.nextStageId || this.state.activeStageId;

				activeIndex = this.getStageIndexById(stages, this.state.activeStageId);
				prevIndex = this.getStageIndexById(stages, prevStage);
			}
			else
			{
				activeIndex = this.getStageIndexById(stages, this.state.activeStageId);
				prevIndex = this.getStageIndexById(stages, this.prevActiveStageId);
			}

			if (prevIndex === activeIndex)
			{
				return this.getInitialSliderStages(stages, activeIndex);
			}

			let start = Math.min(activeIndex, prevIndex);
			const maxIndex = Math.max(prevIndex, activeIndex);

			if (start !== 0)
			{
				start = start - 1;
			}

			if (stages[activeIndex].semantics !== 'P')
			{
				if (stages[prevIndex].semantics === 'P')
				{
					return [...this.state.category.processStages.slice(start), stages[activeIndex]];
				}
				else
				{
					return [this.state.category.processStages[this.state.category.processStages.length - 1], stages[activeIndex]];
				}
			}
			else
			{
				if (stages[prevIndex].semantics === 'S')
				{
					return [...this.state.category.processStages.slice(start), stages[prevIndex]];
				}
				else if (stages[prevIndex].semantics === 'F')
				{
					return [...this.state.category.processStages.slice(start), ...this.state.category.successStages, stages[prevIndex]];
				}
			}

			return stages.slice(start, maxIndex + 2);
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
			else if (stages[activeIndex + 1])
			{
				if (stages[activeIndex + 1].semantics === 'P')
				{
					end = 3;
				}
				else
				{
					end = 2;
				}
			}

			return stages.slice(start, activeIndex + end);
		}

		renderStage(stage, index, activeIndex, activeStageId)
		{
			const {
				color,
				name,
				id,
			} = stage;
			const { category, data, useStageChangeMenu } = this.props;
			const backgroundColor = index > activeIndex ? '#eef2f4' : color;
			const readOnly = this.isReadonly();

			return View(
				{
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
									color: index > activeIndex ? '#a8adb4' : this.calculateTextColor(backgroundColor),
									fontWeight: '500',
									flexShrink: 2,
								},
								text: name,
							},
						),
						!readOnly && activeStageId === id ? Image(
							{
								style: {
									width: 8,
									height: 5,
									margin: 5,
								},
								svg: {
									content: svgImages.stageSelectArrow(),
								},
							},
						) : null,
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

		calculateTextColor(baseColor)
		{
			let r, g, b;
			if (baseColor > 7)
			{
				let hexComponent = baseColor.split('(')[1].split(')')[0];
				hexComponent = hexComponent.split(',');
				r = parseInt(hexComponent[0]);
				g = parseInt(hexComponent[1]);
				b = parseInt(hexComponent[2]);
			}
			else if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
			{
				let c = baseColor.substring(1).split('');
				if (c.length === 3)
				{
					c = [c[0], c[0], c[1], c[1], c[2], c[2]];
				}
				c = '0x' + c.join('');
				r = (c >> 16) & 255;
				g = (c >> 8) & 255;
				b = c & 255;
			}

			const y = 0.21 * r + 0.72 * g + 0.07 * b;
			return (y < 145) ? '#fff' : '#333';
		}
	}

	const svgImages = {
		stageSelectArrow: () => {
			return `<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M7.48524 0.949718L3.94971 4.48525L0.414173 0.949718H7.48524Z" fill="white"/></svg>`;
		},
		arrow: (backgroundColor, borderColor) => {
			return `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H0.314926C2.30669 0 4.16862 0.9884 5.28463 2.63814L13.8629 15.3191C14.5498 16.3344 14.5498 17.6656 13.8629 18.6809L5.28463 31.3619C4.16863 33.0116 2.30669 34 0.314926 34H0V0Z" fill="${backgroundColor}"/><path d="M0 31H5.5L5.2812 31.3282C4.1684 32.9974 2.29502 34 0.288897 34H0V31Z" fill="${borderColor}"/></svg>`;
		},
	};
	module.exports = { StageSelector, AnimationMode };
});
