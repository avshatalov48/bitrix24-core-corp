/**
 * @module disk/simple-list/items/file-redux/action-menu
 */
jn.define('disk/simple-list/items/file-redux/action-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { confirmDestructiveAction, Alert } = require('alert');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');
	const { Filesystem } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');
	const { isEmpty } = require('utils/object');

	const { FolderCode } = require('disk/enum');
	const { RenameDialog } = require('disk/dialogs/rename');
	const { removeObject } = require('disk/remove');
	const { moveObject } = require('disk/move');
	const { copyObject } = require('disk/copy');

	const { selectById } = require('disk/statemanager/redux/slices/files/selector');

	const store = require('statemanager/redux/store');

	const { fetchObjectWithRights } = require('disk/rights');

	const Sections = {
		EDIT: 'edit',
		REMOVE: 'remove',
		SHARING: 'sharing',
	};

	/**
	 * @typedef {Object} Order
	 * @property {'ASC' | 'DESC'} NAME
	 * @property {'ASC' | 'DESC'} CREATE_TIME
	 * @property {'ASC' | 'DESC'} UPDATE_TIME
	 * @property {'ASC' | 'DESC'} SIZE
	 * @property {'ASC' | 'DESC'} ID
	 */

	/**
	 * @class ActionMenu
	 * @param {number} objectId
	 * @param {Order} order
	 * @param {{ storageId: number }} context
	 * @param {object} parentWidget
	 */
	class ActionMenu
	{
		constructor(objectId, order, context, parentWidget)
		{
			this.objectId = objectId;
			this.order = order;
			this.context = context;
			this.parentWidget = parentWidget;
			this.object = selectById(store.getState(), objectId);
			if (!this.context.storageId)
			{
				this.context.storageId = this.object.storageId;
			}
		}

		/**
		 * @type {FileRights|FolderRights}
		 */
		get rights()
		{
			return this.object?.rights ?? {};
		}

		get actions()
		{
			const actions = [];
			if (this.object?.links?.download)
			{
				actions.push({
					id: 'share',
					title: Loc.getMessage('M_DISK_FILE_ACTIONS_SHARE'),
					sectionCode: Sections.SHARING,
					iconName: Icon.SHARE,
					onItemSelected: () => {
						dialogs.showLoadingIndicator();
						Filesystem.downloadFile(withCurrentDomain(this.object.links.download))
							.then((localUrl) => {
								dialogs.showSharingDialog({
									uri: localUrl,
								});
								dialogs.hideLoadingIndicator();
							}).catch(e => {
								console.error(e);
								Alert.alert(
									Loc.getMessage('M_DISK_FILE_ACTIONS_SHARE_ERROR_TITLE'),
									Loc.getMessage('M_DISK_FILE_ACTIONS_SHARE_ERROR_DESCRIPTION'),
								);
								dialogs.hideLoadingIndicator();
							});
					},
					sort: 100,
				});
			}

			if (this.rights.canRename)
			{
				actions.push(
					{
						id: 'rename',
						title: Loc.getMessage('M_DISK_FILE_ACTIONS_RENAME'),
						sectionCode: Sections.EDIT,
						iconName: Icon.EDIT,
						onItemSelected: () => {
							RenameDialog.open({
								objectId: this.objectId,
							});
						},
					},
				);
			}

			if (this.rights.canMarkDeleted)
			{
				actions.push(
					{
						id: 'move',
						title: Loc.getMessage('M_DISK_FILE_ACTIONS_MOVE'),
						sectionCode: Sections.SHARING,
						iconName: Icon.FOLDER_SUCCESS,
						onItemSelected: () => {
							moveObject(this.objectId, this.order, this.context, this.parentWidget);
						},
						sort: 300,
					},
				);
			}

			if (this.object.id === this.object.realObjectId)
			{
				actions.push(
					{
						id: 'copy',
						title: Loc.getMessage('M_DISK_FILE_ACTIONS_COPY'),
						sectionCode: Sections.SHARING,
						iconName: Icon.COPY,
						onItemSelected: () => {
							copyObject(this.objectId, this.order, this.context, this.parentWidget);
						},
						sort: 400,
					},
				);
			}

			if (this.rights.canMarkDeleted && this.object.code !== FolderCode.FOR_UPLOADED_FILES)
			{
				actions.push(
					{
						id: 'remove',
						title: Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE'),
						sectionCode: Sections.REMOVE,
						iconName: Icon.TRASHCAN,
						isDestructive: true,
						onItemSelected: () => {
							confirmDestructiveAction({
								title: this.object.isFolder
									? Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE_FOLDER_CONFIRM_TITLE')
									: Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE_FILE_CONFIRM_TITLE'),
								description: this.object.isFolder
									? Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE_FOLDER_CONFIRM_DESCRIPTION')
									: Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE_FILE_CONFIRM_DESCRIPTION'),
								destructionText: Loc.getMessage('M_DISK_FILE_ACTIONS_REMOVE_CONFIRM_ACCEPT'),
								onDestruct: () => {
									removeObject(this.objectId);
								},
							});
						},
					},
				);
			}

			return actions;
		}

		// discuss: {
		// 	id: 'discuss',
		// 	title: Loc.getMessage('M_DISK_FILE_ACTIONS_SEND_TO_CHAT'),
		// 	sectionCode: Sections.SHARING,
		// 	iconName: Icon.CHATS,
		// 	onItemSelected: () => {},
		// 	sort: 200,
		// },

		async show(target)
		{
			if (isEmpty(this.rights))
			{
				this.object = await fetchObjectWithRights(this.objectId);
			}
			void new UIMenu(this.actions).show({ target });
		}
	}
	module.exports = { ActionMenu };
});
