/**
 * @module disk/in-app-url/routes
 */
jn.define('disk/in-app-url/routes', (require, exports, module) => {
	const { showToast } = require('toast');
	const { URL } = require('utils/url');
	const { Loc } = require('loc');
	const { openFolder } = require('disk/opener/folder');
	const { fetchObjectWithRights } = require('disk/rights');
	const { filesUpsertedFromServer } = require('disk/statemanager/redux/slices/files');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const {
		openNativeViewer,
		getNativeViewerMediaType,
		getExtension,
		getMimeType,
	} = require('utils/file');

	const EntityType = {
		COMMON: 'common',
		USER: 'user',
		GROUP: 'group',
	};

	function openFile(file)
	{
		openNativeViewer({
			fileType: getNativeViewerMediaType(getMimeType(getExtension(file.name), file.name)),
			url: file.links.download,
			name: file.name,
		});
	}

	async function openObject(diskObjectId)
	{
		const diskObject = await fetchObjectWithRights(diskObjectId);

		if (!diskObject)
		{
			showToast({
				message: Loc.getMessage('M_DISK_IN_APP_URL_FILE_NOT_FOUND'),
			});
		}

		if (diskObject.isFolder)
		{
			void openFolder(diskObject.id);

			return;
		}

		openFile(diskObject);
	}

	async function fetchTargetFolder(path, entityType, entityId)
	{
		try
		{
			const response = await BX.ajax.runAction('diskmobile.Common.getFolderByPath', {
				data: {
					path,
					entityType,
					entityId,
				},
			});

			if (response.errors.length > 0)
			{
				console.error(response.errors);

				return null;
			}

			const diskObject = response.data.diskObject;
			dispatch(filesUpsertedFromServer([diskObject]));

			return selectById(store.getState(), diskObject.id);
		}
		catch (e)
		{
			showToast({
				message: Loc.getMessage('M_DISK_IN_APP_URL_FOLDER_NOT_FOUND'),
			});
			console.error(e);
		}

		return null;
	}

	function getDecodedEntityPath(url)
	{
		return decodeURI(URL(url).pathname).split('/path')[1];
	}

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/bitrix/tools/disk/focus.php', (params, { queryParams, url }) => {
			const id = queryParams?.objectId || queryParams?.folderId;
			if (id)
			{
				void openObject(id);
			}
		}).name('disk:entity');

		inAppUrl.register(`/company/personal/user/${env.userId}/disk/path/`, (params, { url }) => {
			const path = getDecodedEntityPath(url);
			void fetchTargetFolder(path, EntityType.USER, env.userId).then((targetFolder) => {
				void openObject(targetFolder.id);
			});
		}).name('disk:personal');

		inAppUrl.register('/workgroups/group/:groupId/disk/path/', ({ groupId }, { url }) => {
			const path = getDecodedEntityPath(url);
			void fetchTargetFolder(path, EntityType.GROUP, groupId).then((targetFolder) => {
				void openObject(targetFolder.id);
			});
		}).name('disk:group');

		inAppUrl.register('/docs/path/', (params, { url }) => {
			const path = getDecodedEntityPath(url);
			void fetchTargetFolder(path, EntityType.COMMON, 'shared_files_s1').then((targetFolder) => {
				void openObject(targetFolder.id);
			});
		}).name('disk:common');
	};
});
