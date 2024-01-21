/**
 * @module lists/process/catalog
 */
jn.define('lists/process/catalog', (require, exports, module) => {
	const { Loc } = require('loc');
	const { StorageCache } = require('storage-cache');
	const { EntityDetail, EntityDetailTabs } = require('lists/entity-detail');

	class ProcessCatalog
	{
		static openListWidget(parentWidget = PageManager)
		{
			parentWidget.openWidget(
				'list',
				{
					backdrop: {
						bounceEnable: true,
						swipeAllowed: true,
						mediumPositionPercent: 80,
						onlyMediumPosition: true,
						forceDismissOnSwipeDown: true,
						shouldResizeContent: true,
					},
					// useSearch: true,
					// useLargeTitleMode: true,
					groupStyle: true,
					title: Loc.getMessage('LISTSMOBILE_PROCESS_CATALOG_TITLE'),
					onReady: (list) => {
						new ProcessCatalog(list);
					},
				},
				parentWidget,
			);
		}

		static prepareListItem(item)
		{
			return {
				id: item.id,
				title: item.name,
				imageUrl: item.pictureSrc || '/bitrix/images/lists/default.png',
				params: {
					item,
				},
			};
		}

		constructor(list)
		{
			this.list = list;
			this.cache = new StorageCache('lists.process.catalog', 'catalog');

			BX.onViewLoaded(() => {
				this.setListListeners();
				this.loadFromCache();
				this.reload();
			});
		}

		get layout()
		{
			return this.list || layout || {};
		}

		setListListeners()
		{
			const eventHandlers = {
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		onItemSelected(item)
		{
			EntityDetail.open(this.layout, {
				// eslint-disable-next-line no-undef
				uid: Random.getString(),
				iBlockId: item.id || 0,
				iBlockTypeId: item.params.item.iBlockTypeId || '',
				entityId: 0,
				iBlockSectionId: 0,
				socNetGroupId: 0,
				activeTabId: EntityDetailTabs.DETAIL_TAB,
			});
		}

		loadFromCache()
		{
			BX.onViewLoaded(() => {
				const catalog = this.cache.get();
				if (Object.keys(catalog).length === 0)
				{
					return;
				}

				this.list.setItems(Object.values(catalog));
			});
		}

		reload()
		{
			BX.ajax.runAction('listsmobile.Process.loadCatalog', {})
				.then((response) => {
					const processes = response.data.items.map((item) => ProcessCatalog.prepareListItem(item));

					this.fillCache(processes);
					this.list.setItems(processes);
				})
				.catch((response) => {})
			;
		}

		fillCache(processes)
		{
			this.cache.set(processes);
		}
	}

	module.exports = { ProcessCatalog };
});
