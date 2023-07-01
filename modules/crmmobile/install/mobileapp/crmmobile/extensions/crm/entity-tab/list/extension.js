/**
 * @module crm/entity-tab/list
 */
jn.define('crm/entity-tab/list', (require, exports, module) => {
	const { EntityTab } = require('crm/entity-tab');
	const { Filter } = require('crm/entity-tab/filter');
	const { TypePull } = require('crm/entity-tab/pull-manager');
	const { StatefulList } = require('layout/ui/stateful-list');

	/**
	 * @class ListTab
	 */
	class ListTab extends EntityTab
	{
		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			this.entityTypeName = props.entityTypeName;
		}

		render()
		{
			return View(
				this.getViewConfig(),
				this.renderStatefulList(),
			);
		}

		renderStatefulList()
		{
			const testId = `LIST_${this.props.entityTypeName.toUpperCase()}`;

			return new StatefulList({
				testId,
				actions: this.props.actions || {},
				actionParams: this.prepareActionParams(),
				itemLayoutOptions: this.getItemLayoutOptions(),
				itemActions: this.getItemActions(),
				itemParams: this.props.itemParams,
				getItemCustomStyles: this.getItemCustomStyles,
				emptyListText: BX.message('M_CRM_LIST_EMPTY_LIST_TEXT'),
				emptySearchText: BX.message('M_CRM_LIST_EMPTY_SEARCH_TEXT'),
				layout: this.props.layout,
				layoutOptions: this.getLayoutOptions(),
				menuButtons: this.getMenuButtons(),
				cacheName: this.props.cacheName,
				layoutMenuActions: this.getMenuActions(),
				itemDetailOpenHandler: this.handleItemDetailOpen.bind(this),
				itemCounterLongClickHandler: this.getCounterLongClickHandler(),
				onDetailCardUpdateHandler: this.onDetailCardUpdate.bind(this),
				onDetailCardCreateHandler: this.onDetailCardCreate.bind(this),
				onPanListHandler: this.props.onPanList || null,
				isShowFloatingButton: this.isShowFloatingButton(),
				floatingButtonClickHandler: this.handleFloatingButtonClick.bind(this),
				floatingButtonLongClickHandler: this.handleFloatingButtonLongClick.bind(this),
				getEmptyListComponent: this.getEmptyListComponent.bind(this),
				itemType: 'Kanban',
				pull: this.getPullConfig(),
				ref: (ref) => this.viewComponent = ref,
				analyticsLabel: {
					module: 'crm',
					source: 'crm-entity-tab',
					entityTypeId: this.props.entityTypeId,
				},
			});
		}

		getItemCustomStyles(item, section, row)
		{
			if (row !== 0)
			{
				return {};
			}

			return {
				wrapper: {
					paddingTop: 12,
				},
			};
		}

		deleteItem(itemId)
		{
			const params = {
				eventId: this.pullManager.registerRandomEventId(),
			};

			const { actions } = this.props;

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(actions.deleteItem, {
					data: {
						id: itemId,
						entityType: this.props.entityTypeName,
						params,
					},
				}).then((response) => {
					if (response.errors.length > 0)
					{
						reject({
							errors: response.errors,
							showErrors: true,
						});
					}

					resolve({
						action: 'delete',
						id: itemId,
					});
				}).catch((response) => {
					console.error(response.errors);
					reject({
						errors: response.errors,
						showErrors: true,
					});
				});
			});
		}

		/**
		 * @param {string} prefix
		 * @returns {string}
		 */
		getPullCommand(prefix)
		{
			const { entityTypeName } = this.props;
			return `${prefix}_${entityTypeName}_0`; // @todo need check this.getCurrentEntityType().isCategoriesSupported after support a smart-processes
		}

		getCurrentStatefulList()
		{
			return this.viewComponent;
		}

		isCurrentSlideName(slideName)
		{
			return true;
		}

		isNeedProcessPull(data, context)
		{
			const { command, params } = data;

			if (this.pullManager.hasEvent(params.eventId))
			{
				return false;
			}

			return command === this.getPullCommand(TypePull.Command);
		}

		onDetailCardUpdate(params)
		{
			if (this.props.entityTypeId === params.entityTypeId)
			{
				this.getCurrentStatefulList().updateItems([params.entityId]);
			}
		}

		reload(params = {})
		{
			if (params.clearFilter)
			{
				this.filter = new Filter(this.getDefaultPresetId());
				this.state.searchButtonBackgroundColor = null;
			}

			const initMenu = BX.prop.getBoolean(params, 'initMenu', false);

			this.setState({
				forceRenderSwitcher: !this.state.forceRenderSwitcher,
			}, () => {
				const canUseCache = !(Boolean(this.filter.currentFilterId) || Boolean(this.filter.search));
				this.getViewComponent().reload(
					{
						forcedShowSkeleton: BX.prop.getBoolean(params, 'forcedShowSkeleton', false),
					},
					{
						useCache: canUseCache,
					},
					() => initMenu && this.getViewComponent().initMenu(),
				);
			});
		}

		prepareActionParams()
		{
			const actionParams = super.prepareActionParams();
			actionParams.loadItems.extra = (actionParams.loadItems.extra || {});

			const entityType = this.getCurrentEntityType();
			const { presetId } = entityType.data;

			this.filter.prepareActionParams(actionParams, presetId);

			return actionParams;
		}

		scrollToTop()
		{
			const simpleList = this.getViewComponent().getSimpleList();
			this.scrollSimpleListToTop(simpleList);
		}

		getMenuActions()
		{
			const menuActions = [];

			const entityType = this.getCurrentEntityType();
			if (entityType && entityType.isLastActivityEnabled)
			{
				menuActions.push(this.itemsSortManager.getSortMenuAction(this.onSetSortTypeHandler));
			}

			const parentMenu = super.getMenuActions();
			if (menuActions.length > 0)
			{
				parentMenu[0].showTopSeparator = true;
			}

			return [...menuActions, ...parentMenu];
		}
	}

	module.exports = { ListTab };
});
