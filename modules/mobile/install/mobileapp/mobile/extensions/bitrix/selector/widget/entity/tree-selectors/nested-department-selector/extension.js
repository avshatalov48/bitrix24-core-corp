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

	const SCOPES = {
		[ScopesIds.RECENT]: {
			id: ScopesIds.RECENT,
			title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_RECENT'),
		},
		[ScopesIds.DEPARTMENT]: {
			id: ScopesIds.DEPARTMENT,
			title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_DEPARTMENTS'),
		},
		[ScopesIds.PROJECT]: {
			id: ScopesIds.PROJECT,
			title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_GROUPS'),
		},
	};

	/**
	 * @class NestedDepartmentSelector
	 */
	class NestedDepartmentSelector extends BaseTreeSelector
	{
		/**
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
			super(options);

			this.createNavigator();

			this.entity.addEvents({
				onScopeChanged: this.#onScopeChanged,
				onItemSelected: this.onItemSelected,
			});

			this.recentTitle = this.options.widgetParams.title ?? NestedDepartmentSelectorEntity.getTitle();
			this.tabConfig = this.createTabConfig(options.customTabsConfig);
		}

		hasDepartmentScope()
		{
			return Object.prototype.hasOwnProperty.call(this.tabConfig, ScopesIds.DEPARTMENT);
		}

		getEntity()
		{
			return NestedDepartmentSelectorEntity;
		}

		getNodeEntityId()
		{
			return 'department';
		}

		prepareOptions(options)
		{
			return {
				...options,
				shouldAnimate: true,
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
					enabledTabs: this.createTabConfig(options.customTabsConfig),
					onItemsLoadedFromServer: (items) => this.#onItemsLoadedFromServer(items),
					onInitialItemsLoad: (items) => this.#onInitialItemsLoad(items),
					getScopeId: () => this.getCurrentScopeId(),
					findInTree: Navigator.findInTree,
				},
			};
		}

		prepareSearchOptions(options)
		{
			return {
				...options.searchOptions,
				onSearchCancelled: (data) => this.#onSearchCancelled(data),
				onSearch: (data) => this.#onSearch(data),
			};
		}

		getExternalLeftButtons()
		{
			return Type.isArrayFilled(this.options?.leftButtons) ? this.options?.leftButtons : [];
		}

		getCurrentScopeId = () => {
			return this.scopeId || {};
		};

		createTabConfig(customTabsConfig = null)
		{
			const defaultTabs = {
				[ScopesIds.RECENT]: SCOPES[ScopesIds.RECENT],
			};

			if (!customTabsConfig || !Type.isArrayFilled(customTabsConfig))
			{
				defaultTabs[ScopesIds.DEPARTMENT] = SCOPES[ScopesIds.DEPARTMENT];

				return defaultTabs;
			}

			customTabsConfig.forEach((id) => {
				if (SCOPES[id] && id !== ScopesIds.RECENT)
				{
					defaultTabs[id] = SCOPES[id];
				}
			});

			return defaultTabs;
		}

		#getScopes()
		{
			return Object.entries(this.tabConfig)
				.map(([id, config]) => ({ id, title: config.title }))
			;
		}

		#onInitialItemsLoad = (items) => {
			this.setTitle(
				this.recentTitle,
			);

			if (this.hasDepartmentScope())
			{
				const rootDepartment = items.find(({ entityId }) => entityId === this.getNodeEntityId());

				this.getNavigator().init(rootDepartment);
			}
		};

		#onItemsLoadedFromServer = (items) => {
			let scopes = null;
			if (items.some(({ entityId }) => entityId === this.getNodeEntityId() || this.options.customTabsConfig))
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

		#onScopeChanged = ({ text, scope, shouldResetSearch = true }) => {
			this.scopeId = scope.id;

			if (this.hasDepartmentScope())
			{
				const navigator = this.getNavigator();

				navigator.setCurrentNodeById(navigator.getRootNode());
			}

			this.setLeftButtons();

			if (shouldResetSearch)
			{
				this.resetSearch();
			}

			this.#setItemsByScope({ text, scope });
		};

		#setItemsByScope = ({ text, scope }) => {
			const scopeHandlers = {
				[ScopesIds.RECENT]: () => this.recentTitle,
				[ScopesIds.DEPARTMENT]: () => this.getNavigator().getCurrentNode().title,
				[ScopesIds.PROJECT]: () => '',
			};

			const title = scopeHandlers[scope.id] ? scopeHandlers[scope.id]() : '';

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
			const provider = this.getSelector().getProvider();

			const scopeHandlers = {
				[ScopesIds.DEPARTMENT]: async () => {
					const navigator = this.getNavigator();
					const children = await this.getOrLoadNodeChildren(navigator.getCurrentNode());

					return this.getPreparedItems(children);
				},
				[ScopesIds.RECENT]: async () => {
					return provider.prepareItems(provider.getRecentItems());
				},
				[ScopesIds.PROJECT]: async () => {
					const groups = await provider.loadGroups();

					return provider.prepareItems(groups);
				},
			};

			const currentScopeId = this.getCurrentScopeId();
			const handler = scopeHandlers[currentScopeId];

			return handler ? handler() : [];
		}
	}

	module.exports = { NestedDepartmentSelector, findInTree: Navigator.findInTree };
});
