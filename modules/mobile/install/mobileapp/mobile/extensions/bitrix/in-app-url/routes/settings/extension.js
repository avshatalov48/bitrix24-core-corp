/**
 * @module in-app-url/routes/settings
 */
jn.define('in-app-url/routes/settings', (require, exports, module) => {
	const { Feature } = require('feature');
	const { checkFeatureFlag, FeatureFlagType } = require('feature-flag');

	module.exports = function(inAppUrl) {
		inAppUrl.register('/settings/tab.presets', (params, { context }) => {
			PageManager.openComponent(
				'JSStackComponent',
				{
					// eslint-disable-next-line no-undef
					scriptPath: availableComponents['tab.presets'].publicUrl,
					rootWidget: {
						name: 'layout',
						settings: {
							objectName: 'layout',
							titleParams: {
								text: context?.title,
								useLargeTitleMode: true,
							},
						},
					},
				},
			);
		}).name('settings-tab-presets');

		inAppUrl.register('/settings/general', (params, { context }) => {
			const openOldSettings = () => {
				PageManager.openComponent(
					'JSStackComponent',
					{
						componentCode: 'settings.config',
						// eslint-disable-next-line no-undef
						scriptPath: availableComponents['settings'].publicUrl,
						type: 'component',
						rootWidget: {
							name: 'settings',
							settings: {
								objectName: 'settings',
								titleParams: {
									text: context?.title,
									type: 'section',
								},
							},
						},
						params: {
							USER_ID: env.userId,
							SITE_ID: env.siteId,
							LANGUAGE_ID: env.languageId,
							IS_ADMIN: env.isAdmin,
						},
					},
				);
			};

			if (!Feature.isNativeSettingApiSupported())
			{
				openOldSettings();

				return;
			}

			checkFeatureFlag(FeatureFlagType.SETTINGS_V2).then((result) => {
				if (result === true)
				{
					requireLazy('settings-v2').then(
						({ openSettings, SettingsPageId }) => {
							openSettings({
								settingsPageId: SettingsPageId.ROOT,
							});
						},
					);
				}
				else
				{
					openOldSettings();
				}
			}).catch((e) => {
				console.error(e);
				openOldSettings();
			});
		}).name('settings-general');

		inAppUrl.register('/settings/go-to-web', (params, { context }) => {
			requireLazy('qrauth/utils')
				.then(({ qrauth }) => {
					qrauth.open({
						showHint: true,
						title: context?.title,
						hintText: context?.hintText,
						analyticsSection: context?.analyticsSection,
					});
				})
				.catch((error) => {
					console.error('Error loading qrauth', error);
				});
		}).name('settings-go-to-web');

		inAppUrl.register('/settings/notification', () => {

			const openOldNotificationSettings = () => {
				requireLazy('settings/notify-provider')
					.then(({ SettingsNotifyManager, SettingsNotifyProvider }) => {
						if (SettingsNotifyManager)
						{
							const provider = new SettingsNotifyProvider();

							SettingsNotifyManager.setSettingsProvider(provider);
							SettingsNotifyManager.loadCache()
								.then(() => {
									if (!SettingsNotifyManager.requestConfigDataLoaded)
									{
										SettingsNotifyManager.requestConfigData();
									}

									const form = SettingsNotifyManager.prepareForm('notify');

									provider.openForm(form.compile(), form.getId());
									SettingsNotifyManager.onSettingsProviderStateChanged('onViewShown', form.getId());
								})
								.catch((error) => {
									console.error(error);
								});
						}
					})
					.catch((error) => {
						console.error('Error loading SettingsNotifyManager', error);
					});
			};

			checkFeatureFlag(FeatureFlagType.SETTINGS_V2).then((result) => {
				if (result === true)
				{
					requireLazy('settings-v2').then(
						({ openSettings, SettingsPageId }) => {
							openSettings({
								settingsPageId: SettingsPageId.NOTIFICATIONS_ROOT,
							});
						},
					);
				}
				else
				{
					openOldNotificationSettings();
				}
			}).catch((e) => {
				console.error(e);
				openOldNotificationSettings();
			});
		}).name('settings-notification');
	};
});
