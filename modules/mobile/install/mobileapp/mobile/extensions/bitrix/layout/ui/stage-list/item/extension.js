/**
 * @module layout/ui/stage-list/item
 */
jn.define('layout/ui/stage-list/item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');

	const MIN_STAGE_HEIGHT = 71;

	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};

	const { PureComponent } = require('layout/pure-component');
	const { StageStep } = require('layout/ui/stage-list/item/step');

	const svgImages = {
		editButtonIcon: '<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.5505 0.708708C11.9426 0.31773 12.5779 0.319865 12.9674 0.71347L14.2992 2.05937C14.6867 2.45089 14.6846 3.08201 14.2945 3.47092L5.28648 12.4522L2.54781 9.68469L11.5505 0.708708ZM0.00953897 14.6436C-0.0163586 14.7416 0.0113888 14.8452 0.0816823 14.9173C0.153826 14.9894 0.257416 15.0172 0.355457 14.9894L3.41693 14.1646L0.834563 11.5831L0.00953897 14.6436Z" fill="#6a737f"/></svg>',
		successStageFlagIcon: '<svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.0644531 0.19812V15.7924H2.6635V10.5943H13.926L10.659 5.39621L13.926 0.19812H0.0644531Z" fill="#9DCF00"/></svg>',
		failedStageFlagIcon: '<svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.332031 0.19812V15.7924H2.93107V10.5943H5.66641C5.95968 7.45074 8.39145 4.92814 11.4925 4.49569L14.1936 0.19812H0.332031Z" fill="#ff5752"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4407 16.2967C15.2366 16.2967 17.5031 14.0302 17.5031 11.2343C17.5031 8.43843 15.2366 6.17192 12.4407 6.17192C9.6448 6.17192 7.37829 8.43843 7.37829 11.2343C7.37829 14.0302 9.6448 16.2967 12.4407 16.2967ZM15.3766 13.1918L14.3979 14.1705L12.4404 12.2131L10.483 14.1705L9.50422 13.1918L11.4617 11.2343L9.50422 9.27686L10.483 8.29813L12.4404 10.2556L14.3979 8.29813L15.3766 9.27686L13.4192 11.2343L15.3766 13.1918Z" fill="#ff5752"/></svg>',
		allStagesBadge: '<svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 0H0V2H2V0Z" fill="#d5d7db"/><path d="M16 0H4V2H16V0Z" fill="#d5d7db"/><path d="M4 10H16V12H4V10Z" fill="#d5d7db"/><path d="M2 10H0V12H2V10Z" fill="#d5d7db"/><path d="M4 5H16V7H4V5Z" fill="#d5d7db"/><path d="M2 5H0V7H2V5Z" fill="#d5d7db"/></svg>',
	};
	const dragButtonSvg = '<svg width="6" height="14" viewBox="0 0 6 14" fill="none" xmlns="http://www.w3.org/2000/svg"><g><path d="M2 0H0V2H2V0Z" fill="#959ca4"/><path d="M6 0H4V2H6V0Z" fill="#959ca4"/><path d="M0 4H2V6H0V4Z" fill="#959ca4"/><path d="M6 4H4V6H6V4Z" fill="#959ca4"/><path d="M0 8H2V10H0V8Z" fill="#959ca4"/><path d="M6 8H4V10H6V8Z" fill="#959ca4"/><path d="M0 12H2V14H0V12Z" fill="#959ca4"/><path d="M6 12H4V14H6V12Z" fill="#959ca4"/></g></svg>';

	/**
	 * @class StageListItem
	 */
	class StageListItem extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.onSelectedStage = this.handlerOnSelectedStage.bind(this);
		}

		get counter()
		{
			return BX.prop.get(this.props, 'counter', {});
		}

		get unsuitable()
		{
			return BX.prop.getBoolean(this.counter, 'dropzone', false);
		}

		get showTotal()
		{
			return BX.prop.getBoolean(this.props, 'showTotal', false);
		}

		get showCount()
		{
			return BX.prop.getBoolean(this.props, 'showCount', false);
		}

		get showCounters()
		{
			return BX.prop.getBoolean(this.props, 'showCounters', false);
		}

		get showAllStagesItem()
		{
			return BX.prop.getBoolean(this.props, 'showAllStagesItem', false);
		}

		get hideBadge()
		{
			return BX.prop.getBoolean(this.props, 'hideBadge', false);
		}

		get showArrow()
		{
			return BX.prop.getBoolean(this.props, 'showArrow', false);
		}

		get stage()
		{
			return BX.prop.get(this.props, 'stage', {});
		}

		get showTunnels()
		{
			return BX.prop.getBoolean(this.props, 'showTunnels', false);
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		isUnsuitable()
		{
			if (this.showAllStagesItem)
			{
				return this.unsuitable;
			}

			return false;
		}

		handlerOnSelectedStage()
		{
			if (!this.enableStageSelect())
			{
				return;
			}

			Haptics.impactLight();

			const { onSelectedStage, stage } = this.props;
			if (typeof onSelectedStage === 'function')
			{
				onSelectedStage(stage);
			}
		}

		isReadOnly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		enableStageSelect()
		{
			return BX.prop.getBoolean(this.props, 'enableStageSelect', false);
		}

		canMoveStages()
		{
			return BX.prop.getBoolean(this.props, 'canMoveStages', true);
		}

		renderBadgeContainer()
		{
			const { indexContainer, indexText, dragButton, allStagesIcon } = styles;
			const { stage, index } = this.props;

			if (stage.semantics && stage.semantics !== SEMANTICS.PROCESS)
			{
				return this.renderFinalBadge();
			}

			let indexElement = null;
			if (Number(stage.id))
			{
				indexElement = Text(
					{
						style: indexText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: String(index),
					},
				);
			}
			else if (this.showAllStagesItem)
			{
				indexElement = Image(
					{
						style: allStagesIcon,
						tintColor: AppTheme.colors.base3,
						svg: {
							content: svgImages.allStagesBadge,
						},
					},
				);
			}

			return View(
				{
					testId: 'StageListItemIndexBadge',
					style: indexContainer,
				},
				!this.isReadOnly() && this.canMoveStages() && Image(
					{
						style: dragButton,
						tintColor: AppTheme.colors.base3,
						svg: {
							content: dragButtonSvg,
						},
					},
				),
				indexElement,
			);
		}

		renderFinalBadge()
		{
			return View(
				{
					testId: 'StageListItemIndexBadge',
					style: {
						width: 60,
						height: 26,
						justifyContent: 'center',
						marginTop: 23,
						alignItems: 'center',
					},
				},
				Image(
					{
						style: {
							width: this.props.stage.semantics === SEMANTICS.SUCCESS ? 14 : 18,
							height: this.props.stage.semantics === SEMANTICS.SUCCESS ? 16 : 17,
							opacity: (this.isUnsuitable() ? 0.3 : 1),
						},
						resizeMode: 'contain',
						svg: {
							content: this.props.stage.semantics === SEMANTICS.SUCCESS
								? svgImages.successStageFlagIcon
								: svgImages.failedStageFlagIcon,
						},
					},
				),
			);
		}

		renderContent()
		{
			const { index, active, showContentBorder } = this.props;

			// @todo hide for users without permissions too
			const isDummyEditButton = (!this.stage.id || this.isReadOnly());

			return View(
				{
					testId: 'StageListItemInnerContent',
					style: {
						borderBottomWidth: showContentBorder ? 1 : 0,
						borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
						flexDirection: 'row',
						flexGrow: 2,
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flexGrow: 2,
							paddingTop: 11,
							paddingBottom: this.hasTunnels(this.stage) ? 17 : 11,
						},
					},
					new StageStep({
						index,
						active,
						stage: this.stage,
						counter: this.counter,
						showTotal: !this.isUnsuitable() && this.showTotal,
						showCount: !this.isUnsuitable() && this.showCount,
						showCounters: !this.isUnsuitable() && this.showCounters,
						showAllStagesItem: this.showAllStagesItem,
						unsuitable: this.isUnsuitable(),
						showArrow: this.showArrow,
						isReversed: this.isReversed,
					}),
					this.renderAdditionalContent(this.stage),
				),
				this.renderEditButton(isDummyEditButton),
			);
		}

		hasTunnels(stage)
		{
			return this.showTunnels && stage.tunnels !== 0;
		}

		renderAdditionalContent(stage)
		{
			return null;
		}

		/**
		 * @param {boolean} isDummyView
		 * @returns {*}
		 */
		renderEditButton(isDummyView = false)
		{
			const testId = 'StageListItemEditButton';

			if (isDummyView)
			{
				return View(
					{
						testId,
						style: styles.editButtonContainer,
					},
				);
			}

			return View(
				{
					testId,
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						...styles.editButtonContainer,
					},
					onClick: () => {
						this.handlerOnOpenStageDetail(this.props.stage);
					},
				},
				Image(
					{
						tintColor: AppTheme.colors.base3,
						style: styles.editButtonIcon,
						svg: {
							content: svgImages.editButtonIcon,
						},
					},
				),
			);
		}

		handlerOnOpenStageDetail(stage)
		{
			const { onOpenStageDetail } = this.props;
			if (typeof onOpenStageDetail === 'function')
			{
				onOpenStageDetail(stage);
			}
		}

		getStageTestId()
		{
			const { id, color } = this.props.stage || {};
			const { index, active } = this.props;

			return `StageListItem-${id}-${index}-${color}-${active}`;
		}

		render()
		{
			if (!this.stage)
			{
				return View();
			}

			return View(
				{
					testId: this.getStageTestId(),
					style: styles.wrapper(this.isStageEnabled()),
					onClick: this.enableStageSelect()
						&& this.isStageEnabled()
						&& this.onSelectedStage,
				},
				this.hideBadge ? null : this.renderBadgeContainer(),
				this.renderContent(),
			);
		}

		isStageEnabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}
	}

	const styles = {
		wrapper: (enabled) => ({
			flexDirection: 'row',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			opacity: enabled ? 1 : 0.52,
			minHeight: MIN_STAGE_HEIGHT,
		}),
		indexContainer: {
			justifyContent: 'center',
			alignItems: 'center',
			alignSelf: 'flex-start',
			flexDirection: 'row',
			maxWidth: 60,
			width: '100%',
			marginTop: 25,
		},
		dragButton: {
			width: 6,
			height: 14,
			marginRight: 9,
		},
		indexText: {
			color: AppTheme.colors.base3,
			fontWeight: '500',
			fontSize: 14,
			flexWrap: 'no-wrap',
		},
		allStagesIcon: {
			margin: 4,
			width: 16,
			height: 12,
		},
		editButtonContainer: {
			width: 46,
			height: 37,
			marginLeft: 6,
			marginRight: 9,
			alignSelf: 'flex-start',
			marginTop: 17,
		},
		editButtonIcon: {
			width: 14,
			height: 14,
		},
	};

	module.exports = { StageListItem, MIN_STAGE_HEIGHT };
});
