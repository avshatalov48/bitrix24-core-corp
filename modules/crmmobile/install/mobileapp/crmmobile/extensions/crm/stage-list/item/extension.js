/**
 * @module crm/stage-list/item
 */
jn.define('crm/stage-list/item', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { dragButton: dragButtonSvg } = require('crm/assets/common');

	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};

	const FIRST_TUNNEL_ADDITIONAL_HEIGHT = 5;
	const TUNNEL_HEIGHT = 22;
	const TUNNEL_MARGIN_TOP = 9;
	const MIN_STAGE_HEIGHT = 71;

	class StageListItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initStageProps(props);

			this.onSelectedStage = this.handlerOnSelectedStage.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.initStageProps(props);
		}

		initStageProps(props)
		{
			this.unsuitable = BX.prop.getBoolean(props, 'unsuitable', false);
			this.showTunnels = BX.prop.get(props, 'showTunnels', false);
			this.showTotal = !this.unsuitable && BX.prop.get(props, 'showTotal', false);
			this.showCount = !this.unsuitable && BX.prop.get(props, 'showCount', false);
			this.showCounters = !this.unsuitable && BX.prop.get(props, 'showCounters', false);
			this.showAllStagesItem = BX.prop.get(props, 'showAllStagesItem', false);
			this.hideBadge = BX.prop.get(props, 'hideBadge', false);
			this.showArrow = BX.prop.get(props, 'showArrow', false);
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
			const { stage } = this.props;

			if (stage.semantics && stage.semantics !== SEMANTICS.PROCESS)
			{
				return this.renderFinalBadge();
			}

			let indexElement = null;
			if (stage.id)
			{
				indexElement = Text(
					{
						style: indexText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: String(stage.index),
					},
				);
			}
			else if (this.showAllStagesItem)
			{
				indexElement = Image(
					{
						style: allStagesIcon,
						svg: {
							content: svgImages.allStagesBadge,
						},
					},
				);
			}

			return View(
				{
					style: indexContainer,
				},
				!this.isReadOnly() && this.canMoveStages() && Image(
					{
						style: dragButton,
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
							opacity: (this.unsuitable ? 0.3 : 1),
						},
						resizeMode: 'contain',
						svg: {
							content: this.props.stage.semantics === SEMANTICS.SUCCESS ? svgImages.successStageFlagIcon : svgImages.failedStageFlagIcon,
						},
					},
				),
			);
		}

		renderContent(stage)
		{
			// @todo hide for users without permissions too
			const isDummyEditButton = (!stage.id || this.isReadOnly());

			return View(
				{
					style: styles.content(stage.showContentBorder),
				},
				View(
					{
						style: styles.stageView(this.hasTunnels(stage)),
					},
					new Crm.StageStep({
						stage,
						showTotal: this.showTotal,
						showCount: this.showCount,
						showCounters: this.showCounters,
						showAllStagesItem: this.showAllStagesItem,
						unsuitable: this.unsuitable,
						showArrow: this.showArrow,
					}),
					(this.showTunnels ? this.renderTunnels(stage) : null),
				),
				this.renderEditButton(isDummyEditButton),
			);
		}

		hasTunnels(stage)
		{
			return this.showTunnels && stage.tunnels !== 0;
		}

		/**
		 * @param {boolean} isDummyView
		 * @returns {*}
		 */
		renderEditButton(isDummyView = false)
		{
			if (isDummyView)
			{
				return View(
					{
						style: styles.editButtonContainer,
					},
				);
			}

			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						...styles.editButtonContainer,
					},
					onClick: () => {
						const { key, type, index, ...stage } = this.props.stage;
						this.handlerOnOpenStageDetail(stage);
					},
				},
				Image(
					{
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

		renderTunnels(stage)
		{
			if (stage.tunnels === 0)
			{
				return null;
			}

			const tunnels = stage.tunnels.map((tunnel, index) => this.renderTunnel(tunnel, index));

			return View(
				{
					style: styles.tunnelsContainer,
				},
				...tunnels,
			);
		}

		renderTunnel(tunnel, index)
		{
			return View(
				{
					style: styles.tunnelWrapper(index),
				},
				View(
					{
						style: {
							paddingBottom: 6,
							paddingLeft: index === 0 ? 0 : 4,
						},
					},
					Image(
						{
							style: styles.tunnelVector(index),
							svg: {
								content: index === 0 ? svgImages.tunnelFirstVector : svgImages.tunnelVector,
							},
						},
					),
				),
				View(
					{
						style: styles.tunnelContentContainer,
					},
					new Crm.Tunnel({ ...tunnel }),
				),
			);
		}

		render()
		{
			const { stage } = this.props;
			return View(
				{
					style: styles.wrapper(this.isStageEnabled()),
					onClick: this.enableStageSelect() && this.isStageEnabled() && this.onSelectedStage,
				},
				this.hideBadge ? null : this.renderBadgeContainer(),
				this.renderContent(stage),
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
			backgroundColor: '#fff',
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
			color: '#bdc1c6',
			fontWeight: '500',
			fontSize: 14,
			flexWrap: 'no-wrap',
		},
		allStagesIcon: {
			margin: 4,
			width: 16,
			height: 12,
		},
		content: (showContentBorder) => (
			{
				borderBottomWidth: showContentBorder ? 1 : 0,
				borderBottomColor: '#e6e7e9',
				flexDirection: 'row',
				flexGrow: 2,
				alignItems: 'center',
			}
		),
		stageView: (hasTunnels) => ({
			flexDirection: 'column',
			flexGrow: 2,
			paddingTop: 11,
			paddingBottom: hasTunnels ? 17 : 11,
		}),
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
		tunnelsContainer: {
			marginLeft: 6,
			marginTop: -8,
		},
		tunnelWrapper: (index) => ({
			flexDirection: 'row',
			height: index === 0 ? TUNNEL_HEIGHT + FIRST_TUNNEL_ADDITIONAL_HEIGHT : TUNNEL_HEIGHT + TUNNEL_MARGIN_TOP,
			marginTop: index === 0 ? 0 : -(TUNNEL_MARGIN_TOP),
		}),
		tunnelContentContainer: {
			marginLeft: 6,
			flex: 1,
			alignSelf: 'flex-end',
		},
		tunnelVector: (index) => ({
			width: index === 0 ? 12 : 8,
			height: index === 0 ? 21 : 23,
		}),
	};

	const svgImages = {
		tunnelFirstVector: '<svg width="12" height="21" viewBox="0 0 12 21" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="12" height="21"/><circle cx="5" cy="5" r="3.75" fill="#c9ccd0" stroke="white" stroke-width="1.5"/><path d="M5 5V11.5625V17C5 18.6569 6.34315 20 8 20H11" stroke="#c9ccd0" stroke-width="1.5" stroke-linecap="round"/></svg>',
		tunnelVector: '<svg width="8" height="23" viewBox="0 0 8 23" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="8" height="23" fill="none"/><path d="M1 -7V20C1 21.1046 1.89543 22 3 22H7" stroke="#c9ccd0" stroke-width="1.5" stroke-linecap="round"/></svg>',
		editButtonIcon: '<svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M11.5505 0.708708C11.9426 0.31773 12.5779 0.319865 12.9674 0.71347L14.2992 2.05937C14.6867 2.45089 14.6846 3.08201 14.2945 3.47092L5.28648 12.4522L2.54781 9.68469L11.5505 0.708708ZM0.00953897 14.6436C-0.0163586 14.7416 0.0113888 14.8452 0.0816823 14.9173C0.153826 14.9894 0.257416 15.0172 0.355457 14.9894L3.41693 14.1646L0.834563 11.5831L0.00953897 14.6436Z" fill="#6a737f"/></svg>',
		successStageFlagIcon: '<svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.0644531 0.19812V15.7924H2.6635V10.5943H13.926L10.659 5.39621L13.926 0.19812H0.0644531Z" fill="#9DCF00"/></svg>',
		failedStageFlagIcon: '<svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.332031 0.19812V15.7924H2.93107V10.5943H5.66641C5.95968 7.45074 8.39145 4.92814 11.4925 4.49569L14.1936 0.19812H0.332031Z" fill="#ff5752"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4407 16.2967C15.2366 16.2967 17.5031 14.0302 17.5031 11.2343C17.5031 8.43843 15.2366 6.17192 12.4407 6.17192C9.6448 6.17192 7.37829 8.43843 7.37829 11.2343C7.37829 14.0302 9.6448 16.2967 12.4407 16.2967ZM15.3766 13.1918L14.3979 14.1705L12.4404 12.2131L10.483 14.1705L9.50422 13.1918L11.4617 11.2343L9.50422 9.27686L10.483 8.29813L12.4404 10.2556L14.3979 8.29813L15.3766 9.27686L13.4192 11.2343L15.3766 13.1918Z" fill="#ff5752"/></svg>',
		allStagesBadge: '<svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 0H0V2H2V0Z" fill="#d5d7db"/><path d="M16 0H4V2H16V0Z" fill="#d5d7db"/><path d="M4 10H16V12H4V10Z" fill="#d5d7db"/><path d="M2 10H0V12H2V10Z" fill="#d5d7db"/><path d="M4 5H16V7H4V5Z" fill="#d5d7db"/><path d="M2 5H0V7H2V5Z" fill="#d5d7db"/></svg>',
	};

	module.exports = { StageListItem, TUNNEL_HEIGHT, MIN_STAGE_HEIGHT, FIRST_TUNNEL_ADDITIONAL_HEIGHT };
});
