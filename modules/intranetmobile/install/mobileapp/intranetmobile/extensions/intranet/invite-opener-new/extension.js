/**
 * @module intranet/invite-opener-new
 */
jn.define('intranet/invite-opener-new', (require, exports, module) => {
	const { Notify } = require('notify');
	const { Invite, IntranetInviteAnalytics } = require('intranet/invite-new');
	const { Loc } = require('loc');
	const { StatusBox } = require('layout/ui/status-box');
	const { makeLibraryImagePath } = require('asset-manager');
	const { getInviteSettings, setUserVisitedInvitations } = require('intranet/invite-opener-new/api');

	const ErrorCode = {
		POSSIBILITIES_RESTRICTED: 'Invite possibilities restricted',
		PERMISSIONS_RESTRICTED: 'Invite permissions restricted',
	};

	/**
	 * @param {Object} params
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
		getInviteSettings(params)
			.then((inviteSettings) => {
				if (!inviteSettings)
				{
					return;
				}

				void setUserVisitedInvitations();

				if (!inviteSettings.canCurrentUserInvite)
				{
					handleUserHasNoPermissionsToInvite(params.onInviteError, params.parentLayout);

					return;
				}

				openInviteWidget({
					...inviteSettings,
					...params,
				});
			})
			.catch(console.error)
			.finally(() => Notify.hideCurrentIndicator())
		;
	};

	const handleUserHasNoPermissionsToInvite = (onInviteError, parentLayout = PageManager) => {
		StatusBox.open({
			backdropTitle: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE_MSGVER_1'),
			testId: 'status-box-no-invitation',
			imageUri: makeLibraryImagePath('no-invitation.svg', 'invite-status-box', 'intranet'),
			parentWidget: parentLayout,
			description: Loc.getMessage('INTRANET_INVITE_DISABLED_BOX_TEXT'),
			buttonText: Loc.getMessage('INTRANET_INVITE_DISABLED_BOX_BUTTON_TEXT'),
		});

		if (onInviteError)
		{
			onInviteError([new Error(ErrorCode.PERMISSIONS_RESTRICTED)]);
		}
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
	 * @param {boolean} [params.creatorEmailConfirmed=false] - Admin confirmed email.
	 * @param {boolean} [params.canInviteBySMS=false] - Invite by SMS is avalable.
	 * @param {number} [params.canInviteByLink=false] - Invite by link is avalable.
	 * @param {number} [params.canInviteByEmail=false] - Invite by email is avalable.
	 * @param {boolean} [params.multipleInvite=true] - Whether multiple invites are allowed.
	 * @param {boolean} [params.adminConfirm=false] - Whether admin confirmation is required.
	 * @param {boolean} [params.isBitrix24Included=false] - Whether Bitrix24 is included.
	 * @param {boolean} [params.isInviteWithLocalEmailAppEnabled=true] - Whether invite with local email app is enabled.
	 */
	const openInviteWidget = ({
		parentLayout = null,
		openWidgetConfig = {},
		analytics = {},
		onInviteSentHandler = null,
		onInviteError = null,
		onViewHiddenWithoutInvitingHandler = null,
		canInviteBySMS = false,
		canInviteByLink = false,
		canInviteByEmail = false,
		creatorEmailConfirmed = false,
		multipleInvite = true,
		adminConfirm = false,
		isBitrix24Included = false,
		isInviteWithLocalEmailAppEnabled = true,
	}) => {
		const inviteAnalytics = new IntranetInviteAnalytics({ analytics });
		inviteAnalytics.sendDrawerOpenEvent();
		inviteAnalytics.setDepartmentParam(false);
		const config = {
			enableNavigationBarBorder: false,
			titleParams: {
				text: Loc.getMessage('INTRANET_INVITE_OPENER_TITLE_MSGVER_1'),
				type: 'dialog',
			},
			modal: true,
			backdrop: {
				showOnTop: false,
				onlyMediumPosition: false,
				mediumPositionHeight: 530,
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
					canInviteBySMS,
					canInviteByLink,
					canInviteByEmail,
					creatorEmailConfirmed,
					multipleInvite,
					adminConfirm,
					isBitrix24Included,
					isInviteWithLocalEmailAppEnabled,
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

	module.exports = {
		openIntranetInviteWidget,
		ErrorCode,
	};
});
