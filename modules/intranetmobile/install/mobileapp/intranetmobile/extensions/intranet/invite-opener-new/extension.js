/**
 * @module intranet/invite-opener-new
 */
jn.define('intranet/invite-opener-new', (require, exports, module) => {
	const { Notify } = require('notify');
	const { Invite, IntranetInviteAnalytics } = require('intranet/invite-new');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { InviteStatusBox } = require('intranet/invite-status-box');
	const { Tourist } = require('tourist');

	const ErrorCode = {
		POSSIBILITIES_RESTRICTED: 'Invite possibilities restricted',
		PERMISSIONS_RESTRICTED: 'Invite permissions restricted',
	};

	/**
	 * @param params
	 * @param {AnalyticsEvent} params.analytics
	 * @param {Boolean} params.multipleInvite
	 * @param {LayoutComponent} params.parentLayout
	 * @param {Object} params.openWidgetConfig
	 * @param {Function} params.onInviteSentHandler
	 * @param {Function} params.onInviteError
	 * @param {Function} params.onViewHiddenWithoutInvitingHandler
	 */
	const openIntranetInviteWidget = (params) => {
		if (env.isCollaber || env.extranet)
		{
			return;
		}

		Notify.showIndicatorLoading();
		getInviteSettings().then(
			(response) => processGetInviteSettingsFulfilled(response, params),
			(response) => processGetInviteSettingsRejected(response, params),
		)
			.catch((errors) => console.error(errors))
			.finally(() => Notify.hideCurrentIndicator());
	};

	const processGetInviteSettingsRejected = (response, params) => {
		const responseHasErrors = response.errors && response.errors.length > 0;
		if (responseHasErrors)
		{
			handleErrors(response.errors, params.onInviteError);
		}
	};

	const processGetInviteSettingsFulfilled = (response, params) => {
		setUserVisitedInvitations();

		const responseHasErrors = response.errors && response.errors.length > 0;
		if (responseHasErrors)
		{
			handleErrors(response.errors, params.onInviteError);

			return;
		}

		const {
			canCurrentUserInvite,
			canInviteByPhone,
			canInviteByLink,
			isBitrix24Included,
			adminInBoxRedirectLink,
		} = response.data;

		if (!isBitrix24Included)
		{
			if (env.isAdmin)
			{
				handleAdminCanInviteInWeb(params.onInviteError, adminInBoxRedirectLink, params.parentLayout);
			}
			else
			{
				handleOnlyAdminCanInvite(params.onInviteError, params.parentLayout);
			}

			return;
		}

		if (!canCurrentUserInvite || (!canInviteByPhone && !canInviteByLink))
		{
			handleUserHasNoPermissionsToInvite(params.onInviteError, params.parentLayout);

			return;
		}

		openInviteWidget({
			...extractResponseData(response.data),
			...params,
		});
	};

	const setUserVisitedInvitations = () => {
		Tourist.ready()
			.then(() => {
				if (Tourist.firstTime('visit_invitations'))
				{
					return Tourist.remember('visit_invitations')
						.then(() => {
							BX.postComponentEvent('onSetUserCounters', [
								{
									[String(env.siteId)]: { menu_invite: 0 },
								},
							]);
						})
						.catch(console.error);
				}

				// eslint-disable-next-line promise/no-return-wrap
				return Promise.resolve();
			})
			.catch(console.error);
	};

	const handleAdminCanInviteInWeb = (onInviteError, adminInBoxRedirectLink, parentLayout = PageManager) => {
		InviteStatusBox.open({
			backdropTitle: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE'),
			testId: 'status-box-invite-in-web',
			imageName: 'user-locked.svg',
			description: Loc.getMessage('INTRANET_INVITE_ADMIN_ONLY_IN_WEB_BOX_TEXT'),
			buttonText: Loc.getMessage('INTRANET_INVITE_GO_TO_WEB_BUTTON_TEXT'),
			parentWidget: parentLayout,
			onButtonClick: () => {
				setTimeout(() => {
					qrauth.open({
						redirectUrl: adminInBoxRedirectLink,
						showHint: true,
						analyticsSection: 'userList',
					});
				}, 500);
			},
		});

		if (onInviteError)
		{
			onInviteError([new Error(ErrorCode.POSSIBILITIES_RESTRICTED)]);
		}
	};

	const handleOnlyAdminCanInvite = (onInviteError, parentLayout = PageManager) => {
		InviteStatusBox.open({
			backdropTitle: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE'),
			testId: 'status-box-no-permission',
			imageName: 'user-locked.svg',
			parentWidget: parentLayout,
			description: Loc.getMessage('INTRANET_INVITE_ADMIN_ONLY_BOX_TEXT'),
			buttonText: Loc.getMessage('INTRANET_INVITE_DISABLED_BOX_BUTTON_TEXT'),
		});

		if (onInviteError)
		{
			onInviteError([new Error(ErrorCode.PERMISSIONS_RESTRICTED)]);
		}
	};

	const handleUserHasNoPermissionsToInvite = (onInviteError, parentLayout = PageManager) => {
		InviteStatusBox.open({
			backdropTitle: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE'),
			testId: 'status-box-no-invitation',
			imageName: 'no-invitation.svg',
			parentWidget: parentLayout,
			description: Loc.getMessage('INTRANET_INVITE_DISABLED_BOX_TEXT'),
			buttonText: Loc.getMessage('INTRANET_INVITE_DISABLED_BOX_BUTTON_TEXT'),
		});

		if (onInviteError)
		{
			onInviteError([new Error(ErrorCode.PERMISSIONS_RESTRICTED)]);
		}
	};

	const handleErrors = (errors, onInviteError) => {
		Alert.alert('', Invite.getAjaxErrorText(errors));

		if (onInviteError)
		{
			onInviteError(errors);
		}
	};

	const getInviteSettings = () => {
		return BX.ajax.runAction('intranetmobile.invite.getInviteSettings');
	};

	const extractResponseData = (data) => {
		return {
			adminConfirm: data.adminConfirm ?? false,
			inviteLink: data.inviteLink ?? '',
			creatorEmailConfirmed: data.creatorEmailConfirmed ?? false,
			sharingMessage: data.sharingMessage ?? '',
			canInviteByPhone: data.canInviteByPhone ?? false,
			canInviteByLink: data.canInviteByLink ?? false,
		};
	};

	/**
	 * Opens the invite widget with the specified configuration.
	 *
	 * @param {Object} params - The parameters for configuring the invite widget.
	 * @param {Object} [params.parentLayout=null] - The parent layout for the widget.
	 * @param {Object} [params.openWidgetConfig={}] - Configuration options for opening the widget.
	 * @param {Object} [params.analytics={}] - Analytics configuration.
	 * @param {Function} [params.onInviteSentHandler=null] - Callback function to handle successful invite sending.
	 * @param {Function} [params.onInviteError=null] - Callback function to handle invite sending errors.
	 * @param {Function} [params.onViewHiddenWithoutInvitingHandler=null]
	 * - Callback function to handle the view being hidden without sending an invitation.
	 * @param {string} [params.inviteLink=''] - The link to be shared in the invite.
	 * @param {boolean} [params.creatorEmailConfirmed=false] - Admin confirmed email.
	 * @param {boolean} [params.canInviteByPhone=false] - Invite by phone is avalable.
	 * @param {number} [params.canInviteByLink=false] - Invite by link is avalable.
	 * @param {string} [params.sharingMessage=''] - The message to be shared with the invite.
	 * @param {boolean} [params.multipleInvite=true] - Whether multiple invites are allowed.
	 */
	const openInviteWidget = ({
		parentLayout = null,
		openWidgetConfig = {},
		analytics = {},
		onInviteSentHandler = null,
		onInviteError = null,
		onViewHiddenWithoutInvitingHandler = null,
		inviteLink = '',
		canInviteByPhone = false,
		canInviteByLink = false,
		creatorEmailConfirmed = false,
		sharingMessage = '',
		multipleInvite = true,
		adminConfirm = false,
	}) => {
		const inviteAnalytics = new IntranetInviteAnalytics({ analytics });
		inviteAnalytics.sendDrawerOpenEvent();
		inviteAnalytics.setDepartmentParam(false);
		const config = {
			enableNavigationBarBorder: false,
			titleParams: {
				text: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE'),
				type: 'dialog',
			},
			modal: true,
			backdrop: {
				showOnTop: false,
				onlyMediumPosition: false,
				mediumPositionHeight: 516,
				bounceEnable: true,
				swipeAllowed: true,
				swipeContentAllowed: false,
				horizontalSwipeAllowed: false,
				shouldResizeContent: true,
				adoptHeightByKeyboard: true,
			},
			...openWidgetConfig,
			onReady: (readyLayout) => {
				let onInviteSentHandlerExecuted = false;
				let onInviteErrorExecuted = false;
				readyLayout.showComponent(new Invite({
					layout: readyLayout,
					parentLayout,
					openWidgetConfig,
					analytics: inviteAnalytics,
					onInviteSentHandler: (users) => {
						onInviteSentHandlerExecuted = true;
						if (onInviteSentHandler)
						{
							onInviteSentHandler(users);
						}
					},
					onInviteError: (errors) => {
						onInviteErrorExecuted = true;
						if (onInviteError)
						{
							onInviteError(errors);
						}
					},
					inviteLink,
					canInviteByPhone,
					canInviteByLink,
					creatorEmailConfirmed,
					sharingMessage,
					multipleInvite,
					adminConfirm,
				}));

				readyLayout.on('onViewRemoved', () => {
					if (!onInviteSentHandlerExecuted && !onInviteErrorExecuted && onViewHiddenWithoutInvitingHandler)
					{
						onViewHiddenWithoutInvitingHandler();
					}
				});
			},
		};

		if (parentLayout)
		{
			parentLayout.openWidget('layout', config);

			return;
		}

		PageManager.openWidget('layout', config);
	};

	module.exports = { openIntranetInviteWidget, ErrorCode };
});
