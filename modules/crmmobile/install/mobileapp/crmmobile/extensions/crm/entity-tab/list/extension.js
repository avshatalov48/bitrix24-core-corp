/**
 * @module crm/entity-tab/list
 */
jn.define('crm/entity-tab/list', (require, exports, module) => {
	const { EntityTab, TypePull } = require('crm/entity-tab');
	const { Filter } = require('layout/ui/kanban/filter');
	const { ListItemType, ListItemsFactory } = require('crm/simple-list/items');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { Type } = require('type');

	/**
	 * @class ListTab
	 */
	class ListTab extends EntityTab
	{
		getView()
		{
			return 'list';
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
				actionCallbacks: this.props.actionCallbacks,
				itemLayoutOptions: this.getItemLayoutOptions(),
				itemActions: this.getItemActions(),
				popupItemMenu: true,
				itemParams: {
					isClientEnabled: this.isClientEnabled(),
					...this.props.itemParams,
				},
				forcedShowSkeleton: false,
				getItemCustomStyles: this.getItemCustomStyles,
				emptyListText: BX.message('M_CRM_LIST_EMPTY_LIST_TEXT'),
				emptySearchText: BX.message('M_CRM_LIST_EMPTY_SEARCH_TEXT'),
				layout: this.props.layout,
				layoutOptions: this.getLayoutOptions(),
				menuButtons: this.getMenuButtons(),
				cacheName: this.getCacheName(),
				layoutMenuActions: this.getMenuActions(),
				itemCounterLongClickHandler: this.getCounterLongClickHandler(),
				onNotViewableHandler: this.onNotViewable,
				onPanListHandler: this.props.onPanList || null,
				isShowFloatingButton: this.isShowFloatingButton(),
				getEmptyListComponent: this.getEmptyListComponent,
				itemType: ListItemType.CRM_ENTITY,
				itemFactory: ListItemsFactory,
				pull: this.getPullConfig(),
				onFloatingButtonClick: this.onFloatingButtonClickHandler,
				onFloatingButtonLongClick: this.onFloatingButtonLongClickHandler,
				itemDetailOpenHandler: this.itemDetailOpenHandler,
				onDetailCardUpdateHandler: this.onDetailCardUpdateHandler,
				onDetailCardCreateHandler: this.onDetailCardCreateHandler,
				ref: this.bindRef,
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

			const { actions, entityTypeName } = this.props;

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(actions.deleteItem, {
					data: {
						id: itemId,
						entityType: entityTypeName,
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

		getCurrentStatefulList()
		{
			return this.viewComponent;
		}

		isCurrentStage(stageCode)
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

		hasColumnChangesInItem(item, oldItem)
		{
			return false;
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
			if (this.changeCategoryIfViewNotFound())
			{
				return;
			}

			if (params.clearFilter)
			{
				this.filter = new Filter(this.getDefaultPresetId());
				this.state.searchButtonBackgroundColor = null;
			}

			const initMenu = BX.prop.getBoolean(params, 'initMenu', false);

			this.setState({
				forceRenderSwitcher: !this.state.forceRenderSwitcher,
			}, () => {
				let useCache = false;

				// Note: disabled cache for now, because of the bug with the filter cancellation
				if (!params.filterCancelled)
				{
					const selectedNotDefaultPreset = this.filter.hasSelectedNotDefaultPreset();
					useCache = !selectedNotDefaultPreset && !Type.isStringFilled(this.filter.getSearchString());
				}

				this.getViewComponent().reload(
					{
						forcedShowSkeleton: BX.prop.getBoolean(params, 'forcedShowSkeleton', !useCache),
					},
					{ useCache },
					() => initMenu && this.getViewComponent().initMenu(),
				);
			});
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

		isAllStagesDisplayed()
		{
			return true;
		}
	}

	module.exports = { ListTab };
});
