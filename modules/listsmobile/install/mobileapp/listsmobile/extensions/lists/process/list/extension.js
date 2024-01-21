/**
 * @module lists/process/list
 */

jn.define('lists/process/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ProcessItemsFactory, ListItemType } = require('lists/process/simple-list/items');
	const { ProcessCatalog } = require('lists/process/catalog');
	const { EntityDetail, EntityDetailTabs } = require('lists/entity-detail');

	class ProcessList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state.process = [];
		}

		get actions()
		{
			return {}; // todo
		}

		get actionParams()
		{
			return {}; // todo
		}

		get itemType()
		{
			return ListItemType.PERSONAL_PROCESS; // todo: ListItemType.PROCESS
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		render()
		{
			return new StatefulList({

				layout: this.layout,

				getEmptyListComponent: this.renderEmptyListComponent.bind(this),

				actions: this.actions,
				actionParams: this.actionParams,

				floatingButtonClickHandler: this.onFloatingButtonClick.bind(this),

				itemType: this.itemType,
				itemFactory: ProcessItemsFactory,
				itemDetailOpenHandler: this.handleTaskDetailOpen.bind(this),

				cacheName: `lists.process.list.${env.userId}`,
			});
		}

		renderEmptyListComponent()
		{
			return new EmptyScreen({
				title: Loc.getMessage('LISTSMOBILE_PROCESS_LIST_EMPTY_TITLE'),
			});
		}

		onFloatingButtonClick()
		{
			ProcessCatalog.openListWidget(this.layout);
		}

		handleTaskDetailOpen(id, item)
		{
			EntityDetail.open(this.layout, {
				// eslint-disable-next-line no-undef
				uid: Random.getString(),
				iBlockId: item.process.iblockId || 0,
				iBlockTypeId: item.process.iblockTypeId || '',
				entityId: id || 0,
				iBlockSectionId: item.process.iblockSectionId || 0,
				socNetGroupId: 0,
				activeTabId: EntityDetailTabs.DETAIL_TAB,
			});
		}
	}

	module.exports = { ProcessList };
});
