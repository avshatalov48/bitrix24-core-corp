/**
 * @module intranet/background
 */
jn.define('intranet/background', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	class IntranetBackground
	{
		static init()
		{
			const intranetBackground = new IntranetBackground();

			intranetBackground.initMiniProfile();
		}

		initMiniProfile()
		{
			const isNeedToShowMiniProfile = Application.storage.get(`intranet.miniProfile.needToShow_${env.userId}`, null);

			if (isNeedToShowMiniProfile)
			{
				this.showMiniProfile();
				Application.storage.set(`intranet.miniProfile.needToShow_${env.userId}`, false);
			}
			else if (isNeedToShowMiniProfile === null || isNeedToShowMiniProfile === undefined)
			{
				const request = new RunActionExecutor('intranetmobile.userprofile.isNeedToShowMiniProfile', {});
				request.call(false)
					.then((response) => {
						Application.storage.set(`intranet.miniProfile.needToShow_${env.userId}`, false);

						if (response.data)
						{
							this.showMiniProfile();
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

		showMiniProfile()
		{
			PageManager.openComponent('JSStackComponent', {
				scriptPath: '/mobileapp/jn/intranet:user-mini-profile/?version=',
				componentCode: 'intranet.user-mini-profile',
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
			});
		}
	}

	module.exports = {
		IntranetBackground,
	};
});
