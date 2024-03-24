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

		inAppUrl.register('/workgroups/group/:ownerId/disk/path/', ({ ownerId }) => {
			BX.postComponentEvent('onDiskFolderOpen', [{ entityType: 'group', ownerId }], 'background');
		}).name('disk:group');

		inAppUrl.register('/docs/:folder/', ({ folder }) => {
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
				.then((list) => ProfileView.open({ userId, backdrop }, list));
		}).name('open:user');

		inAppUrl.register('/company/personal/user/:userId/blog/:postId/$', ({ postId }) => {
			PageManager.openPage({
				url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${postId}`,
			});
		}).name('blog:post');

		inAppUrl.register('/company/personal/user/:userId/blog/:postId/\\?commentId=:commentId#com:com', ({ postId, commentId, com }) => {
			PageManager.openPage({
				url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${postId}&commentId=${commentId}#com${com}`,
			});
		}).name('blog:post:comment');

		inAppUrl.register('/company/personal/log/:logId/$', ({ logId }) => {
			PageManager.openPage({
				url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=${logId}`,
			});
		}).name('log:entry');

		inAppUrl.register('/workgroups/group/:groupId/$', ({ groupId }) => {
			const data = {
				projectId: groupId,
				action: 'view',
				siteId: env.siteId,
				siteDir: env.siteDir,
			};

			BX.postComponentEvent('projectbackground::project::action', [data], 'background');
		}).name('group:open');
	};
});
