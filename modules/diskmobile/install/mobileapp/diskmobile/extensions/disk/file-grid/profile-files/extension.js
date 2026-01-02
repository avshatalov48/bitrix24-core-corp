/**
 * @module disk/file-grid/profile-files
 */
jn.define('disk/file-grid/profile-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { BaseFileGrid } = require('disk/file-grid/base');

	class ProfileFilesGrid extends BaseFileGrid
	{
		getId()
		{
			return 'ProfileFilesGrid';
		}

		getListActions()
		{
			return {
				loadItems: 'diskmobile.Profile.getChildren',
			};
		}

		isShowFloatingButton()
		{
			return this.state.folderRights?.canAdd ?? false;
		}

		getListActionParams()
		{
			return {
				loadItems: {
					id: this.getFolderId(),
					search: this.getSearchParams().searchString,
					searchContext: this.getSearchContext(),
					sortingType: this.state.sortingType,
					order: {
						[this.state.sortingType]: this.state.isASC ? 'ASC' : 'DESC',
					},
					context: this.props.context || {
						storageId: this.getStorageId(),
					},
					showRights: 'Y',
				},
			};
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

	module.exports = { ProfileFilesGrid };
});
