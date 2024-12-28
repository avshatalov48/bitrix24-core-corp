/**
 * @module in-app-url/routes
 */
jn.define('in-app-url/routes', (require, exports, module) => {
	const { Feature } = require('feature');
	const { getHttpPath } = require('utils/url');
	const { ProfileView } = require('user/profile');
	const { WorkgroupUtil } = require('project/utils');

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = function(inAppUrl) {

		try
		{
			const diskmobileInAppUrlRoutes = require('disk/in-app-url/routes');
		}
		catch (err)
		{
			console.warn('Cannot get diskmobile routes, try to install diskmobile', err);

			inAppUrl.register('/bitrix/tools/disk/focus.php', (params, { queryParams, url }) => {
				const diskParams = {};
				const resolveParams = ['folderId', 'objectId'];
				resolveParams.forEach((param) => {
					const value = queryParams[param];
					if (value)
					{
						diskParams[param] = value;
					}
				});

				if (!Feature.isOpenImageNonContextSupported())
				{
					return Application.openUrl(getHttpPath(url));
				}

				return BX.postComponentEvent('onDiskFolderOpen', [diskParams], 'background');
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
					params = [
						{
							entityType: 'common',
							ownerId: `shared_files_${env.siteId}`,
						},
					];
				}

				BX.postComponentEvent('onDiskFolderOpen', params, 'background');
			}).name('disk:common');
		}

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
				.then((list) => ProfileView.open({ userId, backdrop }, list))
				.catch(console.error);
		}).name('open:user');

		inAppUrl.register('/company/personal/user/:userId/blog/:postId/$', ({ postId }) => {
			PageManager.openPage({
				url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${postId}`,
			});
		}).name('blog:post');

		inAppUrl.register(
			'/company/personal/user/:userId/blog/:postId/\\?commentId=:commentId#com:com',
			({ postId, commentId, com }) => {
				PageManager.openPage({
					url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${postId}&commentId=${commentId}#com${com}`,
				});
			},
		).name('blog:post:comment');

		inAppUrl.register('/company/personal/log/:logId/$', ({ logId }) => {
			PageManager.openPage({
				url: `mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=${logId}`,
			});
		}).name('log:entry');

		inAppUrl.register('/workgroups/group/:groupId/$', ({ groupId }) => {
			void WorkgroupUtil.openProject(null, {
				projectId: groupId,
				siteId: env.siteId,
				siteDir: env.siteDir,
			});
		}).name('group:open');
	};
});
