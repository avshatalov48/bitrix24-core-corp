/**
 * @module user-profile
 */
jn.define('user-profile', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ajaxPublicErrorHandler } = require('error');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { TabType } = require('user-profile/const');
	const { fetchTabs, fetchNewProfileFeatureEnabled } = require('user-profile/api');
	const { openUserProfile, ProfileView } = require('user/profile');
	const { showErrorToast } = require('toast');

	/**
	 * @class UserProfile
	 */
	class UserProfile
	{
		static isOpening = false;

		/**
		 * @param {Object} params
		 * @param {number} [params.ownerId=env.userId]
		 * @param {string} [params.selectedTabId=TabType.COMMON]
		 * @param {boolean} [params.openInComponent=false]
		 * @param {PageManager} [params.parentWidget=PageManager]
		 */
		static async open({
			ownerId = env.userId,
			selectedTabId = TabType.COMMON,
			openInComponent = false,
			parentWidget = PageManager,
			widgetParams = {},
		} = {})
		{
			if (UserProfile.isOpening)
			{
				return null;
			}
			UserProfile.isOpening = true;

			try
			{
				if (!ownerId)
				{
					return null;
				}

				void NotifyManager.showLoadingIndicator();
				const isEnabled = await fetchNewProfileFeatureEnabled();

				if (!isEnabled)
				{
					NotifyManager.hideLoadingIndicatorWithoutFallback();

					return UserProfile.openLegacyProfile(ownerId, openInComponent, parentWidget, widgetParams);
				}

				const response = await fetchTabs({ ownerId, selectedTabId });
				NotifyManager.hideLoadingIndicatorWithoutFallback();

				if (response.status === 'error')
				{
					void ajaxPublicErrorHandler(response);

					return null;
				}

				const { tabs, selectedTabId: selectedTabIdFromResponse, canView } = response.data;
				if (!canView)
				{
					showErrorToast({
						message: Loc.getMessage('M_PROFILE_NO_PERMISSIONS_TOAST_MESSAGE'),
					});

					return null;
				}
				const {
					prepareTabs,
					bindEvents,
					initTabNestedWidgets,
				} = await requireLazy('user-profile/tabs-preparer');

				const preparedTabs = prepareTabs(tabs, selectedTabIdFromResponse);
				const closeIcon = AppTheme.id === 'dark' ? modalIcons.darkArrowDown : modalIcons.lightArrowDown;
				const params = {
					...Object.fromEntries(preparedTabs.map((tab) => [tab.id, tab.params])),
					closeIcon,
				};

				if (openInComponent)
				{
					PageManager.openComponent('JSStackComponent', {
						// eslint-disable-next-line no-undef
						scriptPath: availableComponents['user-profile-tabs'].publicUrl,
						componentCode: 'user-profile-tabs',
						canOpenInDefault: true,
						rootWidget: {
							name: 'tabs',
							settings: {
								objectName: 'tabs',
								title: Loc.getMessage('M_PROFILE_TITLE'),
								grabTitle: false,
								modal: true,
								leftButtons: [
									{
										svg: {
											content: closeIcon,
										},
										isCloseButton: true,
									},
								],
								tabs: {
									items: preparedTabs,
								},
							},
						},
						params: {
							ownerId,
							params,
						},
					}, parentWidget);

					return null;
				}

				return new Promise((resolve) => {
					parentWidget
						.openWidget('tabs', {
							titleParams: {
								text: Loc.getMessage('M_PROFILE_TITLE'),
							},
							grabTitle: false,
							modal: true,
							leftButtons: [
								{
									svg: {
										content: closeIcon,
									},
									isCloseButton: true,
								},
							],
							tabs: {
								items: preparedTabs,
							},
						})
						.then((widget) => {
							bindEvents(widget, ownerId);
							initTabNestedWidgets(widget, params);
							resolve(widget);
						})
						.catch(console.error)
					;
				});
			}
			finally
			{
				UserProfile.isOpening = false;
			}
		}

		static async openLegacyProfile(userId, openInComponent, parentWidget, widgetParams)
		{
			if (openInComponent)
			{
				ProfileView.openComponent({
					userId,
				});

				return null;
			}

			return openUserProfile({
				parentWidget,
				userId,
				...widgetParams,
			});
		}
	}

	const modalIcons = {
		darkArrowDown: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5052 11.1729C20.7786 10.8995 21.2212 10.8995 21.4945 11.1729C21.7679 11.4462 21.7678 11.8887 21.4945 12.1621L14.4945 19.1621C14.2211 19.4355 13.7786 19.4355 13.5052 19.1621L6.50525 12.1621C6.23193 11.8887 6.2319 11.4462 6.50525 11.1729C6.77861 10.8995 7.22115 10.8995 7.49451 11.1729L13.9994 17.6777L20.5052 11.1729Z" fill="#828385"/></svg>',
		lightArrowDown: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.4949 11.1718C7.22153 10.8985 6.77832 10.8985 6.50495 11.1718C6.23158 11.4452 6.23158 11.8884 6.50495 12.1618L13.505 19.1618C13.7783 19.4351 14.2215 19.4351 14.4949 19.1618L21.4949 12.1618C21.7683 11.8884 21.7683 11.4452 21.4949 11.1718C21.2215 10.8985 20.7783 10.8985 20.505 11.1718L13.9999 17.6768L7.4949 11.1718Z" fill="#A7A7A7"/></svg>',
	};

	module.exports = {
		UserProfile,
		fetchNewProfileFeatureEnabled,
	};
});
