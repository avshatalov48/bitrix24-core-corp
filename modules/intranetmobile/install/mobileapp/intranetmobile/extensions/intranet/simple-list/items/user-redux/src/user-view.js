/**
 * @module intranet/simple-list/items/user-redux/user-view
 */
jn.define('intranet/simple-list/items/user-redux/user-view', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Indent, Color, Component } = require('tokens');
	const { Loc } = require('loc');
	const { showSafeToast } = require('toast');
	const { Icon } = require('ui-system/blocks/icon');
	const { Text2, Text5, Text6 } = require('ui-system/typography/text');
	const { ChipStatus, ChipStatusMode, ChipStatusDesign } = require('ui-system/blocks/chips/chip-status');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { Button, ButtonSize } = require('ui-system/form/buttons');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { UserName } = require('layout/ui/user/user-name');
	const { ActionMenu } = require('intranet/simple-list/items/user-redux/action-menu');
	const { Actions } = require('intranet/simple-list/items/user-redux/src/actions');
	const { EmployeeActions, EmployeeStatus, RequestStatus } = require('intranet/enum');
	const { openPhoneMenu } = require('communication/phone-menu');
	const { selectById } = require('intranet/statemanager/redux/slices/employees/selector');
	const store = require('statemanager/redux/store');
	const { useCallback } = require('utils/function');

	/**
	 * @class UserView
	 */
	class UserView extends PureComponent
	{
		render()
		{
			const requestStatus = selectById(store.getState(), this.props.id)?.requestStatus;

			return View(
				{
					style: {
						paddingTop: Indent.XS.toNumber(),
						paddingBottom: Indent.XL2.toNumber(),
						borderBottomColor: this.props.showBorder && Color.bgSeparatorPrimary.toHex(),
						marginHorizontal: Component.paddingLr.toNumber(),
						borderBottomWidth: 0.5,
					},
				},
				this.isAwaitingResponse(requestStatus) && this.renderTransparentForeground(),
				this.renderHeader(),
				this.renderStatuses(),
			);
		}

		renderHeader()
		{
			const { id } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'flex-start',
						alignContent: 'flex-start',
						paddingTop: Indent.L.toNumber(),
						paddingBottom: Indent.XL3.toNumber(),
					},
				},
				View(
					{
						style: {
							paddingTop: 10, // hack to align with buttons
						},
					},
					Avatar({
						id,
						size: 40,
						testId: `USER_VIEW_${id}`,
						withRedux: true,
					}),
				),
				this.renderInfo(),
				this.renderButtons(),
			);
		}

		renderInfo()
		{
			const { id, fullName, isAdmin, workPosition } = this.props;

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						flexWrap: 'wrap',
						paddingHorizontal: Indent.L.toNumber(),
					},
				},
				View(
					{
						style: {
							width: '100%',
							justifyContent: 'space-between',
							alignContent: 'flex-start',
							alignItems: 'flex-start',
							flexDirection: 'row',
							paddingBottom: Indent.XS2.toNumber(),
							paddingTop: 10, // hack to align with buttons
						},
					},
					UserName({
						id,
						testId: `USER_VIEW_PROFILE_NAME_${id}`,
						textElement: Text2,
						accent: true,
						style: {
							paddingRight: Indent.XS.toNumber(),
							maxWidth: isAdmin ? '65%' : null,
							marginRight: Indent.XS.toNumber(),
						},
						text: fullName,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
					isAdmin && this.renderAdminChip(),
				),
				View(
					{},
					workPosition && View(
						{
							style: { marginTop: Indent.XS2.toNumber() },
						},
						Text5({
							color: Color.base2,
							text: workPosition,
						}),
					),
					this.renderDepartment(),
				),
			);
		}

		renderDepartment()
		{
			const { department = [] } = this.props;
			let departmentNames = Object.values(department).join(', ');

			if (this.isExtranet())
			{
				departmentNames = Loc.getMessage('M_INTRANET_USER_STATUS_EXTRANET');
			}

			if (this.isCollaber())
			{
				departmentNames = Loc.getMessage('M_INTRANET_USER_STATUS_GUEST');
			}

			if (!departmentNames)
			{
				return null;
			}

			return View(
				{
					style: { marginTop: Indent.XS.toNumber() },
				},
				Text6({
					color: Color.base4,
					text: departmentNames,
				}),
			);
		}

		renderTransparentForeground()
		{
			return View({
				style: {
					position: 'absolute',
					width: '100%',
					// any value that is bigger or equal to cell height
					height: 300,
					backgroundColor: Color.bgContentPrimary.toHex(),
					opacity: 0.7,
					zIndex: 10,
				},
			});
		}

		renderAdminChip()
		{
			return ChipStatus({
				text: Loc.getMessage('M_INTRANET_USER_STATUS_ADMIN'),
				mode: ChipStatusMode.TINTED,
				design: ChipStatusDesign.SUCCESS,
				testId: 'user-view-status-admin',
				compact: true,
			});
		}

		renderButtons()
		{
			const { employeeStatus, id, personalMobile, personalPhone, canInvite } = this.props;
			const isInvited = employeeStatus === EmployeeStatus.INVITED.getValue();

			const menu = new ActionMenu({ userId: id, canInvite });

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-end',
						alignSelf: 'flex-start',
					},
				},
				(!isInvited || personalMobile || personalPhone) && Button({
					size: ButtonSize.M,
					leftIcon: isInvited ? Icon.PHONE_UP : Icon.CHATS,
					leftIconColor: Color.base4,
					backgroundColor: Color.bgContentPrimary,
					onClick: isInvited ? this.openPhoneMenu : this.openChat,
					testId: isInvited ? 'user-view-open-phone-menu' : 'user-view-open-chat-button',
					style: {
						marginRight: menu.hasActions() ? Indent.S.toNumber() : 0,
					},
				}),
				menu.hasActions() && Button({
					size: ButtonSize.M,
					leftIcon: Icon.MORE,
					leftIconColor: Color.base4,
					backgroundColor: Color.bgContentPrimary,
					onClick: useCallback(() => menu.show(this.moreButtonRef)),
					testId: 'user-view-open-action-menu-button',
					forwardRef: (ref) => {
						this.moreButtonRef = ref;
					},
				}),
			);
		}

		renderStatuses()
		{
			const { employeeStatus } = this.props;
			const needShowBadge = (
				employeeStatus === EmployeeStatus.INVITED.getValue()
				|| employeeStatus === EmployeeStatus.INVITE_AWAITING_APPROVE.getValue()
			);

			return View(
				{
					style: {
						justifyContent: 'space-between',
						flexDirection: 'row',
						alignContent: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
						},
					},
					...this.getBottomChips(),
				),
				needShowBadge && BadgeCounter({
					testId: 'invited-or-awaiting-approval-counter',
					value: 1,
					design: BadgeCounterDesign.ALERT,
				}),
			);
		}

		getBottomChips()
		{
			const { employeeStatus, isMobileInstalled, isDesktopInstalled, canUserBeReinvited } = this.props;

			if (employeeStatus === EmployeeStatus.INVITED.getValue())
			{
				return [
					ChipStatus({
						text: canUserBeReinvited
							? Loc.getMessage('M_INTRANET_USER_STATUS_INVITED')
							: Loc.getMessage('M_INTRANET_USER_STATUS_INVITE_IS_SENDED'),
						design: canUserBeReinvited
							? ChipStatusDesign.PRIMARY
							: ChipStatusDesign.SUCCESS,
						mode: ChipStatusMode.TINTED,
						style: {
							marginRight: Indent.S.toNumber(),
						},
						testId: canUserBeReinvited
							? 'user-view-status-invite-is-resent'
							: 'user-view-status-invite-not-accepted',
					}),
					canUserBeReinvited && ChipButton({
						mode: ChipButtonMode.OUTLINE,
						design: ChipButtonDesign.GREY,
						icon: Icon.REFRESH,
						onClick: this.resendInvite,
						compact: true,
						testId: 'user-view-resend-invite-button',
					}),
				];
			}

			if (employeeStatus === EmployeeStatus.INVITE_AWAITING_APPROVE.getValue())
			{
				return [
					ChipStatus({
						text: Loc.getMessage('M_INTRANET_USER_STATUS_PENDING'),
						design: ChipStatusDesign.WARNING,
						mode: ChipStatusMode.TINTED,
						style: {
							marginRight: Indent.S.toNumber(),
						},
						testId: 'user-view-status-pending',
					}),
					ChipButton({
						mode: ChipButtonMode.OUTLINE,
						design: ChipButtonDesign.PRIMARY,
						icon: Icon.CHECK,
						onClick: this.acceptRequest,
						compact: true,
						style: {
							marginRight: Indent.S.toNumber(),
						},
						testId: 'user-view-accept-request-button',
					}),
					ChipButton({
						mode: ChipButtonMode.OUTLINE,
						design: ChipButtonDesign.GREY,
						icon: Icon.CROSS,
						onClick: this.rejectRequest,
						compact: true,
						style: {
							marginRight: Indent.S.toNumber(),
						},
						testId: 'user-view-reject-request-button',
					}),
				];
			}

			return [
				ChipButton({
					text: Loc.getMessage('M_INTRANET_USER_STATUS_MOBILE_APP_INSTALLED'),
					mode: ChipButtonMode.OUTLINE,
					design: isMobileInstalled ? ChipButtonDesign.PRIMARY : ChipButtonDesign.GREY,
					icon: Icon.MOBILE,
					onClick: this.openInviteToInstallMobileApp,
					compact: true,
					style: {
						marginRight: Indent.S.toNumber(),
					},
					testId: 'user-view-open-invite-button',
				}),
				ChipButton({
					text: Loc.getMessage('M_INTRANET_USER_STATUS_DESKTOP_INSTALLED'),
					mode: ChipButtonMode.OUTLINE,
					design: isDesktopInstalled ? ChipButtonDesign.PRIMARY : ChipButtonDesign.GREY,
					icon: Icon.SCREEN,
					onClick: this.openInviteToInstallDesktopApp,
					compact: true,
					style: {
						marginRight: Indent.S.toNumber(),
					},
					testId: 'user-view-is-desktop-installed',
				}),
			];
		}

		openChat = () => {
			void requireLazy('im:messenger/api/dialog-opener').then(({ DialogOpener }) => {
				DialogOpener.open({
					dialogId: this.props.id,
				});
			});
		};

		resendInvite = () => Actions.list[EmployeeActions.REINVITE.getValue()]({ userId: this.props.id });

		acceptRequest = () => Actions.list[EmployeeActions.CONFIRM_USER_REQUEST.getValue()]({ userId: this.props.id });

		rejectRequest = () => Actions.list[EmployeeActions.DECLINE_USER_REQUEST.getValue()]({ userId: this.props.id });

		openInviteToInstallMobileApp = () => {
			const message = this.props.isMobileInstalled
				? Loc.getMessage('M_INTRANET_RECOMMENDATION_TO_INSTALL_MOBILE_ALREADY_INSTALLED')
				: Loc.getMessage('M_INTRANET_RECOMMENDATION_TO_INSTALL_MOBILE_IS_NOT_INSTALLED');

			showSafeToast(
				{
					...this.getToastParams(),
					message,
					icon: Icon.MOBILE,
				},
				layout,
			);
		};

		openInviteToInstallDesktopApp = () => {
			const message = this.props.isDesktopInstalled
				? Loc.getMessage('M_INTRANET_RECOMMENDATION_TO_INSTALL_DESKTOP_ALREADY_INSTALLED')
				: Loc.getMessage('M_INTRANET_RECOMMENDATION_TO_INSTALL_DESKTOP_IS_NOT_INSTALLED');

			showSafeToast(
				{
					...this.getToastParams(),
					message,
					icon: Icon.SCREEN,
				},
				layout,
			);
		};

		openPhoneMenu = () => {
			const { personalMobile, personalPhone } = this.props;
			if (!personalMobile && !personalPhone)
			{
				showSafeToast(
					{
						...this.getToastParams(),
						message: Loc.getMessage('M_INTRANET_PERSONAL_MOBILE_IS_NOT_FILLED'),
					},
					layout,
				);
			}
			const canUseTelephony = BX.componentParameters.get('canUseTelephony', 'N') === 'Y';
			openPhoneMenu(
				{
					number: personalMobile || personalPhone,
					canUseTelephony,
					analyticsSection: 'userList',
				},
			);
		};

		getToastParams()
		{
			return {
				textSize: 14,
				time: 1,
			};
		}

		isAwaitingResponse(requestStatus)
		{
			return requestStatus === RequestStatus.PENDING.getValue();
		}

		isExtranet()
		{
			const { isExtranet } = this.props;

			return Boolean(isExtranet);
		}

		isCollaber()
		{
			const { isCollaber } = this.props;

			return Boolean(isCollaber);
		}
	}

	module.exports = { UserView };
});
