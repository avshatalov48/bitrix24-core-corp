/**
 * @module disk/file-grid/folder-files
 */
jn.define('disk/file-grid/folder-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { BaseFileGrid } = require('disk/file-grid/base');

	class FolderFilesGrid extends BaseFileGrid
	{
		constructor(props)
		{
			super(props);

			this.scrollToItemId = props.scrollToItemId;
		}

		onListRef = (ref) => {
			if (ref)
			{
				this.stateFulListRef = ref;
				if (this.scrollToItemId)
				{
					setTimeout(() => {
						void this.scrollToFirstOf([this.scrollToItemId], true, true, false);
						this.scrollToItemId = null;
					}, 500);
				}
			}
		};

		getId()
		{
			return 'FolderFilesGrid';
		}

		fetchStorage()
		{
			this.setState({ loading: false });
		}

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_EMPTY_FOLDER_TITLE');
			let imageUri = makeLibraryImagePath('my-files.svg', 'empty-states', 'disk');
			let description = null;

			if (this.isSearching())
			{
				title = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_TITLE_MSGVER_2');
				description = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_DESCRIPTION');
				imageUri = makeLibraryImagePath('search.svg', 'empty-states');
			}

			return {
				title,
				description,
				imageUri,
			};
		};
	}

	module.exports = { FolderFilesGrid };
});
