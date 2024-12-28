/**
 * @module tasks/layout/flow/detail/src/common
 */
jn.define('tasks/layout/flow/detail/src/common', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Duration } = require('utils/date');
	const { Alert } = require('alert');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { Card } = require('ui-system/layout/card');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { AhaMoment } = require('ui-system/popups/aha-moment');
	const { ChipStatus, ChipStatusMode, ChipStatusDesign } = require('ui-system/blocks/chips/chip-status');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ProfileView } = require('user/profile');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { H3 } = require('ui-system/typography/heading');
	const { Text4, Text6 } = require('ui-system/typography/text');
	const { Color, Indent, Typography } = require('tokens');

	const store = require('statemanager/redux/store');
	const { connect } = require('statemanager/redux/connect');
	const { selectById } = require('tasks/statemanager/redux/slices/flows');

	class FlowDetailCommon extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.ahaMomentButtonRef = null;
		}

		render()
		{
			if (Type.isNil(this.props.flow))
			{
				Alert.alert(
					Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_NOT_FOUND_ALERT_TITLE'),
					Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_NOT_FOUND_ALERT_DESCRIPTION'),
					() => {
						this.props.layout.close();
					},
				);

				return this.renderFlowDataNotFound();
			}

			return ScrollView(
				{
					style: {
						flex: 1,
						width: '100%',
					},
				},
				this.renderFlowTitle(),
				this.renderFlowDescription(),
				this.renderFlowDuration(),
				this.renderFlowEfficiency(),
				this.renderFlowAdministrator(),
			);
		}

		renderFlowDataNotFound = () => {
			return View(
				{
					style: {
						width: '100%',
						paddingVertical: Indent.L.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				H3({
					text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_NOT_FOUND_DESCRIPTION'),
					color: Color.base1,
				}),
			);
		};

		renderFlowTitle = () => {
			return View(
				{
					style: {
						width: '100%',
						paddingVertical: Indent.L.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				H3({
					text: this.props.flow.name,
					color: Color.base1,
				}),
				this.groupExistsInFlow() && this.renderFlowSubTitle(),
			);
		};

		groupExistsInFlow()
		{
			return this.props.flow.groupId > 0;
		}

		renderFlowSubTitle = () => {
			const { groupId, groupName, groupIsCollab, groupDialogId } = this.props.flow;

			return View(
				{
					style: {
						paddingVertical: Indent.XS.toNumber(),
						flexDirection: 'row',
						width: '100%',
					},
				},
				Text4({
					testId: `${this.testId}-flow-subtitle-label`,
					text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_SUBTITLE'),
					color: Color.base4,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						marginRight: Indent.XS.toNumber(),
					},
				}),
				Link4({
					testId: `${this.testId}-flow-subtitle-link`,
					text: groupName,
					color: Color.accentMainPrimary,
					numberOfLines: 1,
					mode: LinkMode.DASH,
					ellipsize: Ellipsize.END,
					style: {
						marginTop: Indent.XS2.toNumber(),
					},
					onClick: () => this.openGroupDetail(groupId, groupIsCollab, groupDialogId),
				}),
			);
		};

		renderFlowDescription = () => {
			const isEmpty = this.props.flow.description.length === 0;

			return new CollapsibleText({
				testId: `${this.testId}-flow-description-collapsible-text`,
				value: this.props.flow.description,
				moreButtonColor: Color.accentMainPrimary.toHex(),
				containerStyle: {
					width: '100%',
					paddingHorizontal: Indent.XL3.toNumber(),
					paddingBottom: isEmpty ? 0 : Indent.XL3.toNumber(),
					flexGrow: 0,
				},
				style: {
					...Typography.getTokenBySize({ size: 4 })?.getStyle(),
					color: Color.base2.toHex(),
				},
			});
		};

		renderFlowDuration = () => {
			return Card(
				{
					testId: `${this.testId}-flow-duration-card`,
					border: true,
					style: {
						marginHorizontal: Indent.XL3.toNumber(),
						marginBottom: Indent.M.toNumber(),
						flexDirection: 'row',
					},
				},
				Text4({
					testId: `${this.testId}-flow-duration-label-text`,
					text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_DURATION_LABEL_TEXT'),
					numberOfLines: 1,
					ellipsize: 'end',
					color: Color.base2,
				}),
				this.renderQuestionIcon(),
				Text4({
					testId: `${this.testId}-flow-duration-value-text`,
					text: this.getPlannedCompletionTimeText(),
					numberOfLines: 1,
					accent: true,
					ellipsize: 'end',
					color: Color.base2,
					style: {
						flex: 1,
						textAlign: 'right',
					},
				}),
			);
		};

		getPlannedCompletionTimeText()
		{
			const plannedCompletionTime = this.props.flow.plannedCompletionTime;

			if (!plannedCompletionTime)
			{
				return null;
			}

			return Duration.createFromMinutes(plannedCompletionTime / 60).format();
		}

		renderQuestionIcon()
		{
			return View(
				{
					ref: (ref) => {
						this.ahaMomentButtonRef = ref;
					},
					onClick: () => {
						this.showFlowAhaMoment(this.ahaMomentButtonRef);
					},
				},
				IconView({
					icon: iconTypes.outline.question,
					color: Color.base5,
					iconSize: 20,
				}),
			);
		}

		showFlowAhaMoment = (targetRef) => {
			AhaMoment.show({
				testId: `${this.testId}-flow-aha-moment`,
				targetRef,
				description: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_AHA_MOMENT_TEXT'),
				closeButton: false,
				disableHideByOutsideClick: false,
			});
		};

		openUserProfile = (userId) => {
			if (!userId)
			{
				return;
			}

			this.props.layout?.openWidget('list', {
				groupStyle: true,
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			})
				.then((list) => ProfileView.open({ userId, isBackdrop: true }, list))
				.catch(console.error);
		};

		renderFlowEfficiency()
		{
			return Card(
				{
					testId: `${this.testId}-flow-efficiency-card`,
					border: true,
					style: {
						marginHorizontal: Indent.XL3.toNumber(),
						marginBottom: Indent.M.toNumber(),
						flexDirection: 'row',
					},
				},
				Text4({
					testId: `${this.testId}-flow-efficiency-label-text`,
					text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_FLOW_EFFICIENCY_LABEL_TEXT'),
					numberOfLines: 1,
					ellipsize: 'end',
					color: Color.base2,
					style: {
						flex: 1,
					},
				}),
				ChipStatus({
					testId: `${this.testId}-flow-efficiency-value-chip-status`,
					mode: ChipStatusMode.TINTED,
					design: this.props.flow.efficiencySuccess ? ChipStatusDesign.SUCCESS : ChipStatusDesign.ALERT,
					text: `${Number(this.props.flow.efficiency)}%`,
				}),
			);
		}

		renderFlowAdministrator = () => {
			const { flow } = this.props;

			return Card(
				{
					testId: `${this.testId}-flow-administrator-card`,
					border: true,
					style: {
						marginHorizontal: Indent.XL3.toNumber(),
						marginBottom: Indent.M.toNumber(),
						flexDirection: 'row',
						alignItems: 'center',
					},
					onClick: () => {
						this.openUserProfile(flow.ownerId);
					},
				},
				Avatar({
					id: flow.ownerId,
					size: 32,
					withRedux: true,
					testId: `${this.testId}-flow-administrator-avatar`,
				}),
				View(
					{
						style: {
							flex: 1,
						},
					},
					Text6({
						testId: `${this.testId}-flow-administrator-label`,
						text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_ADMINISTRATOR_LABEL_TEXT'),
						color: Color.base4,
						style: {
							flexShrink: 1,
							marginLeft: Indent.M.toNumber(),
						},
					}),
					Text4({
						testId: `${this.testId}-flow-administrator-name`,
						text: flow.ownerFullName,
						color: Color.base2,
						style: {
							flexShrink: 1,
							marginLeft: Indent.M.toNumber(),
						},
					}),
				),
				View(
					{
						onClick: () => {
							// todo: replace after fix DialogOpener in immobile
							BX.postComponentEvent(
								'ImMobile.Messenger.Dialog:open',
								[{ dialogId: flow.ownerId }],
								'im.messenger',
							);
						},
					},
					IconView({
						icon: iconTypes.outline.message,
						color: Color.accentMainPrimary,
						iconSize: 24,
					}),
				),
			);
		};

		openGroupDetail = (groupId, groupIsCollab, groupDialogId) => {
			ProjectViewManager.open(env.userId, groupId, this.props.layout, groupIsCollab, groupDialogId);
		};
	}

	const mapStateToProps = (state, ownProps) => {
		const flowId = ownProps.id;
		const flow = selectById(state, flowId);

		if (Type.isNil(flow))
		{
			return { flow };
		}

		const {
			fullName: ownerFullName,
		} = usersSelector.selectById(state, Number(flow.ownerId));

		let groupName = null;
		let groupIsCollab = null;
		let groupDialogId = null;

		if (flow.groupId > 0)
		{
			const { name, isCollab, additionalData } = selectGroupById(store.getState(), flow.groupId);

			groupName = name;
			groupIsCollab = isCollab;
			groupDialogId = additionalData.DIALOG_ID;
		}

		const {
			id,
			name,
			description,
			plannedCompletionTime,
			efficiency,
			efficiencySuccess,
			ownerId,
			groupId,
		} = flow;

		return {
			flow: {
				id,
				name,
				description,
				plannedCompletionTime,
				efficiency,
				efficiencySuccess,
				ownerId,
				ownerFullName,
				groupId,
				groupName,
				groupIsCollab,
				groupDialogId,
			},
		};
	};

	module.exports = { FlowDetailCommon: connect(mapStateToProps)(FlowDetailCommon) };
});
