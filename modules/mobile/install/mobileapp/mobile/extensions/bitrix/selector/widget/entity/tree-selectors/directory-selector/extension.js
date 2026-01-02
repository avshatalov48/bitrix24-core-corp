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
			super(options);

			this.rootTitle = options.widgetParams?.title;
			this.isMultipleSelectionMode = options.allowMultipleSelection;

			this.entity.addEvents({
				onItemSelected: this.onItemSelected,
				onClose: this.onClose,
			});
		}

		get canSelectRoot()
		{
			return this.options.selectOptions?.canSelectRoot ?? true;
		}

		getEntity()
		{
			return DirectorySelectorEntity;
		}

		getNodeEntityId()
		{
			return 'folder';
		}

		prepareOptions(options)
		{
			return {
				...options,
				allowMultipleSelection: true,
			};
		}

		prepareWidgetParams(options)
		{
			const isMultipleSelectionMode = options.allowMultipleSelection;
			const isShowDirectoriesOnlyMode = Boolean(options.provider?.options?.showDirectoriesOnly);

			const sendButtonName = options.widgetParams?.sendButtonName
				?? (
					!isMultipleSelectionMode && isShowDirectoriesOnlyMode
						? Loc.getMessage('DIRECTORY_SELECTOR_SELECT_DIRECTORY_ITEM')
						: null
				);

			return {
				...options.widgetParams,
				sendButtonName,
			};
		}

		prepareSearchOptions(options)
		{
			return {
				...options.searchOptions,
				onSearchCancelled: () => this.#onSearchCancelled(),
			};
		}

		prepareProvider(options)
		{
			const provider = { ...options.provider };
			const preparedProvider = Type.isPlainObject(provider) ? provider : {};
			const providerOptions = Type.isPlainObject(preparedProvider?.options) ? preparedProvider?.options : {};

			return {
				...provider,
				options: {
					...providerOptions,
					findInTree: Navigator.findInTree,
					onItemsLoaded: (items) => this.#onItemsLoaded(items),
					storageId: preparedProvider.storageId,
					getCurrentNode: () => this.getNavigator().getCurrentNode(),
					onStorageLoaded: (storage) => this.#onStorageLoaded(storage),
				},
			};
		}

		onClose = (selectedItems) => {
			const parentItem = this.navigator.getCurrentNode();

			this.options.events?.onClose?.({
				selectedItems,
				parentItem,
			});
		};

		#onStorageLoaded = (storage) => {
			this.getNavigator().init({
				entityId: this.getNodeEntityId(),
				...storage,
			});

			this.#setIsButtonActive(this.canSelectRoot);

			if (this.rootTitle)
			{
				return;
			}

			this.rootTitle = storage.type === 'user'
				? Loc.getMessage('DIRECTORY_SELECTOR_USER_ROOT_DIRECTORY_TITLE')
				: storage.name;

			this.setTitle(this.rootTitle);
		};

		#onItemsLoaded = (items) => {
			this.navigator.setCurrentNodeChildren(items);

			this.getSelector().setItems(
				this.getPreparedItems(items),
			);
		};

		onNodeChanged = (node) => {
			if (!node)
			{
				return;
			}

			let title = node.title || node.name;

			if (this.getNavigator().isRoot(node))
			{
				title = this.rootTitle;
				this.#setIsButtonActive(this.canSelectRoot);
			}
			else
			{
				this.#setIsButtonActive(true);
			}

			super.onNodeChanged({ title });
		};

		#setIsButtonActive = (state) => {
			this.getSelector().getWidget().setSendButtonVisible(state);
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
				shortTitle = this.rootTitle;
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
