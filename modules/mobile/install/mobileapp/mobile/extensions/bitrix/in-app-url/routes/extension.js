/**
 * @module in-app-url/routes
 */
jn.define('in-app-url/routes', (require, exports, module) => {

	const { ProfileView } = require('user/profile');

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = function(inAppUrl) {
		inAppUrl.register('/bitrix/tools/disk/focus.php', (params, { queryParams }) => {
			const folderId = queryParams.folderId || queryParams.objectId;
			const diskParams = Number(folderId) > 0 ? { folderId } : {};

			BX.postComponentEvent('onDiskFolderOpen', [diskParams], 'background');
		}).name('disk:entity');

		inAppUrl.register(`/company/personal/user/${env.userId}/disk/path/`, () => {
			BX.postComponentEvent('onDiskFolderOpen', [], 'background');
		}).name('disk:personal');

		inAppUrl.register(`/workgroups/group/:ownerId/disk/path/`, ({ ownerId }) => {
			BX.postComponentEvent('onDiskFolderOpen', [{ entityType: 'group', ownerId }], 'background');
		}).name('disk:group');

		inAppUrl.register(`/docs/:folder/`, ({ folder }) => {
			const folders = ['shared', 'path'];
			let params = [];

			if (folders.includes(folder))
			{
				params = [{
					entityType: 'common',
					ownerId: `shared_files_${env.siteId}`,
				}];
			}

			BX.postComponentEvent('onDiskFolderOpen', params, 'background');
		}).name('disk:common');

		inAppUrl.register('/company/personal/user/:userId/(\\?\\w+)?$', ({ userId }, { context = {} }) => {
			const widgetParams = { groupStyle: true };
			const { backdrop = true } = context;

			if (backdrop)
			{
				widgetParams.backdrop = {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				};
			}

			PageManager.openWidget('list', widgetParams)
				.then(list => ProfileView.open({ userId, backdrop }, list));

		}).name('open:user');

	};

});
