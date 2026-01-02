/**
 * @module intranet/invite-opener-new/api
 */
jn.define('intranet/invite-opener-new/api', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Tourist } = require('tourist');
	const { Type } = require('type');

	/**
	 * @param {Object} [params={}]
	 * @param {Function} [params.onInviteError=null]
	 */
	function getInviteSettings(params = {})
	{
		return BX.ajax.runAction('intranetmobile.invite.getInviteSettings')
			.then(
				(response) => processGetInviteSettingsFulfilled(response, params),
				(response) => processGetInviteSettingsRejected(response, params),
			)
			.catch(console.error)
		;
	}

	function processGetInviteSettingsRejected(response, params)
	{
		if (Type.isArrayFilled(response.errors))
		{
			handleErrors(response.errors, params.onInviteError);
		}

		return null;
	}

	function processGetInviteSettingsFulfilled(response, params)
	{
		const { errors, data } = response;
		if (errors && errors.length > 0)
		{
			handleErrors(errors, params.onInviteError);

			return null;
		}

		return extractResponseData(data);
	}

	function handleErrors(errors, onInviteError = null)
	{
		Alert.alert('', getAjaxErrorText(errors));
		onInviteError?.(errors);
	}

	function getAjaxErrorText(errors)
	{
		return (
			errors
				.map((errorMessage) => {
					if (errorMessage.message)
					{
						return errorMessage.message.replace('<br/>:', '\n').replace('<br/>', '\n');
					}

					return errorMessage.replace('<br/>:', '\n').replace('<br/>', '\n');
				})
				.filter((errorMessage) => errorMessage.length > 0)
				.join('\n')
		);
	}

	function extractResponseData(data)
	{
		const {
			canCurrentUserInvite = false,
			adminConfirm = false,
			creatorEmailConfirmed = false,
			canInviteBySMS = false,
			canInviteByLink = false,
			canInviteByEmail = false,
			isBitrix24Included = false,
			isInviteWithLocalEmailAppEnabled = true,
		} = data;

		return {
			canCurrentUserInvite,
			adminConfirm,
			creatorEmailConfirmed,
			canInviteBySMS,
			canInviteByLink,
			canInviteByEmail,
			isBitrix24Included,
			isInviteWithLocalEmailAppEnabled,
		};
	}

	async function setUserVisitedInvitations()
	{
		await Tourist.ready();

		if (Tourist.firstTime('visit_invitations'))
		{
			await Tourist.remember('visit_invitations');
			BX.postComponentEvent('onSetUserCounters', [{ [String(env.siteId)]: { menu_invite: 0 } }]);
		}
	}

	module.exports = {
		getInviteSettings,
		setUserVisitedInvitations,
	};
});
