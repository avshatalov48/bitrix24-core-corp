(() =>
{
	class KnowledgeList extends BaseList
	{
		/**
		 * Component id.
		 * @return {string}
		 */
		static id()
		{
			return 'knowledge_list';
		}

		/**
		 * REST method.
		 * @return {string}
		 */
		static method()
		{
			return 'landing.site.getList';
		}

		/**
		 * REST params.
		 * @return {object}
		 */
		params()
		{
			return {
				params: {
					select: [
						'ID', 'TITLE', 'PUBLIC_URL', 'PREVIEW_PICTURE'
					],
					filter: {
						'=TYPE': 'KNOWLEDGE'
					},
					order: {
						ID: 'desc'
					}
				},
				initiator: 'mobile',
				scope: 'knowledge'
			};
		}

		/**
		 * Draw item from REST to List.
		 * @param site
		 * @return {object}
		 */
		static prepareItemForDrawing(site)
		{
			if (site.params && site.params.code === 'skip_handle')
			{
				return site;
			}

			return {
				title: site['TITLE'],
				sectionCode: this.id(),
				color: '#5D5C67',
				useLetterImage: true,
				imageUrl: site['PREVIEW_PICTURE'],
				id: site.ID,
				sortValues: {
					name: site['TITLE']
				},
				params: {
					id: site['ID'],
					title: site['TITLE'],
					url: site['PUBLIC_URL']
				},
				styles: {
					image: {
						image: {borderRadius: 0}
					}
				}
			}

		}
	}

	/**
	 * Init app.
	 */
	let knowledge = new KnowledgeList(list)
		.setHandlers(
			{
				prepareItems: function(items)
				{
					if (items !== 'undefined')
					{
						if (items.length <= 0)
						{
							return [
								{
									type: 'button',
									title: BX.message('KNOWLEDGE_NO_RECORDS'),
									sectionCode: 'knowledge_list',
									unselectable: true
								}
							];
						}
						else
						{
							return items.map(item => KnowledgeList.prepareItemForDrawing(item))
						}
					}
					else
					{
						return items;
					}
				},
				onUserTypeText: function (data)
				{
					//start search when user type text
					this.searcher.fetchResults(data)
				},
				onSearchShow: function ()
				{
					//show recent result on show
					this.searcher.showRecentResults();
				},
				onSearchItemSelected: function (data)
				{
					if (data.params.code)
					{
						if (data.params.code === 'skip_handle')
						{
							return;
						}

						if (data.params.code === 'more_search_result')
						{
							this.searcher.fetchNextResults(data.params.query);
							return;
						}
					}

					//add to recent search result
					this.searcher.addRecentSearchItem(data);
					PageManager.openPage({
						url: data.params.url,
						title: data.params.title,
						cache: false
					});

				},
				onItemSelected: function(data)
				{
					PageManager.openPage({
						url: data.params.url,
						title: data.params.title,
						cache: false
					});
				},
				onRefresh: function()
				{
					this.reload()
				}
			})
		.setSearchDelegate(
			new (
				class extends BaseListSearchDelegate {

					onSearchRequestStart(items, sections)
					{
						this.list.setSearchResultItems(items, sections);
					}

					onSearchResultEmpty(items, sections)
					{
						this.list.setSearchResultItems(items, sections);
					}

					onSearchResultReady(items, sections)
					{
						this.list.setSearchResultItems(items, sections);
					}

					onSearchResultReadyNext(items, sections)
					{
						this.list.setSearchResultItems(items, sections);
					}

					onSearchResultRecent(items, sections)
					{

						this.list.setSearchResultItems(items, sections);
					}

					getSearchMethod()
					{
						return 'landing.site.getList';
					}

					prepareItems(items)
					{
						if (items !== 'undefined')
						{
							return items.map(item => KnowledgeList.prepareItemForDrawing(item))
						}
						else
						{
							return items;
						}

					}

					getSearchQueryOption(query)
					{
						return {
							params: {
								select: [
									'ID', 'TITLE', 'PUBLIC_URL', 'PREVIEW_PICTURE'
								],
								filter: {
									'=TYPE': 'KNOWLEDGE',
									'TITLE': '%' + query + '%'
								},
								order: {
									ID: 'desc'
								}
							},
							initiator: 'mobile',
							scope: 'knowledge'
						};
					}
				})(list)
		)
	;
	knowledge.init();

})();