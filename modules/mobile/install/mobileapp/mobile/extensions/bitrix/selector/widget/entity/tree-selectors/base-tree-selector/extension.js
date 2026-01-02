/**
 * @module selector/widget/entity/tree-selectors/base-tree-selector
 */
jn.define('selector/widget/entity/tree-selectors/base-tree-selector', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { Loc } = require('loc');
	const { Navigator } = require('selector/widget/entity/tree-selectors/shared/navigator');

	const COMMON_SECTION_CODE = 'common';

	/**
	 * @class BaseTreeSelector
	 */
	class BaseTreeSelector
	{
		/**
		 * @typedef {Object} WidgetParams
		 * @property {Object} backdrop - Backdrop settings.
		 * @property {number} backdrop.mediumPositionPercent - Medium position percentage for the backdrop.
		 * @property {boolean} backdrop.horizontalSwipeAllowed - Whether horizontal swipe is allowed.
		 * @property {string} sendButtonName - Name of the send button.
		 * @property {string} title - Title of the widget.
		 */
		constructor(options = {})
		{
			this.options = this.prepareOptions(options);

			this.shouldAnimate = this.options.shouldAnimate ?? false;

			this.onItemSelected = this.onItemSelected.bind(this);

			this.createNavigator();

			this.entity = this.makeEntity(this.getEntity(), options);
		}

		makeEntity(entityClass, options = {})
		{
			return entityClass.make({
				...this.options,
				searchOptions: this.prepareSearchOptions(options),
				provider: this.prepareProvider(options),
				widgetParams: this.prepareWidgetParams(options),
			});
		}

		/**
		 * @abstract
		 */
		getEntity()
		{
			throw new Error('Method must be implemented');
		}

		/**
		 * @abstract
		 */
		getNodeEntityId()
		{
			throw new Error('Method must be implemented');
		}

		prepareOptions(options)
		{
			return options;
		}

		prepareWidgetParams(options)
		{
			return options.widgetParams;
		}

		prepareProvider(options)
		{
			return options.provider;
		}

		prepareSearchOptions(options)
		{
			return options.searchOptions;
		}

		createNavigator(options = {})
		{
			this.navigator = new Navigator({
				onCurrentNodeChanged: this.onNodeChanged,
				...options,
			});
		}

		/**
		 * @public
		 * @param params
		 * @param layout
		 * @returns {Promise}
		 */
		show(params, layout)
		{
			return this.getSelector().show(params, layout);
		}

		/**
		 * @public
		 * @returns {EntitySelectorWidget}
		 */
		getSelector()
		{
			return this.entity;
		}

		getNavigator()
		{
			return this.navigator;
		}

		resetSearch = () => {
			this.getSelector().getProvider().resetQuery();
			this.getSelector().getWidget().setQueryText('');
		};

		getExternalLeftButtons()
		{
			return [];
		}

		setLeftButtons()
		{
			const navigator = this.getNavigator();
			const useInsideNavigationButtons = !isEqual(
				navigator.getCurrentNode(),
				navigator.getRootNode(),
			);

			this.getSelector().getWidget().setLeftButtons(
				useInsideNavigationButtons
					? this.#getNavigationalLeftButtons()
					: this.getExternalLeftButtons(),
			);
		}

		#getNavigationalLeftButtons()
		{
			return [
				{
					type: 'back',
					callback: this.#onBackButtonClick,
				},
			];
		}

		#onBackButtonClick = () => {
			const navigator = this.getNavigator();
			if (!navigator)
			{
				return;
			}

			navigator.moveToParentNode();

			this.setLeftButtons();

			const currentNode = navigator.getCurrentNode();

			const title = currentNode.type === 'user'
				? Loc.getMessage('DIRECTORY_SELECTOR_USER_ROOT_DIRECTORY_TITLE')
				: currentNode.name || currentNode.title;

			this.setTitle(title);

			this.getSelector().setItems(
				this.getPreparedItems(
					navigator.getCurrentNodeChildren(),
				),
				this.shouldAnimate ? 'slideFromLeft' : 'none',
			);
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

		onNodeChanged = ({ title }) => {
			this.setTitle(title);
		};

		setTitle = (title, useProgress = false) => {
			this.getSelector().getWidget()?.setTitle({
				text: title,
				useProgress,
				type: 'wizard',
			});
		};

		onItemSelected({ item, text, scope })
		{
			const { sourceEntity } = item.params.customData;

			if (sourceEntity.entityId !== this.getNodeEntityId())
			{
				return;
			}

			void this.#drawList(sourceEntity);
		}

		async #drawList(entity)
		{
			const newNode = this.setNodeById(entity);

			this.setLeftButtons();

			let children = await this.getOrLoadNodeChildren(newNode);

			children = this.getPreparedItems(children);

			if (children.length === 0)
			{
				children = [this.#getEmptyItem()];
			}

			this.setTitle(newNode.title || newNode.name);

			this.getSelector().setItems(
				children,
				this.shouldAnimate ? 'slideFromRight' : 'none',
			);
		}

		async getOrLoadNodeChildren(node)
		{
			const navigator = this.getNavigator();
			const provider = this.getSelector().getProvider();

			let children = navigator.getChildren(node);
			if (!children)
			{
				this.getSelector().setItems([
					this.#getLoadingItem(),
				]);

				children = await provider.loadChildren(node);

				this.getNavigator().setChildren({
					id: node.id,
					entityId: node.entityId,
					children,
				});
			}

			return children;
		}

		getPreparedItems(items)
		{
			return this.getSelector().getProvider().prepareItems([
				...this.getServiceElements(),
				...items,
			]);
		}

		getServiceElements()
		{
			return [];
		}

		#getEmptyItem()
		{
			return {
				title: Loc.getMessage('BASE_TREE_SELECTOR_EMPTY_ITEM'),
				type: 'button',
				sectionCode: COMMON_SECTION_CODE,
				unselectable: true,
			};
		}

		#getLoadingItem()
		{
			return {
				id: 'loading',
				title: Loc.getMessage('BASE_TREE_SELECTOR_LOADING_ITEM'),
				type: 'loading',
				unselectable: true,
				sectionCode: COMMON_SECTION_CODE,
			};
		}
	}

	module.exports = { BaseTreeSelector };
});
