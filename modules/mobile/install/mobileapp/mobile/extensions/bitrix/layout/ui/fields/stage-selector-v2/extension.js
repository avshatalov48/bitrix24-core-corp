/**
 * @module layout/ui/fields/stage-selector-v2
 */
jn.define('layout/ui/fields/stage-selector-v2', (require, exports, module) => {
	const { BaseField } = require('layout/ui/fields/base');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { throttle } = require('utils/function');
	const { Haptics } = require('haptics');

	const getStageIcon = (color) => `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 29.9987C23.2843 29.9987 30 23.2833 30 14.9993C30 6.71543 23.2843 0 15 0C6.71572 0 0 6.71543 0 14.9993C0 23.2833 6.71572 29.9987 15 29.9987Z"/><path d="M6 9.89286C6 8.29518 7.32217 7 8.95315 7H17.1674C18.118 7 19.0106 7.4483 19.5654 8.20448L23.7224 13.8701C24.0925 14.3745 24.0925 15.0541 23.7224 15.5585L19.5654 21.2241C19.0106 21.9803 18.118 22.4286 17.1674 22.4286L8.95315 22.4286C7.32217 22.4286 6 21.1334 6 19.5357V9.89286Z" fill="${color}"/></svg>`;

	/**
	 * @class StageSelectorV2Field
	 */
	class StageSelectorV2Field extends BaseField
	{
		constructor(props)
		{
			super(props);
			this.stageSliderRef = null;
			this.bindForwardedRef = this.bindForwardedRef.bind(this);

			this.onStageClick = this.onStageClick.bind(this);
			this.onStageLongClick = this.onStageLongClick.bind(this);
			this.onChangeStageHandler = throttle(this.onChangeStage.bind(this), 500);
			this.state.showLoadingAnimation = !this.initiallyHidden;
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.getConfig(), 'isReversed', false);
		}

		get initiallyHidden()
		{
			return BX.prop.getBoolean(this.getConfig(), 'initiallyHidden', false);
		}

		renderReadOnlyContent()
		{
			return this.renderEditableContent();
		}

		renderEditableContent()
		{
			throw new Error('Method renderStages must be implemented');
		}

		/**
		 * @public
		 */
		show()
		{
			this.fieldContainerRef?.animate({
				opacity: 1,
				height: 54,
				duration: 200,
			}, () => {
				this.setState({
					showLoadingAnimation: true,
				});
			});
		}

		/**
		 * @public
		 */
		hide()
		{
			this.fieldContainerRef?.animate({
				opacity: 0,
				height: 0,
				duration: 200,
			}, () => {
				this.setState({
					showLoadingAnimation: false,
				});
			});
		}

		/**
		 * @param {object} stage
		 * @param {number} activeStageId
		 */
		onStageClick(stage, activeStageId)
		{
			const { id } = stage;

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
				this.onChangeStageHandler(stage, activeStageId);
			}
		}

		/**
		 * @param {object} stage
		 * @param {number} activeStageId
		 */
		onStageLongClick(stage, activeStageId)
		{
			const { id } = stage;

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
				this.props.notifyAboutReadOnlyStatus(this);
			}
		}

		/**
		 * @param {object} stage
		 */
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
					// todo create phrase?
					title: BX.message('FIELDS_STAGE_SELECTOR_CHANGE_STAGE_TITLE'),
					showCancelButton: true,
					showActionLoader: false,
					isCustomIconColor: true,
				},
			});

			menu.show();
		}

		/**
		 * @param {object} stage
		 * @param {number} activeStageId
		 */
		onChangeStage(stage, activeStageId)
		{
			if (this.isReadOnly())
			{
				return;
			}

			Keyboard.dismiss();

			if (stage.id === activeStageId)
			{
				void this.openStageList(stage.id);
			}
			else
			{
				this.changeActiveStageId(stage.id, stage.statusId, activeStageId);
			}
		}

		/**
		 * @param {number} activeStageId
		 */
		async openStageList(activeStageId)
		{
			throw new Error('Method openStageList must be implemented');
		}

		/**
		 * @param {number} id
		 * @param {string} statusId
		 * @param {number} activeStageId
		 */
		changeActiveStageId(id, statusId, activeStageId)
		{
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

			Haptics.impactLight();

			this.onBeforeHandleChange(actionParams)
				.then(() => this.handleChange(id))
				.catch(console.error);
		}

		/**
		 * @param {number} id
		 */
		animate(id)
		{
			if (this.stageSliderRef)
			{
				this.stageSliderRef.animateToStage(id);
			}
		}

		/**
		 * @param {object} ref
		 */
		bindForwardedRef(ref)
		{
			this.stageSliderRef = ref;
		}

		/**
		 * @param {object} params
		 */
		forceUpdate(params)
		{
			if (this.props.forceUpdate)
			{
				this.props.forceUpdate(params);
			}
		}

		getExternalWrapperStyle()
		{
			const defaultStyles = super.getExternalWrapperStyle();

			if (this.initiallyHidden)
			{
				return {
					...defaultStyles,
					opacity: 0,
					height: 0,
				};
			}

			return defaultStyles;
		}
	}

	StageSelectorV2Field.propTypes = {
		...BaseField.propTypes,
		config: PropTypes.shape({
			// base field props
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			// stage selector v2 field props
			isReversed: PropTypes.bool,
			useStageChangeMenu: PropTypes.bool,
		}),
	};

	StageSelectorV2Field.defaultProps = {
		...BaseField.defaultProps,
		config: {
			isReversed: false,
			useStageChangeMenu: true,
		},
	};

	module.exports = {
		StageSelectorV2Field,
	};
});
