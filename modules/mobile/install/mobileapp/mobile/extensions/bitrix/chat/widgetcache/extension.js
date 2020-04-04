(function()
{
	class ChatWidgetCache
	{
		constructor(userId, languageId)
		{
			this.userId = userId;
			this.languageId = languageId;

			this.recentList = [];
			this.colleaguesList = [];
			this.lastSearchList = [];
			this.businessUsersList = false;

			this.load();
		}

		load()
		{
			this.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId);

			let executeTimeRecent = new Date();
			this.database.table(
				ChatTables.recent
			).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length > 0)
					{
						let cacheData = JSON.parse(items[0].VALUE);
						this.recentList = ChatDataConverter.getListFormat(cacheData.list);
						console.info("ChatWidgetCache.load: recent load from cache ("+(new Date() - executeTimeRecent)+'ms)', "count: "+this.recentList.length);
					}
				})
			});

			let executeTimeLastSearch = new Date();
			this.database.table(ChatTables.lastSearch).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length > 0)
					{
						let cacheData = JSON.parse(items[0].VALUE);

						this.lastSearchList = ChatDataConverter.getListFormat(cacheData.recent);

						console.info("ChatWidgetCache.load: last search load from cache \"\" ("+(new Date() - executeTimeLastSearch)+'ms)', "count: "+this.lastSearchList.length);
					}
				})
			});

			let executeTimeColleaguesList = new Date();
			this.database.table(ChatTables.colleaguesList).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length > 0)
					{
						let cacheData = JSON.parse(items[0].VALUE);

						this.colleaguesList = ChatDataConverter.getUserListFormat(cacheData.colleaguesList);

						console.info("ChatWidgetCache.load: colleagues list load from cache ("+(new Date() - executeTimeColleaguesList)+'ms)', "count: "+this.colleaguesList.length);
					}
				})
			});

			let executeTimeBusinessUsersList = new Date();
			this.database.table(ChatTables.businessUsersList).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length > 0)
					{
						let cacheData = JSON.parse(items[0].VALUE);
						this.businessUsersList = cacheData.businessUsersList !== false? ChatDataConverter.getUserListFormat(cacheData.businessUsersList): false;

						console.info("ChatWidgetCache.load: colleagues list load from cache ("+(new Date() - executeTimeBusinessUsersList)+'ms)', this.businessUsersList !== false? "count: "+this.businessUsersList.length: "not available");
					}
				})
			});

			return true;
		}
	}

	window.ChatWidgetCache = ChatWidgetCache;
})();



