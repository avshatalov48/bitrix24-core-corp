/**
 * @module intranet/user-mini-profile
 */
jn.define('intranet/user-mini-profile', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { BackgroundUIManager } = require('background/ui-manager');

	const MINI_PROFILE_COMPONENT_NAME = 'intranet:user-mini-profile';

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

						if (response.data && response.data === true)
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

				BackgroundUIManager.openComponent(
					MINI_PROFILE_COMPONENT_NAME,
					UserMiniProfile.openComponent.bind(null, portalLogoResponse, profileDataResponse),
					1000,
				);
			}
			catch (e)
			{
				console.error(e);
			}
		};

		static openComponent(portalLogoResponse, profileDataResponse)
		{
			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents[MINI_PROFILE_COMPONENT_NAME].publicUrl,
				componentCode: MINI_PROFILE_COMPONENT_NAME,
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

		static getProfileData = async () => {
			return BX.rest.callMethod('user.current');
		};

		static getPortalLogoData = async () => {
			return BX.rest.callMethod('intranet.portal.getLogo');
		};
	}

	module.exports = {
		UserMiniProfile,
		MINI_PROFILE_COMPONENT_NAME,
	};
});
