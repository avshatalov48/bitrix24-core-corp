/* eslint-disable prefer-promise-reject-errors */
/**
 * @module disk/pull
 */
jn.define('disk/pull', (require, exports, module) => {
	const { Pull: StatefulListPull } = require('layout/ui/stateful-list/pull');

	const Command = {
		OBJECT_ADDED: 'objectAdded',
		OBJECT_RENAMED: 'objectRenamed',
		CONTENT_UPDATED: 'contentUpdated',
		OBJECT_MARK_DELETED: 'objectMarkDeleted',
		NEW_RECENTS_FILE: 'newRecentsFile',
		RECENT_FILE_MOVED: 'recentFileMoved',
	};

	const StatefulListActionType = {
		[Command.OBJECT_ADDED]: StatefulListPull.command.ADDED,
		[Command.OBJECT_RENAMED]: StatefulListPull.command.UPDATED,
		[Command.CONTENT_UPDATED]: StatefulListPull.command.UPDATED,
		[Command.OBJECT_MARK_DELETED]: StatefulListPull.command.DELETED,
		[Command.NEW_RECENTS_FILE]: StatefulListPull.command.ADDED,
		[Command.RECENT_FILE_MOVED]: StatefulListPull.command.ADDED,
	};

	const ObjectType = {
		FOLDER: 2,
		FILE: 3,
	};

	const isCommandExists = (command) => Boolean(StatefulListActionType[command]);

	const resolveObjectId = (pullMessage) => {
		if (
			pullMessage.command === Command.NEW_RECENTS_FILE
			|| pullMessage.command === Command.RECENT_FILE_MOVED
		)
		{
			return pullMessage.params?.file?.id;
		}

		if (pullMessage.command === Command.OBJECT_ADDED)
		{
			return pullMessage.params?.addedObject?.id;
		}

		return pullMessage.params?.object?.id;
	};

	const createCommandExistsMiddleware = () => (data) => (isCommandExists(data?.command)
		? Promise.resolve(data)
		: Promise.reject(`Unknown pull command ${data?.command}`));

	const createCommandAllowedMiddleware = (allowedCommands = []) => (data) => {
		return allowedCommands.includes(data?.command)
			? Promise.resolve(data)
			: Promise.reject(`Ignore pull command ${data?.command}`);
	};

	const createResolveObjectIdMiddleware = () => (data) => {
		const objectId = resolveObjectId(data);

		return objectId
			? Promise.resolve({ ...data, objectId })
			: Promise.reject('Unknown object id');
	};

	const createResolveActionTypeMiddleware = () => (data) => {
		const actionType = StatefulListActionType[data?.command];

		return actionType
			? Promise.resolve({ ...data, actionType })
			: Promise.reject('Unknown action type');
	};

	const createRenameOnlyFilesMiddleware = () => (data) => {
		if (data?.command === Command.OBJECT_RENAMED)
		{
			const type = data?.params?.object?.type;
			if (type === ObjectType.FOLDER)
			{
				return Promise.reject('Only file renaming allowed');
			}
		}

		return Promise.resolve(data);
	};

	const createAddObjectsOnlyFromCurrentFolderMiddleware = (currentFolderId) => (data) => {
		if (data?.command === Command.OBJECT_ADDED)
		{
			const receivedFolderId = data?.params?.object?.id;

			return (currentFolderId && receivedFolderId && currentFolderId === receivedFolderId)
				? Promise.resolve(data)
				: Promise.reject('Adding object from another folder - skip');
		}

		return Promise.resolve(data);
	};

	const createUpdateObjectsOnlyFromCurrentFolderMiddleware = (currentFolderId) => (data) => {
		const actionType = StatefulListActionType[data?.command];

		if (actionType === StatefulListPull.command.UPDATED)
		{
			const receivedFolderId = data?.params?.object?.id;

			return (currentFolderId && receivedFolderId && currentFolderId === receivedFolderId)
				? Promise.resolve(data)
				: Promise.reject('Updating object from another folder - skip');
		}

		return Promise.resolve(data);
	};

	const createUpdateObjectsOnlyFromCurrentStatefulListMiddleware = (itemIds) => (data) => {
		const actionType = StatefulListActionType[data?.command];

		if (actionType === StatefulListPull.command.UPDATED && itemIds && Array.isArray(itemIds))
		{
			const objectId = resolveObjectId(data);

			return itemIds.includes(objectId)
				? Promise.resolve(data)
				: Promise.reject('Updating object not presented in current list - skip');
		}

		return Promise.resolve(data);
	};

	const createAddRecentFileOnlyFromCurrentStorageMiddleware = (currentStorageId) => (data) => {
		if (
			(data?.command === Command.NEW_RECENTS_FILE || data?.command === Command.RECENT_FILE_MOVED)
			&& currentStorageId
		)
		{
			const receivedStorageId = data?.params?.file?.storageId;

			return (receivedStorageId && receivedStorageId === currentStorageId)
				? Promise.resolve(data)
				: Promise.reject('Add recent file from another storage - skip');
		}

		return Promise.resolve(data);
	};

	const DiskPull = {
		Command,
		StatefulListActionType,
		isCommandExists,
		resolveObjectId,
	};

	module.exports = {
		DiskPull,
		createCommandExistsMiddleware,
		createCommandAllowedMiddleware,
		createResolveObjectIdMiddleware,
		createResolveActionTypeMiddleware,
		createRenameOnlyFilesMiddleware,
		createAddObjectsOnlyFromCurrentFolderMiddleware,
		createUpdateObjectsOnlyFromCurrentFolderMiddleware,
		createUpdateObjectsOnlyFromCurrentStatefulListMiddleware,
		createAddRecentFileOnlyFromCurrentStorageMiddleware,
	};
});
