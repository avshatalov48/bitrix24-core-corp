/**
 * @module disk/file-grid/navigation/src/more-menu
 */

jn.define('disk/file-grid/navigation/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	const { BaseListMoreMenu } = require('layout/ui/list/base-more-menu');
	const { FileGridSorting } = require('disk/file-grid/navigation/src/sorting');

	const { selectShowFileExtension, setShowFileExtension } = require('disk/statemanager/redux/slices/settings');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	/**
	 * @class FileGridMoreMenu
	 */
	class FileGridMoreMenu extends BaseListMoreMenu
	{
		/**
		 * @param {String} selectedSorting
		 * @param {Boolean} isASC
		 * @param {Boolean} showFileExtension
		 * @param {Object} callbacks
		 */
		constructor(
			selectedSorting,
			isASC,
			callbacks = {},
		)
		{
			super([], null, selectedSorting, callbacks);

			this.isASC = isASC;
			this.showFileExtension = selectShowFileExtension(store.getState());
			this.canCreateFolder = Boolean(callbacks.onCreateFolder);

			this.onSelectSorting = callbacks.onSelectSorting;
			this.onToggleOrder = callbacks.onToggleOrder;
			this.onCreateFolder = callbacks.onCreateFolder;
			this.onToggleFileExtensions = callbacks.onToggleFileExtensions;
			this.onOpenTrashcan = callbacks.onOpenTrashcan;

			this.unsubscribe = store.subscribe(this.handleStorageChange);
		}

		handleStorageChange = () => {
			this.showFileExtension = selectShowFileExtension(store.getState());
		};

		getIsASC()
		{
			return this.isASC;
		}

		/**
		 * @public
		 * @param {Boolean} enableFolderCreation
		 */
		setCanCreateFolder(enableFolderCreation)
		{
			this.canCreateFolder = Boolean(enableFolderCreation);
		}

		/**
		 * @public
		 */
		getCanCreateFolder()
		{
			return this.canCreateFolder;
		}

		/**
		 * @public
		 * @returns {{callback: ((function(): void)|*), type: string, id: string, testId: string}}
		 */
		getMenuButton()
		{
			return {
				type: 'more',
				id: 'file-grid-more',
				testId: 'file-grid-more',
				callback: this.openMoreMenu,
			};
		}

		/**
		 * @private
		 * @returns {Array}
		 */
		getMenuItems()
		{
			const orderIcon = this.isASC ? Icon.ARROW_TOP : Icon.ARROW_DOWN;
			const sortingId = (sortField, selectedSorting) => {
				const order = this.isASC ? 'asc' : 'desc';
				const active = sortField === selectedSorting ? '-active' : '';

				return `${sortField}::${order}${active}`;
			};

			const items = [];

			if (this.onCreateFolder && this.canCreateFolder)
			{
				items.push(
					this.createMenuItem({
						id: 'createFolder',
						title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_CREATE_FOLDER'),
						checked: false,
						icon: Icon.FOLDER,
						showCheckedIcon: false,
					}),
				);
			}

			if (this.onSelectSorting)
			{
				items.push(
					this.createMenuItem({
						id: sortingId(FileGridSorting.types.UPDATE_TIME, this.selectedSorting),
						title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SORTING_UPDATE_TIME'),
						showIcon: this.selectedSorting === FileGridSorting.types.UPDATE_TIME,
						icon: orderIcon,
						showTopSeparator: true,
						sectionCode: 'sorting',
						sectionTitle: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SORTING_SECTION_TITLE'),
					}),

					this.createMenuItem({
						id: sortingId(FileGridSorting.types.CREATE_TIME, this.selectedSorting),
						title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SORTING_CREATE_TIME'),
						showIcon: this.selectedSorting === FileGridSorting.types.CREATE_TIME,
						icon: orderIcon,
						sectionCode: 'sorting',
					}),

					this.createMenuItem({
						id: sortingId(FileGridSorting.types.NAME, this.selectedSorting),
						title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SORTING_NAME'),
						showIcon: this.selectedSorting === FileGridSorting.types.NAME,
						icon: orderIcon,
						sectionCode: 'sorting',
					}),

					this.createMenuItem({
						id: sortingId(FileGridSorting.types.SIZE, this.selectedSorting),
						title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SORTING_SIZE'),
						showIcon: this.selectedSorting === FileGridSorting.types.SIZE,
						icon: orderIcon,
						sectionCode: 'sorting',
					}),
				);
			}

			items.push(
				this.createMenuItem({
					id: 'toggleShowFileExtension',
					title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_SHOW_FILE_EXTENSIONS'),
					sectionCode: 'toggleShowFileExtension',
					checked: this.showFileExtension,
				}),

				this.createMenuItem({
					id: 'trashcan',
					title: Loc.getMessage('M_DISK_FILE_GRID_MORE_MENU_TRASHCAN'),
					icon: Icon.TRASHCAN,
					sectionCode: 'trashcan',
				}),
			);

			return items;
		}

		/**
		 * @private
		 * @param event
		 * @param item
		 */
		onMenuItemSelected(event, item)
		{
			const realItemId = String(item.id).split('::')[0];

			if (FileGridSorting.types[realItemId])
			{
				this.selectSorting(realItemId);

				return;
			}

			switch (realItemId)
			{
				case 'checkItems':
					this.checkItems();
					break;
				case 'createFolder':
					this.createFolder();
					break;
				case 'toggleShowFileExtension':
					this.toggleShowFileExtension();
					break;
				case 'trashcan':
					this.openTrashcan();
					break;
				default:
					break;
			}
		}

		checkItems()
		{}

		createFolder()
		{
			this.onCreateFolder();
		}

		selectSorting(sorting)
		{
			if (this.selectedSorting === sorting)
			{
				this.toggleOrder();
			}
			this.onSelectSorting(sorting);
		}

		toggleOrder()
		{
			this.isASC = !this.isASC;
			this.onToggleOrder(this.isASC);
		}

		toggleShowFileExtension()
		{
			this.showFileExtension = !this.showFileExtension;
			dispatch(setShowFileExtension(this.showFileExtension));
		}

		openTrashcan()
		{
			if (this.onOpenTrashcan)
			{
				this.onOpenTrashcan();
			}
		}
	}

	module.exports = { FileGridMoreMenu };
});
