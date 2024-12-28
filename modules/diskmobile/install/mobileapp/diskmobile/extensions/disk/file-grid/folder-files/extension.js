/**
 * @module disk/file-grid/folder-files
 */
jn.define('disk/file-grid/folder-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { BaseFileGrid } = require('disk/file-grid/base');

	class FolderFilesGrid extends BaseFileGrid
	{
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
				title = Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_TITLE');
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
