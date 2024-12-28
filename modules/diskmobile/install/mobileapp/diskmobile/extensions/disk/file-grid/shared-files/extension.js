/**
 * @module disk/file-grid/shared-files
 */
jn.define('disk/file-grid/shared-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseFileGrid } = require('disk/file-grid/base');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { makeLibraryImagePath } = require('asset-manager');
	const { DiskAnalyticsEvent } = require('disk/analytics');
	const { SearchType, SearchEntity } = require('disk/enum');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons');

	class SharedFilesGrid extends BaseFileGrid
	{
		getId()
		{
			return 'SharedFilesGrid';
		}

		fetchStorage()
		{
			(new RunActionExecutor('diskmobile.Storage.getSharedStorage', {}))
				.setCacheId(`disk.commonStorage${env.userId}`)
				.setCacheHandler((result) => this.onStorageLoaded(result, true))
				.setHandler((result) => this.onStorageLoaded(result, false))
				.call(true);
		}

		/**
		 * @protected
		 * @param {DiskStorageResponse} response
		 * @param {boolean} cached
		 * @return {boolean}
		 */
		handleStorageLoadFailure(response, cached)
		{
			if (response.errors.length > 0)
			{
				this.setState({
					loading: false,
				});

				return true;
			}

			return false;
		}

		isShowFloatingButton()
		{
			return this.state.folderRights?.canAdd;
		}

		getListActions()
		{
			return {
				loadItems: this.getFolderId() ? 'diskmobile.Folder.getChildren' : 'diskmobile.Shared.getChildren',
			};
		}

		getSearchContext()
		{
			return {
				type: (this.isRootFolder() || this.forcedGlobalSearch)
					? SearchType.GLOBAL
					: SearchType.DIRECTORY,
				entities: [SearchEntity.COMMON],
			};
		}

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_SHARED_FILES_EMPTY_TITLE');
			let description = Loc.getMessage('M_DISK_SHARED_FILES_EMPTY_DESCRIPTION');
			let imageUri = makeLibraryImagePath('shared-files.svg', 'empty-states', 'disk');
			let buttons = [];

			if (this.isSearching())
			{
				const isGlobalSearch = this.getSearchContext()?.type === SearchType.GLOBAL;

				title = isGlobalSearch
					? Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_TITLE')
					: Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_TITLE_MSGVER_2');

				description = isGlobalSearch
					? Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_GLOBAL_DESCRIPTION')
					: Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_DESCRIPTION_MSGVER_2');

				imageUri = makeLibraryImagePath('search.svg', 'empty-states');

				buttons = isGlobalSearch ? [] : [
					Button({
						testId: this.getTestId('empty-screen-search-everywhere'),
						design: ButtonDesign.OUTLINE_ACCENT_2,
						size: ButtonSize.M,
						text: Loc.getMessage('M_DISK_EMPTY_SEARCH_RESULT_SEARCH_EVERYWHERE'),
						onClick: () => this.forceGlobalSearch(),
					}),
				];
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
				buttons,
			};
		};

		/**
		 * @protected
		 * @return {string}
		 */
		getTrashWebUrl()
		{
			return '/docs/shared/trashcan/';
		}

		sendCreateFolderAnalytics()
		{
			(new DiskAnalyticsEvent())
				.setEvent(DiskAnalyticsEvent.Event.ADD_FOLDER)
				.setSection(DiskAnalyticsEvent.Section.FILES)
				.setSubSection(DiskAnalyticsEvent.Subsection.COMPANY_FILES)
				.send();
		}

		sendUploadFileAnalytics(files = [])
		{
			files.forEach((file) => {
				(DiskAnalyticsEvent.createFromFile(file))
					.setEvent(DiskAnalyticsEvent.Event.UPLOAD_FILE)
					.setSection(DiskAnalyticsEvent.Section.FILES)
					.setSubSection(DiskAnalyticsEvent.Subsection.COMPANY_FILES)
					.send();
			});
		}
	}

	module.exports = { SharedFilesGrid };
});
