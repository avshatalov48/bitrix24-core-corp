/**
 * @module selector/widget/entity/tree-selectors/nested-department-selector
 */
jn.define('selector/widget/entity/tree-selectors/nested-department-selector', (require, exports, module) => {
	const { BaseTreeSelector } = require('selector/widget/entity/tree-selectors/base-tree-selector');
	const { NestedDepartmentSelectorEntity } = require('selector/widget/entity/tree-selectors/nested-department-selector-entity');
	const { ScopesIds } = require('selector/providers/tree-providers/nested-department-provider');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Navigator } = require('selector/widget/entity/tree-selectors/shared/navigator');
	const { Color } = require('tokens');

	/**
	 * @class NestedDepartmentSelector
	 */
	class NestedDepartmentSelector extends BaseTreeSelector
	{
		/**
		 * @typedef {Object} WidgetParams
		 * @property {string} title - Title of backdrop.
		 * @property {Object} backdrop - Backdrop settings.
		 * @property {number} backdrop.mediumPositionPercent - Medium position percentage for the backdrop.
		 * @property {boolean} backdrop.horizontalSwipeAllowed - Whether horizontal swipe is allowed.
		 *
		 * @typedef {Object} Events
		 * @property {Function} onClose - Handler for the close event.
		 *
		 * @typedef {Object} CreateOptions
		 * @property {boolean} enableCreation - Whether creation is enabled.
		 *
		 * @typedef {Object} SelectOptions
		 * @property {boolean} canUnselectLast - Whether the last item can be unselected.
		 * @property {boolean} singleEntityByType - Whether a single entity by type is allowed.
		 *
		 * @typedef {Object} ProviderOptions
		 * @property {boolean} useLettersForEmptyAvatar - Whether to use letters for empty avatars.
		 * @property {boolean} allowFlatDepartments - Whether flat departments are allowed.
		 * @property {boolean} allowSelectRootDepartment - Whether selecting the root department is allowed.
		 * @property {boolean} addMetaUser - Whether to add a meta user.
		 *
		 * @typedef {Object} Provider
		 * @property {string} context - Context for the provider.
		 * @property {ProviderOptions} options - Options for the provider.
		 *
		 * @typedef {Object} Options
		 * @property {Array|null} initSelectedIds - Initial selected IDs.
		 * @property {Array|null} undeselectableIds - IDs that can't be unselected.
		 * @property {WidgetParams} widgetParams - Widget parameters.
		 * @property {boolean} allowMultipleSelection - Whether multiple selection is allowed.
		 * @property {boolean} closeOnSelect - Whether to close on select.
		 * @property {Events} events - Event handlers.
		 * @property {CreateOptions} createOptions - Options for creation.
		 * @property {SelectOptions} selectOptions - Options for selection.
		 * @property {boolean} canUseRecent - Whether recent items can be used.
		 * @property {Provider} provider - Provider settings.
		 */
		constructor(options = {})
		{
			const provider = Type.isPlainObject(options.provider) ? options.provider : {};
			const providerOptions = Type.isPlainObject(options.provider?.options) ? options.provider?.options : {};

			super(NestedDepartmentSelectorEntity, {
				...options,
				searchOptions: {
					...options.searchOptions,
					onSearchCancelled: (data) => this.#onSearchCancelled(data),
					onSearch: (data) => this.#onSearch(data),
				},
				provider: {
					...provider,
					options: {
						...providerOptions,
						onItemsLoadedFromServer: (items) => this.#onItemsLoadedFromServer(items),
						onRecentLoaded: (items) => this.#onRecentLoaded(items),
						getScopeId: () => this.getCurrentScopeId(),
						findInTree: Navigator.findInTree,
					},
				},
				shouldAnimate: true,
			});

			this.navigator = new Navigator({
				onCurrentNodeChanged: this.#onDepartmentNodeChanged,
			});

			this.leftButtons = Type.isArrayFilled(options?.leftButtons) ? options?.leftButtons : [];

			this.entity.addEvents({
				onScopeChanged: this.#onScopeChanged,
				onItemSelected: this.onItemSelected,
			});

			this.recentTitle = options.widgetParams.title ?? NestedDepartmentSelectorEntity.getTitle();
			this.nodeEntityId = 'department';
		}

		getCurrentScopeId = () => {
			return this.scopeId || {};
		};

		#getScopes()
		{
			return [
				{ id: ScopesIds.RECENT, title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_RECENT') },
				{ id: ScopesIds.DEPARTMENT, title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_DEPARTMENTS') },
			];
		}

		#setScopeById = ({ scopeId, shouldResetSearch = true }) => {
			this.#onScopeChanged({
				scope: { id: scopeId },
				shouldResetSearch,
			});

			this.getSelector().getWidget().setScopeById(
				this.getCurrentScopeId(),
			);
		};

		#onRecentLoaded = (items) => {
			this.setTitle(
				this.recentTitle,
			);

			const rootDepartment = items.find(({ entityId }) => entityId === this.nodeEntityId);

			this.getNavigator().init(rootDepartment);
		};

		#onItemsLoadedFromServer = (items) => {
			let scopes = null;
			if (items.some(({ entityId }) => entityId === this.nodeEntityId))
			{
				scopes = this.#getScopes();
			}
			else
			{
				const recentScope = this.#getScopes().find(({ id }) => id === ScopesIds.RECENT);

				scopes = [recentScope];
			}

			this.getSelector().getWidget()?.setScopes(scopes);
		};

		#onDepartmentNodeChanged = (node) => {
			this.setTitle(node?.title);
		};

		#onScopeChanged = ({ text, scope, shouldResetSearch = true }) => {
			this.scopeId = scope.id;

			const navigator = this.getNavigator();

			this.setLeftButtons();

			navigator.setCurrentNodeById(
				navigator.getRootNode(),
			);

			if (shouldResetSearch)
			{
				this.resetSearch();
			}

			this.#setItemsByScope({ text, scope });
		};

		#setItemsByScope = ({ text, scope }) => {
			let title = '';

			switch (scope.id)
			{
				case ScopesIds.RECENT:
					title = this.recentTitle;
					break;

				case ScopesIds.DEPARTMENT:
					title = this.getNavigator().getCurrentNode().title;
					break;

				default:
			}

			this.setTitle(title);

			this.#getItemsByScope()
				.then((items) => {
					this.getSelector().setItems(items);
				})
				.catch(console.error);
		};

		#onSearch = ({ text }) => {
			this.setTitle(
				Loc.getMessage('NESTED_DEPARTMENT_SELECTOR_SEARCH_TITLE'),
			);

			this.getSelector().getProvider().doSearch(text);
		};

		#onSearchCancelled = ({ scope }) => {
			this.resetSearch();

			const title = ScopesIds.RECENT ? this.recentTitle : this.getNavigator().getCurrentNode().title;

			this.setTitle(title);

			if (this.getCurrentScopeId() !== scope.id)
			{
				this.#onScopeChanged({ scope });

				return;
			}

			this.#setItemsByScope({ scope });
		};

		getServiceElements()
		{
			const {
				allowFlatDepartments,
				allowSelectRootDepartment,
			} = this.getSelector().getProvider().getOptions();

			const currentNode = this.getNavigator().getCurrentNode();
			const shouldRenderDepartmentItem = allowSelectRootDepartment
				|| this.getNavigator().getRootNode() !== currentNode;

			const commonParams = {
				type: 'selectable',
				// new styles' set up
				styles: {
					image: {
						image: {
							tintColor: Color.base1.toHex(),
							contentHeight: 26,
							borderRadiusPx: 6,
						},
						border: {
							color: Color.accentExtraAqua.toHex(),
							width: 2,
						},
					},
				},
				// old styles' set up
				typeIconFrame: 2,
				selectedTypeIconFrame: 1,
			};

			return [
				this.getCurrentScopeId() === ScopesIds.DEPARTMENT && shouldRenderDepartmentItem && {
					...currentNode,
					title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ALL_USERS_AND_SUBDEPARTMENTS'),
					shortTitle: currentNode.title,
					...commonParams,
				},
				this.getCurrentScopeId() === ScopesIds.DEPARTMENT && allowFlatDepartments && {
					...currentNode,
					id: `${currentNode.id}:F`,
					title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ONLY_DEPARTMENT_USERS'),
					shortTitle: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ONLY_DEPARTMENT_USERS_SHORT', {
						'#COMPANY_NAME#': currentNode.title,
					}),
					...commonParams,
				},
			].filter(Boolean);
		}

		async #getItemsByScope()
		{
			const navigator = this.getNavigator();
			const provider = this.getSelector().getProvider();

			switch (this.getCurrentScopeId())
			{
				case ScopesIds.DEPARTMENT:
					const children = await this.getOrLoadNodeChildren(
						navigator.getCurrentNode(),
					);

					return this.getPreparedItems(children);
				case ScopesIds.RECENT:

					return provider.prepareItems(provider.getRecentItems());
				default:
					return [];
			}
		}
	}

	module.exports = { NestedDepartmentSelector, findInTree: Navigator.findInTree };
});
