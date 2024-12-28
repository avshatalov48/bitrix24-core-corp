/**
 * @module disk/file-grid/my-files
 */
jn.define('disk/file-grid/my-files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseFileGrid } = require('disk/file-grid/base');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Tourist } = require('tourist');
	const { DiskAnalyticsEvent } = require('disk/analytics');
	const { selectById: selectStorageById } = require('disk/statemanager/redux/slices/storages');
	const { selectById: selectFolderById } = require('disk/statemanager/redux/slices/files/selector');
	const store = require('statemanager/redux/store');
	const { Link4, LinkMode, LinkDesign } = require('ui-system/blocks/link');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons');
	const {
		SearchType,
		SearchEntity,
	} = require('disk/enum');

	class MyFilesGrid extends BaseFileGrid
	{
		getId()
		{
			return 'MyFilesGrid';
		}

		componentDidMount()
		{
			super.componentDidMount();

			BX.addCustomEvent('disk.tabs:openUploaderDialogCommand', this.onFloatingButtonClick);
			BX.addCustomEvent('disk.tabs:openCreateFolderDialogCommand', this.onFloatingButtonLongClick);
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			BX.removeCustomEvent('disk.tabs:openUploaderDialogCommand', this.onFloatingButtonClick);
			BX.removeCustomEvent('disk.tabs:openCreateFolderDialogCommand', this.onFloatingButtonClick);
		}

		onTabReady()
		{
			if (this.isFirstVisitOfCollabRootFolder())
			{
				this.displayFloatingButtonAhaMoment({
					delay: 300,
					description: Loc.getMessage('M_DISK_UPLOAD_BUTTON_FOR_COLLABER_AHA_MOMENT'),
					onHide: () => Tourist.remember(`open_collab_folder_${this.getFolderId()}`).then(() => {
						this.stateFulListRef?.updateFloatingButton({
							accentByDefault: this.stateFulListRef?.isEmptyList(),
							hide: !this.isShowFloatingButton(),
						});
					}),
				});
			}
		}

		isShowFloatingButton()
		{
			return this.canUserUploadToFolder();
		}

		isFloatingButtonAccent()
		{
			return this.isFirstVisitOfCollabRootFolder() ? true : undefined;
		}

		isFirstVisitOfCollabRootFolder()
		{
			const event = `open_collab_folder_${this.getFolderId()}`;

			return (
				this.currentUserIsCollaber()
				&& this.isRootCollabFolder()
				&& Tourist.firstTime(event)
			);
		}

		isRootCollabFolder()
		{
			return (this.isCollabFolder() && this.breadcrumbs.length === 1);
		}

		fetchStorage()
		{
			(new RunActionExecutor('diskmobile.Storage.getPersonalStorage', {}))
				.setCacheId(`disk.personalStorage${env.userId}`)
				.setCacheHandler((result) => this.onStorageLoaded(result, true))
				.setHandler((result) => this.onStorageLoaded(result, false))
				.call(true);
		}

		getSearchContext()
		{
			return {
				type: (this.isRootFolder() || this.forcedGlobalSearch)
					? SearchType.GLOBAL
					: SearchType.DIRECTORY,
				entities: [SearchEntity.USER, SearchEntity.GROUP],
			};
		}

		getEmptyListComponentProps = () => {
			let title = Loc.getMessage('M_DISK_MY_FILES_EMPTY_TITLE');
			let description = Loc.getMessage('M_DISK_MY_FILES_EMPTY_DESCRIPTION');
			let imageUri = makeLibraryImagePath('my-files.svg', 'empty-states', 'disk');
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
			else if (this.isRootCollabFolder())
			{
				title = Loc.getMessage('M_DISK_COLLAB_FILES_EMPTY_TITLE');
				description = Loc.getMessage('M_DISK_COLLAB_FILES_EMPTY_DESCRIPTION');
				buttons = [
					Link4({
						testId: this.getTestId('empty-screen-read-more'),
						text: Loc.getMessage('M_DISK_COMMON_READ_MORE'),
						mode: LinkMode.SOLID,
						design: LinkDesign.LIGHT_GREY,
						onClick: () => helpdesk.openHelpArticle('22707648', 'helpdesk'),
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
			const subsection = this.isCollabFolder()
				? DiskAnalyticsEvent.Subsection.COLLAB_FILES
				: DiskAnalyticsEvent.Subsection.MY_FILES;

			(new DiskAnalyticsEvent())
				.setEvent(DiskAnalyticsEvent.Event.ADD_FOLDER)
				.setSection(DiskAnalyticsEvent.Section.FILES)
				.setSubSection(subsection)
				.send();
		}

		sendUploadFileAnalytics(files = [])
		{
			let subsection = DiskAnalyticsEvent.Subsection.MY_FILES;
			let collabId = null;

			if (this.isCollabFolder())
			{
				subsection = DiskAnalyticsEvent.Subsection.COLLAB_FILES;
				const folder = selectFolderById(store.getState(), this.getFolderId());
				if (folder)
				{
					collabId = selectStorageById(store.getState(), folder.realStorageId)?.entityId ?? null;
				}
			}

			files.forEach((file) => {
				(DiskAnalyticsEvent.createFromFile(file))
					.setEvent(DiskAnalyticsEvent.Event.UPLOAD_FILE)
					.setSection(DiskAnalyticsEvent.Section.FILES)
					.setSubSection(subsection)
					.setCollabId(collabId)
					.send();
			});
		}
	}

	module.exports = { MyFilesGrid };
});
