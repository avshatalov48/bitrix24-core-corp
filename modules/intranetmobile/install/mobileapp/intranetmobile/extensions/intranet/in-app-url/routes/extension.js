/**
 * @module intranet/in-app-url/routes
 */
jn.define('intranet/in-app-url/routes', (require, exports, module) => {
	module.exports = (inAppUrl) => {
		inAppUrl.register('/intranetmobile/users', (params, { context }) => {
			const {
				canInvite = false,
				canUseTelephony = false,
				title = '',
				openInviteOnMount = false,
			} = context;

			PageManager.openComponent('JSStackComponent', {
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['intranet:user.list'].publicUrl,
				componentCode: 'intranet.user.list',
				params: {
					canInvite,
					canUseTelephony,
					openInviteOnMount,
				},
				rootWidget: {
					name: 'layout',
					componentCode: 'users',
					settings: {
						objectName: 'layout',
						titleParams: {
							text: title,
							type: 'section',
						},
					},
				},
			});
		}).name('intranetmobile-users-list');
	};
});
