/**
 * @module disk/file-grid/recent-files
 */
jn.define('disk/file-grid/recent-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { BaseFileGrid } = require('disk/file-grid/base');
	const { FileGridMoreMenu } = require('disk/file-grid/navigation');
	const { DiskPull, createRenameOnlyFilesMiddleware } = require('disk/pull');

	class RecentFilesGrid extends BaseFileGrid
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
			return 'RecentFilesGrid';
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		showFolders()
		{
			return false;
		}

		showStorageName()
		{
			return true;
		}

		fetchStorage()
		{
			this.setState({ loading: false });
		}

		getListActions()
		{
			return {
				loadItems: 'diskmobile.Recent.get',
			};
		}

		getListActionParams()
		{
			return {
				loadItems: {
					search: this.getSearchParams().searchString,
				},
			};
		}

		getAllowedPullCommands()
		{
			return [
				DiskPull.Command.OBJECT_RENAMED,
				DiskPull.Command.CONTENT_UPDATED,
				DiskPull.Command.OBJECT_MARK_DELETED,
				DiskPull.Command.NEW_RECENTS_FILE,
			];
		}

		getPullCommandProcessors()
		{
			return [
				createRenameOnlyFilesMiddleware(),
			];
		}

		onFloatingButtonClick = () => {
			BX.postComponentEvent('disk.tabs.recent:onFloatingButtonClick', [], 'disk.tabs');
		};

		onFloatingButtonLongClick = () => {
			BX.postComponentEvent('disk.tabs.recent:onFloatingButtonLongClick', [], 'disk.tabs');
		};

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_RECENT_FILES_EMPTY_TITLE');
			let description = Loc.getMessage('M_DISK_RECENT_FILES_EMPTY_DESCRIPTION');
			let imageUri = makeLibraryImagePath('recent-files.svg', 'empty-states', 'disk');

			if (this.isSearching())
			{
				title = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_TITLE');
				description = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_DESCRIPTION');
				imageUri = makeLibraryImagePath('search.svg', 'empty-states');
			}
			else if (!this.isRootFolder())
			{
				title = Loc.getMessage('M_DISK_EMPTY_FOLDER_TITLE');
				description = undefined;
			}

			return {
				title,
				description,
				imageUri,
			};
		};
	}

	module.exports = { RecentFilesGrid };
});
