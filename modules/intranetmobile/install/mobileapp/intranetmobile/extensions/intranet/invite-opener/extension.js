/**
 * @module intranet/invite-opener
 */
jn.define('intranet/invite-opener', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { Notify } = require('notify');
	const { IntranetInvite, IntranetInviteAnalytics } = require('intranet/invite');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const NO_PERMISSIONS_CODE = 'INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS';

	/**
	 * @param params
	 * @param {AnalyticsEvent} params.analytics
	 * @param {Boolean} params.multipleInvite
	 * @param {LayoutComponent} params.parentLayout
	 * @param {Function} params.onInviteSentHandler
	 * @param {Function} params.onInviteError
	 * @param {Function} params.onViewHiddenWithoutInvitingHandler
	 */
	const openIntranetInviteWidget = (params) => {
		Notify.showIndicatorLoading();
		getRegisterData().then(
			(response) => processRegisterDataFulfilled(response, params),
			(response) => processRegisterDataRejected(response, params),
		)
			.catch((errors) => console.error(errors))
			.finally(() => Notify.hideCurrentIndicator());
	};

	const processRegisterDataRejected = (response, params) => {
		const responseHasErrors = response.errors && response.errors.length > 0;
		if (responseHasErrors)
		{
			handleErrors(response.errors, params.onInviteError);
		}
	};

	const processRegisterDataFulfilled = (response, params) => {
		const responseHasErrors = response.errors && response.errors.length > 0;
		if (responseHasErrors)
		{
			handleErrors(response.errors, params.onInviteError);

			return;
		}

		const { creatorEmailConfirmed } = response.data;
		if (!isNil(creatorEmailConfirmed) && !creatorEmailConfirmed)
		{
			handleCreatorEmailNotConfirmed(params.onInviteError);

			return;
		}

		openInviteWidget(response, params);
	};

	const openInviteWidget = (response, params) => {
		const settings = extractSettings(response.data, params);
		const inviteAnalytics = new IntranetInviteAnalytics({
			analytics: params.analytics,
		});
		inviteAnalytics.sendDrawerOpenEvent();

		const openWidgetArguments = [
			'invite',
			{
				...settings,
				onReady: (inviteWidget) => {
					new IntranetInvite({
						analytics: inviteAnalytics,
						disableAdminConfirm: settings.disableAdminConfirm,
						adminConfirm: settings.adminConfirm,
						registerUrl: settings.link,
						rootStructureSectionId: settings.rootStructureSectionId,
						onInviteSentHandler: params.onInviteSentHandler,
						onViewHiddenWithoutInvitingHandler: params.onViewHiddenWithoutInvitingHandler,
						inviteWidget,
					});
				},
			},
		];
		if (params.parentLayout)
		{
			params.parentLayout.openWidget(...openWidgetArguments);

			return;
		}

		PageManager.openWidget(...openWidgetArguments);
	};

	const extractSettings = (data, params) => {
		return {
			objectName: 'inviteComponent',
			link: data.registerUrl ?? '',
			adminConfirm: data.adminConfirm ?? false,
			disableAdminConfirm: data.disableAdminConfirm ?? false,
			rootStructureSectionId: data.rootStructureSectionId ?? 0,
			sharingMessage: data.sharingMessage ?? '',
			multipleInvite: params.multipleInvite ?? true,
		};
	};

	const handleCreatorEmailNotConfirmed = (onInviteError) => {
		Alert.alert(
			Loc.getMessage('INTRANET_INVITE_CONFIRM_CREATOR_EMAIL_ERROR_TITLE'),
			Loc.getMessage('INTRANET_INVITE_CONFIRM_CREATOR_EMAIL_ERROR_TEXT'),
		);
		if (onInviteError)
		{
			onInviteError([new Error('Portal creator email not confirmed')]);
		}
	};

	const handleErrors = (errors, onInviteError) => {
		if (errors[0].code === NO_PERMISSIONS_CODE)
		{
			Alert.alert(
				Loc.getMessage('INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS_TITLE'),
				Loc.getMessage('INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS_TEXT'),
			);
		}
		else
		{
			Alert.alert('', IntranetInvite.getAjaxErrorText(errors));
		}

		if (onInviteError)
		{
			onInviteError(errors);
		}
	};

	const getRegisterData = () => {
		return BX.ajax.runAction('intranet.invite.getData');
	};

	module.exports = { openIntranetInviteWidget };
});
