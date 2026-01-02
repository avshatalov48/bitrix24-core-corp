/**
 * @module in-app-url/routes/bitrix24
 */
jn.define('in-app-url/routes/bitrix24', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WhatsNewUIManager } = require('whats-new/ui-manager');

	module.exports = function(inAppUrl) {
		inAppUrl.register('/whats-new/', (params, { context }) => {
			WhatsNewUIManager.openComponent();
		}).name('/bitrix24/whats-new');

		inAppUrl.register('/bitrix24/profile', (params, { context }) => {
			const {
				canEditProfile = false,
			} = context;

			void requireLazy('user-profile')
				.then(async ({ UserProfile, fetchNewProfileFeatureEnabled }) => {
					const isNewProfileFeatureEnabled = await fetchNewProfileFeatureEnabled();
					if (isNewProfileFeatureEnabled)
					{
						void UserProfile.open({ openInComponent: true });

						return;
					}

					PageManager.openComponent('JSStackComponent', {
						componentCode: 'user.profile',
						// eslint-disable-next-line no-undef
						scriptPath: availableComponents['user.profile'].publicUrl,
						params: {
							userId: env.userId,
							mode: canEditProfile ? 'edit' : 'view',
							items: [],
						},
						rootWidget: {
							name: canEditProfile ? 'form' : 'list',
							settings: {
								objectName: 'form',
							},
						},
					});
				})
				.catch(console.error);
		}).name('/bitrix24/profile');

		inAppUrl.register('/support/', (params, { context }) => {
			const { botId } = context;

			if (botId && Number(botId) > 0)
			{
				Promise.all([
					requireLazy('im:messenger/api/dialog-opener'),
					requireLazy('tourist', false),
				])
					.then(([{ DialogOpener }, { Tourist }]) => {
						return DialogOpener
							.open({ dialogId: Number(botId) })
							.then(() => Tourist.remember('show_support', {}));
					})
					.catch(async (error) => {
						console.error('Error in support opener:', error);

						const { showErrorToast } = await requireLazy('toast', false);
						showErrorToast(
							{
								message: Loc.getMessage('MENU_BITRIX24_SUPPORT24_ERROR_TEXT'),
							},
							this.layout,
						);
					});
			}
			else
			{
				console.error('Empty or invalid botId', botId);
			}
		});

		inAppUrl.register('/change-portal/', () => {
			Application.exit();
		});
	};
});
