/**
 * @module disk/file-grid/collab-recent-files
 */
jn.define('disk/file-grid/collab-recent-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Link4, LinkMode, LinkDesign } = require('ui-system/blocks/link');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { BaseFileGrid } = require('disk/file-grid/base');
	const { FileGridMoreMenu } = require('disk/file-grid/navigation');
	const { DiskAnalyticsEvent } = require('disk/analytics');
	const {
		DiskPull,
		createRenameOnlyFilesMiddleware,
		createAddRecentFileOnlyFromCurrentStorageMiddleware,
	} = require('disk/pull');

	class CollabRecentFiles extends BaseFileGrid
	{
		constructor(props)
		{
			super(props);

			this.images = {};

			this.moreMenu = new FileGridMoreMenu(
				this.sorting.getType(),
				this.sorting.getIsASC(),
				{
					onToggleFileExtensions: this.onToggleFileExtensions,
					onOpenTrashcan: this.onOpenTrashcan,
				},
			);
		}

		getId()
		{
			return 'CollabRecentFiles';
		}

		isShowFloatingButton()
		{
			return this.state.folderRights?.canAdd;
		}

		isCollabFolder()
		{
			return true;
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		showFolders()
		{
			return false;
		}

		getListActions()
		{
			return {
				loadItems: 'diskmobile.Recent.getByCollabId',
			};
		}

		getListActionParams()
		{
			return {
				loadItems: {
					groupId: this.props.collabId,
					search: this.getSearchParams().searchString,
				},
			};
		}

		fetchStorage()
		{
			const options = { groupId: this.props.collabId };

			(new RunActionExecutor('diskmobile.Storage.getBySocialGroup', options))
				.setCacheId(`disk.collabStorage${this.props.collabId}`)
				.setCacheHandler((result) => this.onStorageLoaded(result, true))
				.setHandler((result) => this.onStorageLoaded(result, false))
				.call(true);
		}

		getFolderId()
		{
			return this.storage?.rootObjectId || null;
		}

		onFloatingButtonLongClick = () => {};

		/**
		 * @protected
		 * @param {DiskStorageResponse} response
		 * @param {boolean} cached
		 */
		onStorageLoaded(response, cached)
		{
			if (this.handleStorageLoadFailure(response, cached))
			{
				return;
			}

			this.storage = response.data.storage;

			this.setState({ loading: false, folderRights: response.data.rootFolderRights });
		}

		getAllowedPullCommands()
		{
			return [
				DiskPull.Command.OBJECT_RENAMED,
				DiskPull.Command.CONTENT_UPDATED,
				DiskPull.Command.OBJECT_MARK_DELETED,
				DiskPull.Command.NEW_RECENTS_FILE,
				DiskPull.Command.RECENT_FILE_MOVED,
			];
		}

		getPullCommandProcessors()
		{
			return [
				createRenameOnlyFilesMiddleware(),
				createAddRecentFileOnlyFromCurrentStorageMiddleware(this.getStorageId()),
			];
		}

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_COLLAB_FILES_EMPTY_TITLE');
			let description = Loc.getMessage('M_DISK_COLLAB_FILES_EMPTY_DESCRIPTION');
			let imageUri = makeLibraryImagePath('my-files.svg', 'empty-states', 'disk');
			let buttons = [
				Link4({
					testId: this.getTestId('empty-screen-read-more'),
					text: Loc.getMessage('M_DISK_COMMON_READ_MORE'),
					mode: LinkMode.SOLID,
					design: LinkDesign.LIGHT_GREY,
					onClick: () => helpdesk.openHelpArticle('22707648', 'helpdesk'),
				}),
			];

			if (this.isSearching())
			{
				title = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_TITLE');
				description = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_DESCRIPTION');
				imageUri = makeLibraryImagePath('search.svg', 'empty-states');
				buttons = undefined;
			}
			else if (!this.isRootFolder())
			{
				title = Loc.getMessage('M_DISK_EMPTY_FOLDER_TITLE');
				description = undefined;
				buttons = undefined;
			}

			return {
				title,
				description,
				imageUri,
				buttons,
			};
		};

		sendUploadFileAnalytics(files = [])
		{
			files.forEach((file) => {
				(DiskAnalyticsEvent.createFromFile(file))
					.setEvent(DiskAnalyticsEvent.Event.UPLOAD_FILE)
					.setSection(DiskAnalyticsEvent.Section.COLLAB)
					.setSubSection(DiskAnalyticsEvent.Subsection.COLLAB_FILES)
					.setCollabId(this.props.collabId)
					.send();
			});
		}
	}

	module.exports = { CollabRecentFiles };
});
