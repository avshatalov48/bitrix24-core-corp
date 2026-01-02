/**
 * @module tasks/disk
 */
jn.define('tasks/disk', (require, exports, module) => {
	const { RequestExecutor } = require('rest');
	const cacheId = 'tasks/disk_mobile.disk.getUploadedFilesFolder';

	const getDiskFolderId = (groupId = null) => new Promise((resolve, reject) => {
		const getGroupIdCacheNamePart = (id) => (id ? `/groupId:${id}` : '');
		const getCacheId = (id) => `${cacheId}${getGroupIdCacheNamePart(id)}`;

		const cache = Application.storage.getObject(getCacheId(groupId), null);

		if (cache === null)
		{
			(new RequestExecutor('mobile.disk.getUploadedFilesFolder', {
				groupId,
			}))
				.call()
				.then((response) => {
					const diskFolderId = Number(response.result);
					Application.storage.setObject(getCacheId(groupId), { diskFolderId });
					resolve({ diskFolderId, cached: false });
				})
				.catch((e) => {
					console.error(e);
					reject();
				});
		}
		else
		{
			resolve({
				diskFolderId: cache.diskFolderId,
				cached: true,
			});
		}
	});

	module.exports = { getDiskFolderId };
});
