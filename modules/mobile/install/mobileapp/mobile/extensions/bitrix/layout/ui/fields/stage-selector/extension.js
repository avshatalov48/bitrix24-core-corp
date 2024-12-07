/**
 * @module layout/ui/fields/stage-selector
 */
jn.define('layout/ui/fields/stage-selector', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { BaseField } = require('layout/ui/fields/base');
	const { STAGE_WIDTH, StageItemClass } = require('layout/ui/fields/stage-selector/stage-item');
	const { throttle } = require('utils/function');
	const { isEqual } = require('utils/object');

	const AnimationMode = {
		UPDATE_BEFORE_ANIMATION: 'updateBeforeAnimation',
		ANIMATE_BEFORE_UPDATE: 'animateBeforeUpdate',
	};

	const STAGE_MARGIN = 8;
	const FIRST_STAGE_VIEW_WIDTH = 16;

	/**
	 * @class StageSelectorField
	 * @typedef {LayoutComponent<StageSelectorFieldProps, Array<StageSelectorFieldState>>} StageSelectorField
	 */
	class StageSelectorField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.state.activeStageId = this.getValue();
			this.state.nextStageId = null;

			this.currentStages = [];
			this.prevActiveStageId = null;

			this.stageSliderRef = null;
			this.isAnimationInProgress = false;

			this.onStageClickHandler = this.onStageClick.bind(this);
			this.onStageLongClickHandler = this.onStageLongClick.bind(this);
			this.onChangeStageHandler = throttle(this.onChangeStage.bind(this), 500);
		}

		get stageIdsBySemantics()
		{
			return BX.prop.getObject(this.props, 'stageIdsBySemantics', {});
		}

		get processStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'processStages', []);
		}

		get successStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'successStages', []);
		}

		get failedStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'failedStages', []);
		}

		get storage()
		{
			return BX.prop.get(this.props, 'storage', null);
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.getConfig(), 'isReversed', false);
		}

		isReadonlyNotificationEnabled()
		{
			return BX.prop.getBoolean(this.props, 'showReadonlyNotification', false);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.state.activeStageId = newProps.value;
			if (newProps.value === this.getValue())
			{
				this.prevActiveStageId = newProps.value;
			}
		}

		getConfig()
		{
			const config = super.getConfig();
			const animationMode = BX.prop.getString(config, 'animationMode', AnimationMode.UPDATE_BEFORE_ANIMATION);
			const useStageChangeMenu = BX.prop.getBoolean(config, 'useStageChangeMenu', false);
			const showReadonlyNotification = BX.prop.getBoolean(config, 'showReadonlyNotification', false);

			return {
				...config,
				animationMode,
				useStageChangeMenu,
				showReadonlyNotification,
			};
		}

		renderReadOnlyContent()
		{
			return this.renderEditableContent();
		}

		isEmptyEditable()
		{
			return false;
		}

		renderEditableContent()
		{
			this.currentStages = this.getSliderStages();
			if (
				this.currentStages.length === 0
				|| !(Number.isInteger(this.state.activeStageId))
				|| !this.isActiveStageExist()
			)
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'column',
					},
				},
				this.renderStageSlider(),
				View(
					{
						style: {
							marginHorizontal: 16,
							padding: 4,
							flexDirection: 'row',
							flex: 1,
							borderBottomWidth: 1,
							borderBottomColor: this.showBorder() ? this.getExternalWrapperBorderColor() : null,
						},
					},
				),
			);
		}

		isActiveStageExist()
		{
			const stages = [...this.processStages, ...this.successStages, ...this.failedStages];

			return stages.includes(this.state.activeStageId);
		}

		renderStageSlider()
		{
			const { activeStageId, nextStageId } = this.state;

			let activeIndex;
			let prevIndex;

			if (this.getConfig().animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
			{
				activeIndex = StageSelectorField.getStageIndexById(this.currentStages, nextStageId || activeStageId);
				prevIndex = StageSelectorField.getStageIndexById(this.currentStages, activeStageId);
			}
			else
			{
				activeIndex = StageSelectorField.getStageIndexById(this.currentStages, activeStageId);
				prevIndex = StageSelectorField.getStageIndexById(
					this.currentStages,
					this.prevActiveStageId || activeStageId,
				);
			}

			const currentPosition = this.getCurrentSliderPosition(prevIndex);

			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						height: 50,
					},
				},
				View(
					{
						ref: (ref) => this.stageSliderRef = ref,
						style: {
							width: (STAGE_WIDTH + STAGE_MARGIN) * this.currentStages.length + FIRST_STAGE_VIEW_WIDTH,
							flexDirection: 'row',
							height: 50,
							position: 'absolute',
							top: 0,
							// animation fix (create new object on each render)
							left: activeStageId % 2 ? currentPosition - Math.random() : currentPosition + Math.random(),
						},
					},
					...this.renderStages(this.currentStages, activeIndex),
				),
			);
		}

		renderStages(currentStages, activeIndex)
		{
			throw new Error('Method renderStages must be implemented');
		}

		renderEmptyContent()
		{
			return View(
				{
					style: {
						height: 59,
						flexDirection: 'row',
						width: '100%',
						borderBottomWidth: 1,
						borderBottomColor: this.showBorder() ? this.getExternalWrapperBorderColor() : null,
					},
				},
				View(
					{
						style: {
							width: '100%',
							flexDirection: 'row',
							height: 59,
							position: 'absolute',
							top: 0,
							left: this.hasHiddenEmptyView() ? FIRST_STAGE_VIEW_WIDTH : STAGE_MARGIN + FIRST_STAGE_VIEW_WIDTH,
						},
					},
					new StageItemClass({
						stage: {
							color: AppTheme.colors.base6,
						},
						isReversed: this.isReversed,
						id: 0,
						activeIndex: 0,
						showMenu: false,
					}),
					new StageItemClass({
						stage: {
							color: AppTheme.colors.base6,
						},
						id: 1,
						activeIndex: 0,
						showMenu: false,
					}),
				),
			);
		}

		onStageClick(stage)
		{
			const { id } = stage;
			const { activeStageId } = this.state;

			if (this.isAnimationInProgress)
			{
				return;
			}

			if (this.isReadOnly())
			{
				this.notifyAboutReadOnlyStatus();
			}
			else if (this.getConfig().useStageChangeMenu && activeStageId !== id)
			{
				this.openStageChangeMenu(stage);
			}
			else
			{
				this.onChangeStageHandler(stage);
			}
		}

		onStageLongClick(stage)
		{
			const { activeStageId } = this.state;
			const { id } = stage;

			if (this.isAnimationInProgress)
			{
				return;
			}

			if (this.isReadOnly())
			{
				this.notifyAboutReadOnlyStatus();
			}
			else if (this.getConfig().useStageChangeMenu && activeStageId !== id)
			{
				this.openStageChangeMenu(stage);
			}
		}

		notifyAboutReadOnlyStatus()
		{
			if (this.props.notifyAboutReadOnlyStatus)
			{
				this.props.notifyAboutReadOnlyStatus();
			}
		}

		openStageChangeMenu(stage)
		{
			const { name, color } = stage;
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
								this.onChangeStageHandler(stage);
							});
						},
					},
				],
				params: {
					title: BX.message('FIELDS_STAGE_SELECTOR_CHANGE_STAGE_TITLE'),
					showCancelButton: true,
					showActionLoader: false,
					isCustomIconColor: true,
				},
			});

			menu.show();
		}

		onChangeStage(stage)
		{
			if (this.isReadOnly())
			{
				return;
			}

			Keyboard.dismiss();

			if (stage.id === this.state.activeStageId)
			{
				void this.openStageList(stage.id);
			}
			else
			{
				this.changeActiveStageId(stage.id, stage.statusId);
			}
		}

		async openStageList(activeStageId)
		{
			throw new Error('Method openStageList must be implemented');
		}

		changeActiveStageId(id, statusId)
		{
			const { activeStageId } = this.state;
			if (this.isReadOnly() || activeStageId === id || !this.props.onChange)
			{
				return;
			}

			const actionParams = {
				uid: this.uid,
				activeStageId,
				selectedStageId: id,
				selectedStatusId: statusId,
			};

			this.onBeforeHandleChange(actionParams)
				.then(() => {
					if (this.getConfig().animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
					{
						this.handleChangeBeforeAnimation(id);
					}
					else
					{
						this.prevActiveStageId = activeStageId;
						this.handleChange(id)
							.then(() => {
								this.animate(id);
							})
							.catch(console.error);
					}
				})
				.catch(console.error);
		}

		handleChangeBeforeAnimation(id)
		{
			this.handleChange(id)
				.then(({ action = {} }) => {
					if (action === 'delete')
					{
						return Promise.reject();
					}

					return this.updateBeforeAnimation(id);
				})
				.then(() => this.forceUpdate({ columnId: id }))
				.catch(console.error);
		}

		forceUpdate(params)
		{
			if (this.props.forceUpdate)
			{
				this.props.forceUpdate(params);
			}
		}

		/**
		 * @private
		 * @param {number} stageId
		 * @returns {Promise}
		 */
		updateBeforeAnimation(stageId)
		{
			return new Promise((resolve) => {
				this.setState({ nextStageId: stageId }, () => this.animate(stageId, resolve));
			});
		}

		/**
		 * @public
		 * @param {number} stageId
		 * @returns {Promise}
		 */
		scrollTo(stageId)
		{
			return this.updateBeforeAnimation(stageId);
		}

		/**
		 * @returns {Array<Object>}
		 */
		getSliderStages()
		{
			const { activeStageId, nextStageId } = this.state;

			const stages = [...this.processStages, ...this.successStages, ...this.failedStages];

			let activeIndex;
			let prevIndex;

			if (this.getConfig().animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
			{
				const prevStage = nextStageId || activeStageId;

				activeIndex = StageSelectorField.getStageIndexById(stages, activeStageId);
				prevIndex = StageSelectorField.getStageIndexById(stages, prevStage);
			}
			else
			{
				activeIndex = StageSelectorField.getStageIndexById(stages, activeStageId);
				prevIndex = StageSelectorField.getStageIndexById(stages, this.prevActiveStageId);
			}

			if (prevIndex === activeIndex)
			{
				return this.getInitialSliderStages(stages, activeIndex, this.processStages);
			}

			let start = Math.min(activeIndex, prevIndex);
			const activeStage = stages[activeIndex];
			const prevStage = stages[prevIndex];
			if (start !== 0)
			{
				start -= 1;
			}

			if (!this.processStages.includes(activeStage))
			{
				if (this.processStages.includes(prevStage))
				{
					return [...this.processStages.slice(start), activeStage];
				}

				return [this.processStages[this.processStages.length - 1], activeStage];
			}

			if (this.successStages.includes(prevStage))
			{
				return [...this.processStages.slice(start), prevStage];
			}

			if (this.failedStages.includes(prevStage))
			{
				return [...this.processStages.slice(start), ...this.successStages];
			}

			const maxIndex = Math.max(prevIndex, activeIndex);

			return [...this.processStages, ...this.successStages].slice(start, maxIndex + 2);
		}

		getInitialSliderStages(stages, activeIndex, processStages)
		{
			const { activeStageId } = this.state;
			let start = 0;
			let end = 1;
			if (activeIndex === 0)
			{
				return stages.slice(activeIndex, 2);
			}

			if (stages[activeIndex - 1])
			{
				start = activeIndex - 1;
			}

			const isProgressStage = processStages.includes(activeStageId);

			if (!isProgressStage)
			{
				return [processStages[processStages.length - 1], stages[activeIndex]];
			}

			if (stages[activeIndex + 1])
			{
				end = isProgressStage ? 3 : 2;
			}

			if (stages.length <= 3)
			{
				return stages;
			}

			return stages.slice(start, activeIndex + end);
		}

		static getStageIndexById(stages, stageId)
		{
			return Math.max(stages.findIndex((id) => id === stageId), 0);
		}

		getCurrentSliderPosition(activeIndex)
		{
			if (activeIndex === 0)
			{
				return this.hasHiddenEmptyView() ? FIRST_STAGE_VIEW_WIDTH : STAGE_MARGIN + FIRST_STAGE_VIEW_WIDTH;
			}

			return activeIndex * (-STAGE_WIDTH - STAGE_MARGIN) + FIRST_STAGE_VIEW_WIDTH + 2 * STAGE_MARGIN;
		}

		getContentClickHandler()
		{
			return null;
		}

		getValue()
		{
			if (this.preparedValue === null || !isEqual(this.props.value, this.preparedValue))
			{
				this.preparedValue = this.prepareValue(this.props.value);
			}

			return this.preparedValue;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				externalWrapper: {
					borderBottomWidth: 0,
					paddingBottom: 0,
				},
				wrapper: {
					...styles.wrapper,
					marginLeft: 0,
					marginRight: 0,
					paddingTop: 0,
					paddingBottom: 0,
				},
				readOnlyWrapper: {
					...styles.readOnlyWrapper,
					marginLeft: 0,
					marginRight: 0,
					paddingBottom: 0,
					paddingTop: 0,
				},
				title: {
					...styles.title,
					marginLeft: 16,
				},
			};
		}

		getHiddenEmptyFieldStyles()
		{
			return null;
		}

		/**
		 * @private
		 * @param {number} stageId
		 * @param {function} resolve
		 */
		animate(stageId, resolve)
		{
			const activeIndex = StageSelectorField.getStageIndexById(this.currentStages, stageId);
			const offset = this.getCurrentSliderPosition(activeIndex);

			if (this.stageSliderRef)
			{
				this.isAnimationInProgress = true;
				this.stageSliderRef.animate({
					duration: 500,
					left: offset,
				}, () => {
					this.isAnimationInProgress = false;
					if (this.getConfig().animationMode === AnimationMode.ANIMATE_BEFORE_UPDATE)
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
				});
			}
		}
	}

	const getStageIcon = (color) => `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 29.9987C23.2843 29.9987 30 23.2833 30 14.9993C30 6.71543 23.2843 0 15 0C6.71572 0 0 6.71543 0 14.9993C0 23.2833 6.71572 29.9987 15 29.9987Z"/><path d="M6 9.89286C6 8.29518 7.32217 7 8.95315 7H17.1674C18.118 7 19.0106 7.4483 19.5654 8.20448L23.7224 13.8701C24.0925 14.3745 24.0925 15.0541 23.7224 15.5585L19.5654 21.2241C19.0106 21.9803 18.118 22.4286 17.1674 22.4286L8.95315 22.4286C7.32217 22.4286 6 21.1334 6 19.5357V9.89286Z" fill="${color}"/></svg>`;

	module.exports = {
		StageSelectorType: 'stage-selector',
		StageSelectorField,
		StageItemClass,
	};
});
