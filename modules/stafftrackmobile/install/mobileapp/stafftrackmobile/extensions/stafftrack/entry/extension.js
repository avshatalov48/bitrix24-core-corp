/**
 * @module stafftrack/entry
 */
jn.define('stafftrack/entry', (require, exports, module) => {
	const { UserLinkStatisticsManager } = require('stafftrack/data-managers/user-link-statistics-manager');
	const { Notify } = require('notify');

	class Entry
	{
		static entryVersion()
		{
			return 1;
		}

		static openCheckIn({ dialogId, dialogName, openSettings = false })
		{
			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'stafftrack.check-in',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['stafftrack:stafftrack.check-in'].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						modal: true,
						enableNavigationBarBorder: false,
						backdrop: {
							mediumPositionHeight: 525,
							hideNavigationBar: true,
							swipeAllowed: true,
							swipeContentAllowed: false,
							adoptHeightByKeyboard: true,
							horizontalSwipeAllowed: false,
						},
					},
				},
				params: {
					DIALOG_ID: dialogId,
					DIALOG_NAME: dialogName,
					OPEN_SETTINGS: openSettings,
				},
			});
		}

		static async openUserStatistics({ userId, hash, monthCode })
		{
			const user = await UserLinkStatisticsManager.get(userId, hash);

			if (!user)
			{
				Notify.showMessage('No permission');

				return;
			}

			PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'stafftrack.user-statistics',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['stafftrack:stafftrack.user-statistics'].publicUrl,
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						modal: true,
						backdrop: {
							mediumPositionPercent: 70,
							hideNavigationBar: true,
							swipeContentAllowed: false,
						},
					},
				},
				params: {
					USER: user,
					MONTH_CODE: monthCode,
				},
			});
		}

		static openTimemanPage()
		{
			PageManager.openPage({
				url: `${env.siteDir}mobile/timeman/`,
				useSearchBar: false,
				cache: false,
				titleParams: {
					type: 'section',
				},
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 80,
				},
			});
		}
	}

	if (typeof jnComponent?.preload === 'function')
	{
		const componentCode = 'stafftrack:stafftrack.check-in';

		// eslint-disable-next-line no-undef
		const { publicUrl } = availableComponents[componentCode] || {};

		if (publicUrl)
		{
			setTimeout(() => jnComponent.preload(publicUrl), 1000);
		}
	}

	module.exports = { Entry };
});
