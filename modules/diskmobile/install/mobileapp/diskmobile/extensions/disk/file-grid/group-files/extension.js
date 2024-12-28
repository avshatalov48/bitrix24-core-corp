/**
 * @module disk/file-grid/group-files
 */
jn.define('disk/file-grid/group-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseFileGrid } = require('disk/file-grid/base');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons');
	const { DiskAnalyticsEvent } = require('disk/analytics');
	const {
		SearchType,
		SearchEntity,
	} = require('disk/enum');

	class GroupFilesGrid extends BaseFileGrid
	{
		getId()
		{
			return 'GroupFiles';
		}

		fetchStorage()
		{
			const options = { groupId: this.props.groupId };

			(new RunActionExecutor('diskmobile.Storage.getBySocialGroup', options))
				.setCacheId(`disk.groupStorage${this.props.groupId}`)
				.setCacheHandler((result) => this.onStorageLoaded(result, true))
				.setHandler((result) => this.onStorageLoaded(result, false))
				.call(true);
		}

		getSearchContext()
		{
			return {
				type: SearchType.DIRECTORY,
				entities: [],
				folderId: this.forcedGlobalSearch ? this.storage?.rootObjectId : null,
			};
		}

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_GROUP_FILES_EMPTY_TITLE');
			let description = Loc.getMessage('M_DISK_GROUP_FILES_EMPTY_DESCRIPTION_MSGVER_1');
			let imageUri = makeLibraryImagePath('my-files.svg', 'empty-states', 'disk');
			let buttons = [];

			if (this.isSearching())
			{
				const isGlobalSearch = this.isRootFolder() || this.forcedGlobalSearch;

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

		sendCreateFolderAnalytics()
		{
			(new DiskAnalyticsEvent())
				.setEvent(DiskAnalyticsEvent.Event.ADD_FOLDER)
				.setSection(DiskAnalyticsEvent.Section.PROJECT)
				.setSubSection(DiskAnalyticsEvent.Subsection.PROJECT_FILES)
				.send();
		}

		sendUploadFileAnalytics(files = [])
		{
			files.forEach((file) => {
				(DiskAnalyticsEvent.createFromFile(file))
					.setEvent(DiskAnalyticsEvent.Event.UPLOAD_FILE)
					.setSection(DiskAnalyticsEvent.Section.PROJECT)
					.setSubSection(DiskAnalyticsEvent.Subsection.PROJECT_FILES)
					.send();
			});
		}
	}

	module.exports = { GroupFilesGrid };
});
