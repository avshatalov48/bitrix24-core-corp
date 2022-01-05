export class Filter
{
	constructor(options)
	{
		this.filterId = options.filterId;
		this.scrumManager = options.scrumManager;
		this.requestSender = options.requestSender;

		this.initUiFilterManager();
		this.bindHandlers();
	}

	initUiFilterManager()
	{
		/* eslint-disable */
		this.filterManager = BX.Main.filterManager.getById(this.filterId);
		/* eslint-enable */
	}

	bindHandlers()
	{
		/* eslint-disable */
		BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
		/* eslint-enable */
	}

	onApplyFilter(filterId, values, filterInstance, promise, params)
	{
		if (this.filterId !== filterId)
		{
			return;
		}

		this.scrumManager.fadeOutAll();

		params.autoResolve = false;

		this.requestSender.applyFilter()
			.then(response => {
				const filteredItemsData = response.data;
				this.scrumManager.getAllItems().forEach((item) => {
					this.scrumManager.removeItemFromEntities(item);
				});
				filteredItemsData.forEach((itemData) => {
					this.scrumManager.appendNewItemToEntity(this.scrumManager.createTaskItemByItemData(itemData));
				});
				promise.fulfill();
				this.scrumManager.fadeInAll();
			}).catch((response) => {
				promise.reject();
			this.scrumManager.fadeInAll();
			});
	}
}