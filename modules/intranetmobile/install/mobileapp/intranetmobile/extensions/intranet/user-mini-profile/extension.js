/**
 * @module intranet/user-mini-profile
 */
jn.define('intranet/user-mini-profile', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	class UserMiniProfile
	{
		static init()
		{
			const isNeedToShowMiniProfile = Application.storage.get(`intranet.miniProfile.needToShow_${env.userId}`, null);

			if (isNeedToShowMiniProfile === null || isNeedToShowMiniProfile === undefined)
			{
				const request = new RunActionExecutor('intranetmobile.userprofile.isNeedToShowMiniProfile', {});
				request.call(false)
					.then((response) => {
						Application.storage.set(`intranet.miniProfile.needToShow_${env.userId}`, false);

						if (response.data)
						{
							UserMiniProfile.showMiniProfile();
						}
						else
						{
							BX.postComponentEvent('userMiniProfileClosed', null);
						}
					})
					.catch((error) => {
						console.error(error);
					});
			}
			else
			{
				BX.postComponentEvent('userMiniProfileClosed', null);
			}
		}

		static showMiniProfile = async () => {
			try
			{
				const profileDataResponse = await this.getProfileData();
				const portalLogoResponse = await this.getPortalLogoData();

				PageManager.openComponent('JSStackComponent', {
					name: 'JSStackComponent',
					// eslint-disable-next-line no-undef
					scriptPath: availableComponents['intranet:user-mini-profile'].publicUrl,
					componentCode: 'intranet:user-mini-profile',
					canOpenInDefault: true,
					rootWidget: {
						name: 'layout',
						settings: {
							objectName: 'layout',
							modal: true,
							backdrop: {
								showOnTop: true,
								swipeAllowed: false,
								hideNavigationBar: true,
							},
						},
					},
					params: {
						portalLogoParams: portalLogoResponse.answer.result,
						profileDataParams: profileDataResponse.answer.result,
					},
				});
			}
			catch (e)
			{
				console.error(e);
			}
		};

		static getProfileData = async () => {
			return BX.rest.callMethod('user.current');
		};

		static getPortalLogoData = async () => {
			return BX.rest.callMethod('intranet.portal.getLogo');
		};
	}

	module.exports = {
		UserMiniProfile,
	};
});
