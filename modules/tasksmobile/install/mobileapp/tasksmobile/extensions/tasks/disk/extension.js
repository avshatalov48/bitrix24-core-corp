/**
 * @module tasks/disk
 */
jn.define('tasks/disk', (require, exports, module) => {
	const { RequestExecutor } = require('rest');
	const cacheId = 'tasks/disk_mobile.disk.getUploadedFilesFolder';

	const getDiskFolderId = () => new Promise((resolve, reject) => {
		const cache = Application.storage.getObject(cacheId, null);

		if (cache === null)
		{
			(new RequestExecutor('mobile.disk.getUploadedFilesFolder'))
				.call()
				.then((response) => {
					const diskFolderId = Number(response.result);
					Application.storage.setObject(cacheId, { diskFolderId });
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
