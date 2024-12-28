/**
 * @module layout/ui/stage-slider
 */
jn.define('layout/ui/stage-slider', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { StageItemClass, STAGE_WIDTH, STAGE_MARGIN } = require('layout/ui/stage-slider/item');

	const FIRST_STAGE_VIEW_WIDTH = Indent.L.toNumber();

	/**
	 * @class StageSlider
	 */
	class StageSlider extends PureComponent
	{
		static getStageIndexById(stages, stageId)
		{
			return Math.max(stages.indexOf(stageId), 0);
		}

		constructor(props)
		{
			super(props);

			this.currentStages = [];
			this.previousStages = [];
			this.prevActiveStageId = null;
			this.stageSliderRef = null;
			this.isAnimationInProgress = false;

			this.onStageClickHandler = this.onStageClick.bind(this);
			this.onStageLongClickHandler = this.onStageLongClick.bind(this);

			this.animateToStage = this.animateToStage.bind(this);
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		get isReadOnly()
		{
			return BX.prop.getBoolean(this.props, 'isReadOnly', false);
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

		get showLoadingAnimation()
		{
			return BX.prop.getBoolean(this.props, 'showLoadingAnimation', true);
		}

		/**
		 * @param {object} stage
		 */
		onStageClick(stage)
		{
			if (this.isAnimationInProgress)
			{
				return;
			}

			if (this.props.onStageClick)
			{
				this.props.onStageClick(stage, this.props.activeStageId);
			}
		}

		/**
		 * @param {object} stage
		 */
		onStageLongClick(stage)
		{
			if (this.isAnimationInProgress)
			{
				return;
			}

			if (this.props.onStageLongClick)
			{
				this.onStageLongClick(stage);
			}
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			if (
				Number.isInteger(newProps.activeStageId)
				&& Number.isInteger(this.props.activeStageId)
				&& newProps.activeStageId !== this.props.activeStageId
			)
			{
				// change stage
				this.prevActiveStageId = this.props.activeStageId;
				this.previousStages = this.currentStages;
			}
			else
			{
				// change kanban
				this.prevActiveStageId = null;
			}
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (
				Number.isInteger(this.prevActiveStageId)
				&& Number.isInteger(this.props.activeStageId)
				&& !this.isAnimationInProgress)
			{
				this.animateToStage(this.props.activeStageId);
			}
		}

		render()
		{
			this.currentStages = this.getSliderStages();

			if (
				this.currentStages.length === 0
				|| !Number.isInteger(this.props.activeStageId)
				|| !this.isActiveStageExist()
			)
			{
				return this.renderEmptyContent();
			}

			const activeIndex = StageSlider.getStageIndexById(
				this.currentStages,
				this.props.activeStageId,
			);
			const prevIndex = StageSlider.getStageIndexById(
				this.currentStages,
				(this.prevActiveStageId || this.props.activeStageId),
			);
			const currentPosition = this.getCurrentSliderPosition(prevIndex);

			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						height: 54,
					},
				},
				View(
					{
						ref: (ref) => {
							this.stageSliderRef = ref;
						},
						style: {
							width: (STAGE_WIDTH + STAGE_MARGIN) * this.currentStages.length + FIRST_STAGE_VIEW_WIDTH,
							flexDirection: 'row',
							height: 54,
							alignItems: 'center',
							position: 'absolute',
							top: 0,
							left: currentPosition,
						},
					},
					...this.renderStages(this.currentStages, activeIndex),
				),
			);
		}

		isActiveStageExist()
		{
			const stages = [...this.processStages, ...this.successStages, ...this.failedStages];

			return stages.includes(this.props.activeStageId);
		}

		renderEmptyContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						height: 54,
						alignItems: 'center',
						paddingLeft: FIRST_STAGE_VIEW_WIDTH + STAGE_MARGIN,
					},
				},
				new StageItemClass({
					stage: {
						color: Color.base6.toHex(),
					},
					isReversed: this.isReversed,
					id: 0,
					activeIndex: 0,
					showMenu: false,
					shouldAnimateOnLoading: this.showLoadingAnimation,
					initialOpacity: 1,
				}),
				new StageItemClass({
					stage: {
						color: Color.base6.toHex(),
					},
					isReversed: this.isReversed,
					id: 1,
					activeIndex: 0,
					showMenu: false,
					shouldAnimateOnLoading: this.showLoadingAnimation,
					initialOpacity: 0.3,
				}),
			);
		}

		/**
		 * @return {array}
		 */
		getSliderStages()
		{
			const allStages = this.getAllStages();
			const activeIndex = StageSlider.getStageIndexById(allStages, this.props.activeStageId);
			const prevIndex = StageSlider.getStageIndexById(allStages, this.prevActiveStageId);

			if (prevIndex === activeIndex)
			{
				return this.getInitialSliderStages(allStages, activeIndex, this.processStages);
			}

			const newStages = [...this.previousStages];

			if (prevIndex < activeIndex)
			{
				// Moving forward
				for (let i = prevIndex; i <= activeIndex + 1; i++)
				{
					if (allStages[i] && !newStages.includes(allStages[i]))
					{
						newStages.push(allStages[i]);
					}
				}
			}
			else
			{
				// Moving backward
				if (!newStages.includes(allStages[prevIndex]))
				{
					newStages.push(allStages[prevIndex]);
				}

				if (allStages[activeIndex - 1] && !newStages.includes(allStages[activeIndex - 1]))
				{
					newStages.push(allStages[activeIndex - 1]);
				}
			}

			return newStages;
		}

		getAllStages()
		{
			if (this.successStages.length === 0 && this.failedStages.length === 0)
			{
				return [...this.processStages];
			}

			const activeFailedStage = this.failedStages.includes(this.props.activeStageId);
			const activeSuccessStage = this.successStages.includes(this.props.activeStageId);
			const activeProcessStage = this.processStages.includes(this.props.activeStageId);

			const previousSuccessStage = this.successStages.includes(this.prevActiveStageId);
			const previousProcessStage = this.processStages.includes(this.prevActiveStageId);

			const isLastProcessStage = this.processStages[this.processStages.length - 1] === this.prevActiveStageId;

			if (isLastProcessStage && activeFailedStage)
			{
				return [...this.processStages, this.successStages[0], this.props.activeStageId];
			}

			if (isLastProcessStage)
			{
				return [...this.processStages, this.successStages[0]];
			}

			if (previousProcessStage && activeSuccessStage)
			{
				return [...this.processStages, this.props.activeStageId];
			}

			if (previousProcessStage && activeFailedStage)
			{
				return [...this.processStages, this.props.activeStageId];
			}

			if (previousSuccessStage && activeProcessStage)
			{
				return [...this.processStages, this.prevActiveStageId];
			}

			if (previousSuccessStage && activeFailedStage)
			{
				return [...this.processStages, this.prevActiveStageId, this.props.activeStageId];
			}

			return [...this.processStages, ...this.successStages, ...this.failedStages];
		}

		/**
		 * @param {array} stages
		 * @param {number} activeIndex
		 * @param {array} processStages
		 * @return {array}
		 */
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

		/**
		 * @param {number} activeIndex
		 */
		getCurrentSliderPosition(activeIndex)
		{
			if (activeIndex === 0)
			{
				return STAGE_MARGIN + FIRST_STAGE_VIEW_WIDTH;
			}

			return activeIndex * (-STAGE_WIDTH - STAGE_MARGIN) + FIRST_STAGE_VIEW_WIDTH + 2 * STAGE_MARGIN;
		}

		/**
		 * @param {array} currentStages
		 * @param {number} activeIndex
		 */

		renderStages(currentStages, activeIndex)
		{
			throw new Error('Method renderStages must be implemented');
		}

		/**
		 * @param {number} stageId
		 * @param resolve
		 */
		animateToStage(stageId, resolve = null)
		{
			const activeIndex = StageSlider.getStageIndexById(this.currentStages, stageId);
			const offset = this.getCurrentSliderPosition(activeIndex);

			if (this.stageSliderRef && stageId)
			{
				this.isAnimationInProgress = true;
				this.stageSliderRef.animate({
					duration: 500,
					left: offset,
				}, () => {
					this.isAnimationInProgress = false;
				});
			}
		}
	}

	StageSlider.propTypes = {
		forwardedRef: PropTypes.func,
		stageIdsBySemantics: PropTypes.object.isRequired,

		isReversed: PropTypes.bool,
		isReadOnly: PropTypes.bool,

		onStageClick: PropTypes.func,
		onStageLongClick: PropTypes.func,
	};

	StageSlider.defaultProps = {
		isReversed: false,
	};

	module.exports = {
		StageSlider,
		StageItemClass,
	};
});
