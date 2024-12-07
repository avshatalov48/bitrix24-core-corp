/**
 * @module selector/widget/entity/nested-department-selector
 */
jn.define('selector/widget/entity/nested-department-selector', (require, exports, module) => {
	const { NestedDepartmentSelectorEntity } = require('selector/widget/entity/nested-department-selector-entity');
	const { ScopesIds } = require('selector/providers/nested-department-provider');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Navigator } = require('selector/widget/entity/nested-department-selector/navigator');

	/**
	 * @class NestedDepartmentSelector
	 */
	class NestedDepartmentSelector
	{
		constructor(options = {})
		{
			const provider = Type.isPlainObject(options.provider) ? options.provider : {};
			const providerOptions = Type.isPlainObject(options.provider?.options) ? options.provider?.options : {};
			this.leftButtons = Type.isArrayFilled(options?.leftButtons) ? options?.leftButtons : [];

			this.entity = NestedDepartmentSelectorEntity.make({
				...options,
				searchOptions: {
					...options.searchOptions,
					onSearchCancelled: this.#onSearchCancelled,
				},
				provider: {
					...provider,
					options: {
						...providerOptions,
						onItemsLoadedFromServer: this.#onItemsLoadedFromServer,
						onRecentLoaded: this.#onRecentLoaded,
						getScope: this.getCurrentScope,
						findInTree: Navigator.findInTree,
					},
				},
			});

			this.navigator = new Navigator({
				onCurrentNodeChanged: this.#onDepartmentNodeChanged,
			});

			this.entity.addEvents({
				onScopeChanged: this.#onScopeChanged,
				onItemSelected: this.#onItemSelected,
			});

			this.recentTitle = options.widgetParams.title;
		}

		getNavigator()
		{
			return this.navigator;
		}

		/**
		 * @public
		 * @returns {EntitySelectorWidget}
		 */
		getSelector()
		{
			return this.entity;
		}

		getCurrentScope = () => {
			return this.scope || {};
		};

		#getScopes()
		{
			return [
				{ id: ScopesIds.RECENT, title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_RECENT') },
				{ id: ScopesIds.DEPARTMENT, title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_DEPARTMENTS') },
			];
		}

		#setTitle(title, useProgress = false)
		{
			this.getSelector().getWidget()?.setTitle({
				text: title,
				useProgress,
				type: 'wizard',
			});
		}

		#setLeftButtons = (useDefault = true) => {
			const defaultButtons = [
				{
					type: 'back',
					callback: this.#onBackButtonClicked,
				},
			];

			this.getSelector().getWidget().setLeftButtons(
				useDefault ? defaultButtons : this.leftButtons,
			);
		};

		#onRecentLoaded = (items) => {
			this.#setTitle(
				this.recentTitle ?? NestedDepartmentSelectorEntity.getTitle(),
			);

			const rootDepartment = items.find(({ entityId }) => entityId === 'department');

			this.getNavigator().init(rootDepartment);
		};

		#onItemsLoadedFromServer = (items) => {
			let scopes = null;
			if (items.some(({ entityId }) => entityId === 'department'))
			{
				scopes = this.#getScopes();
			}
			else
			{
				scopes = [
					this.#getScopes().find(({ id }) => id === ScopesIds.RECENT),
				].filter(Boolean);
			}

			this.getSelector().getWidget()?.setScopes(scopes);
		};

		#onDepartmentNodeChanged = (node) => {
			this.#setTitle(node?.title);
		};

		#onBackButtonClicked = () => {
			const navigator = this.getNavigator();
			if (!navigator)
			{
				return;
			}

			navigator.moveToParentNode();

			if (navigator.getCurrentNode().id === navigator.getRootNode().id)
			{
				this.#setLeftButtons(false);
			}

			this.#setPickerAnimation('rightSlide');

			this.getSelector().setItems(
				this.getPreparedItems(
					navigator.getCurrentNodeChildren(),
				),
			);
		};

		#onScopeChanged = ({ text, scope }) => {
			this.scope = scope;

			const navigator = this.getNavigator();

			this.#setLeftButtons(false);

			navigator.setCurrentNodeById(
				navigator.getRootNode(),
			);

			this.#setItemsByScope({ text, scope });
		};

		#setItemsByScope = ({ text, scope }) => {
			let title = '';

			switch (scope.id)
			{
				case ScopesIds.RECENT:
					title = this.recentTitle ?? NestedDepartmentSelectorEntity.getTitle();
					break;

				case ScopesIds.DEPARTMENT:
					title = this.getNavigator().getCurrentNode().title;
					break;

				default:
			}

			this.#setTitle(title);

			this.#getItemsByScope()
				.then((items) => {
					this.#setPickerAnimation('none');

					this.getSelector().setItems(items);
				})
				.catch(console.error);
		};

		#onSearchCancelled = ({ scope }) => {
			if (this.getCurrentScope().id !== scope.id)
			{
				this.#onScopeChanged({ scope });

				return;
			}

			this.#setItemsByScope({ scope });
		};

		setNodeById({ id, entityId })
		{
			const navigator = this.getNavigator();
			if (!navigator)
			{
				return {};
			}

			navigator.setCurrentNodeById({
				id,
				entityId,
			});

			return navigator.getCurrentNode();
		}

		#onItemSelected = ({ item, text, scope }) => {
			const { sourceEntity } = item.params.customData;

			if (sourceEntity.entityId !== 'department')
			{
				return;
			}

			void this.#drawList(sourceEntity);
		};

		#drawList = async (entity) => {
			const newDepartment = this.setNodeById(entity);

			this.#setLeftButtons();

			this.#setTitle(newDepartment.title, true);
			const children = await this.#getOrLoadNodeChildren(newDepartment);
			this.#setTitle(newDepartment.title);

			this.#setPickerAnimation('leftSlide');

			this.getSelector().setItems(
				this.getPreparedItems(children),
			);
		};

		#getServiceElements()
		{
			const {
				allowFlatDepartments,
				allowSelectRootDepartment,
			} = this.getSelector().getProvider().getOptions();

			const parentDepartment = this.getNavigator().getCurrentNode();
			const shouldRenderDepartmentItem = allowSelectRootDepartment
				|| this.getNavigator().getRootNode() !== this.getNavigator().getCurrentNode();

			return [
				this.scope.id === ScopesIds.DEPARTMENT && shouldRenderDepartmentItem && {
					...parentDepartment,
					title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ALL_USERS_AND_SUBDEPARTMENTS'),
					shortTitle: parentDepartment.title,
					type: 'selectable',
					typeIconFrame: 2,
					selectedTypeIconFrame: 1,
				},
				this.scope.id === ScopesIds.DEPARTMENT && allowFlatDepartments && {
					...parentDepartment,
					id: `${parentDepartment.id}:F`,
					title: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ONLY_DEPARTMENT_USERS'),
					shortTitle: Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_ONLY_DEPARTMENT_USERS_SHORT', {
						'#COMPANY_NAME#': parentDepartment.title,
					}),
					type: 'selectable',
					typeIconFrame: 2,
					selectedTypeIconFrame: 1,
				},
			].filter(Boolean);
		}

		getPreparedItems(items)
		{
			return this.getSelector().getProvider().prepareItems([
				...this.#getServiceElements(),
				...items,
			]);
		}

		async #getItemsByScope()
		{
			const navigator = this.getNavigator();
			const provider = this.getSelector().getProvider();

			switch (this.scope.id)
			{
				case ScopesIds.DEPARTMENT:
					const children = await this.#getOrLoadNodeChildren(
						navigator.getCurrentNode(),
					);

					return this.getPreparedItems(children);
				case ScopesIds.RECENT:

					return provider.prepareItems(provider.getRecentItems());
				default:
					return [];
			}
		}

		async #getOrLoadNodeChildren(node)
		{
			const navigator = this.getNavigator();
			const provider = this.getSelector().getProvider();

			let children = navigator.getChildren(node);
			if (!children)
			{
				children = await provider.loadDepartmentChildren(node);

				this.getNavigator().setChildren({
					id: node.id,
					entityId: node.entityId,
					children,
				});
			}

			return children;
		}

		#setPickerAnimation(animationName)
		{
			if (Application.getApiVersion() > 55)
			{
				// this.getSelector().getWidget().setPickerAnimation(animationName);
			}
		}
	}

	module.exports = { NestedDepartmentSelector, findInTree: Navigator.findInTree };
});
