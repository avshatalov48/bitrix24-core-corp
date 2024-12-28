/**
 * @module selector/widget/entity/tree-selectors/directory-selector
 */
jn.define('selector/widget/entity/tree-selectors/directory-selector', (require, exports, module) => {
	const { BaseTreeSelector } = require('selector/widget/entity/tree-selectors/base-tree-selector');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { DirectorySelectorEntity } = require('selector/widget/entity/tree-selectors/directory-selector-entity');
	const { Navigator } = require('selector/widget/entity/tree-selectors/shared/navigator');

	/**
	 * @class DirectorySelector
	 */
	class DirectorySelector extends BaseTreeSelector
	{
		/**
		 * @typedef {Object} WidgetParams
		 * @property {Object} backdrop - Backdrop settings.
		 * @property {number} backdrop.mediumPositionPercent - Medium position percentage for the backdrop.
		 * @property {boolean} backdrop.horizontalSwipeAllowed - Whether horizontal swipe is allowed.
		 * @property {string} [sendButtonName] - Name of the send button.
		 * @property {string} [title] - Title of the widget.
		 *
		 * @typedef {Object} Events
		 * @property {Function} onClose - Handler for the close event.
		 *
		 * @typedef {Object} ProviderOptions
		 * @property {boolean} showDirectoriesOnly - Whether to show directories only.
		 * @property {boolean} canSelectFiles - Whether files can be selected.
		 * @property {{NAME: string}|null} order - Order of the files.
		 *
		 * @typedef {Object} Provider
		 * @property {number} storageId - Storage ID for the provider.
		 * @property {ProviderOptions} options - Options for the provider.
		 *
		 * @typedef {Object} SelectOptions
		 * @property {boolean} canSelectRoot - Whether the root can be selected.
		 *
		 * @typedef {Object} Options
		 * @property {Array|null} initSelectedIds - Initial selected IDs.
		 * @property {Array|null} undeselectableIds - IDs that can't be selected.
		 * @property {WidgetParams} widgetParams - Parameters for the widget.
		 * @property {boolean} allowMultipleSelection - Whether multiple selection is allowed.
		 * @property {boolean} closeOnSelect - Whether to close on select.
		 * @property {Events} events - Event handlers.
		 * @property {Provider} provider - Provider settings.
		 * @property {SelectOptions} selectOptions - Options for selection.
		 */
		constructor(options = {})
		{
			const provider = Type.isPlainObject(options.provider) ? options.provider : {};
			const providerOptions = Type.isPlainObject(options.provider?.options) ? options.provider?.options : {};

			const isMultipleSelectionMode = options.allowMultipleSelection;
			const isShowDirectoriesOnlyMode = Boolean(options.provider?.options?.showDirectoriesOnly);

			const sendButtonName = options.widgetParams?.sendButtonName
				?? (
					!isMultipleSelectionMode && isShowDirectoriesOnlyMode
						? Loc.getMessage('DIRECTORY_SELECTOR_SELECT_DIRECTORY_ITEM')
						: null
				);

			super(DirectorySelectorEntity, {
				...options,
				allowMultipleSelection: true,
				widgetParams: {
					...options.widgetParams,
					sendButtonName,
				},
				events: {
					...options.events,
					onClose: (selectedItems) => {
						const parentItem = this.navigator.getCurrentNode();

						options.events?.onClose?.({
							selectedItems,
							parentItem,
						});
					},
				},
				searchOptions: {
					...options.searchOptions,
					onSearchCancelled: () => this.#onSearchCancelled(),
				},
				provider: {
					...provider,
					options: {
						...providerOptions,
						findInTree: Navigator.findInTree,
						onItemsLoaded: (items) => this.#onItemsLoaded(items),
						storageId: options.provider.storageId,
						getCurrentNode: () => this.getNavigator().getCurrentNode(),
						onStorageLoaded: (storage) => this.#onStorageLoaded(storage),
					},
				},
			});

			this.isMultipleSelectionMode = isMultipleSelectionMode;
			this.widgetRootDirectoryTitle = options.widgetParams?.title;
			this.canSelectRoot = options.selectOptions?.canSelectRoot ?? true;

			this.entity.addEvents({
				onItemSelected: this.onItemSelected,
			});

			this.recentTitle = options.widgetParams.title;
			this.nodeEntityId = 'folder';
		}

		#onStorageLoaded = (storage) => {
			this.navigator = new Navigator({
				onCurrentNodeChanged: this.#onDirectoryChanged,
			});

			this.navigator.init({
				entityId: this.nodeEntityId,
				...storage,
			});

			this.#setIsButtonActive(this.canSelectRoot);

			if (this.widgetRootDirectoryTitle)
			{
				return;
			}

			this.widgetRootDirectoryTitle = storage.type === 'user'
				? Loc.getMessage('DIRECTORY_SELECTOR_USER_ROOT_DIRECTORY_TITLE')
				: storage.name;

			this.#setTitle(this.widgetRootDirectoryTitle);
		};

		#onItemsLoaded = (items) => {
			this.navigator.setCurrentNodeChildren(items);

			this.getSelector().setItems(
				this.getPreparedItems(items),
			);
		};

		#onDirectoryChanged = (node) => {
			if (!node)
			{
				return;
			}

			let title = node.title || node.name;

			if (this.getNavigator().isRoot(node))
			{
				title = this.widgetRootDirectoryTitle;
				this.#setIsButtonActive(this.canSelectRoot);
			}
			else
			{
				this.#setIsButtonActive(true);
			}

			this.#setTitle(title);
		};

		#setIsButtonActive = (state) => {
			if (Application.getApiVersion() < 56)
			{
				this.getSelector().getWidget().setSendButtonEnabled(state);
			}
			else
			{
				this.getSelector().getWidget().setSendButtonVisible(state);
			}
		};

		#setTitle = (title, useProgress = false) => {
			this.getSelector().getWidget()?.setTitle({
				text: title,
				useProgress,
				type: 'wizard',
			});
		};

		onItemSelected = ({ item, text, scope }) => {
			this.resetSearch();

			super.onItemSelected({ item, text, scope });
		};

		#onSearchCancelled = () => {
			this.resetSearch();

			this.getSelector().setItems(
				this.getPreparedItems(
					this.getNavigator().getCurrentNodeChildren(),
				),
			);
		};

		getServiceElements()
		{
			const currentNode = this.getNavigator().getCurrentNode();
			let shortTitle = currentNode.title || currentNode.name;
			let canSelectCurrentNode = true;

			if (this.getNavigator().isRoot(currentNode))
			{
				shortTitle = this.widgetRootDirectoryTitle;
				canSelectCurrentNode = this.canSelectRoot;
			}

			return [
				this.isMultipleSelectionMode && canSelectCurrentNode && {
					...currentNode,
					title: Loc.getMessage('DIRECTORY_SELECTOR_SELECT_DIRECTORY_ITEM'),
					shortTitle,
					type: 'selectable',
				},
			].filter(Boolean);
		}
	}

	module.exports = { DirectorySelector };
});
