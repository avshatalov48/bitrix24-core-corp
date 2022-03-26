/**
 * @bxjs_lang_path extension.php
 */

(() =>
{
	class WorkgroupList
	{
		/**
		 * @param params
		 * @param {String} params.siteId site id
		 * @param {String} params.siteDir site dir
		 * @param {String} params.features project tabs list
		 * @param {String} params.mandatoryFeatures project mandatory tabs list
		 * @param {String} params.pathTemplate path template to a group news page
		 * @param {String} params.calendarWebPathTemplate path template to a web group calendar page
		 * @param {BaseList} params.list list object
		 * @param {String} params.title title at the navigation bar
		 * @param {Number} params.currentUserId current user id
		 */
		constructor(params = {})
		{
			this.siteId = params.siteId;
			this.siteDir = params.siteDir;
			this.features = (params.features || '').split(',');
			this.mandatoryFeatures = (params.mandatoryFeatures || '').split(',');
			this.list = params.list || null;

			if (params.title)
			{
				this.title = params.title;
			}
			this.items = [];

			this.request = new RequestExecutor('socialnetwork.api.workgroup.list');
			this.request.handler = this.handler.bind(this);
			this.request.onCacheFetched = this.onCacheFetched.bind(this);

			this.newsPathTemplate = (params.pathTemplate || '');
			this.calendarWebPathTemplate = (params.calendarWebPathTemplate || '');
			this.currentUserId = parseInt(params.currentUserId || 0);

			BX.onViewLoaded(() =>
			{
				this.list.setSections([
					{id: 'list'},
					{id: 'service'}
				]);
				this.showLoading();
				this.load(true);
			});
		}

		/**
		 *
		 * @param {BaseList} list
		 */
		set list(list)
		{
			this._list = list;
			if (this.list)
			{
				if (this.title)
				{
					this.list.setTitle({
						text: this.title,
					});
				}

				let listener = (event, item) =>
				{
					if (event === 'onItemSelected')
					{
						if (item.id === 'more')
						{
							this.showLoading();
							this.request.callNext();
						}
						else
						{
							if (Application.getApiVersion() >= 41)
							{
								const data = {
									siteId: this.siteId,
									siteDir: this.siteDir,
									projectId: item.id,
									action: 'view',
									item: item,
									newsPathTemplate: this.newsPathTemplate,
									calendarWebPathTemplate: this.calendarWebPathTemplate,
								};

								BX.postComponentEvent('projectbackground::project::action', [ data ], 'background');
							}
							else
							{
								PageManager.openPage({
									cache: false,
									url: this.newsPathTemplate.replace('#group_id#', item.id),
								});
							}
						}
					}
					else if (event === 'onRefresh')
					{
						this.refresh();
					}
				};

				this._list.setListener(listener);

			}
		}

		refresh()
		{
			this.items = [];
			this.load(false);
		}

		load(useCache = false)
		{
			setTimeout(() =>
			{
				this.request.options = {
					params: {
						siteId: this.siteId,
						features: this.features,
						mandatoryFeatures: this.mandatoryFeatures,
						mode: 'mobile',
					},
					filter: {
						'ACTIVE': 'Y',
						'!CLOSED': 'Y',
					},
					select: [
						'ID',
						'NAME',
						'OPENED',
						'AVATAR',
						'AVATAR_TYPE',
						'NUMBER_OF_MEMBERS',
					],
					order: {
						'NAME': 'ASC',
						'ID': 'ASC',
					},
				};

				this.request.cacheId = Object.toMD5({
					siteId: this.siteId,
				});
				this.request.call(useCache);
			});
		}

		/**
		 *
		 * @returns {BaseList}
		 */
		get list()
		{
			return this._list;
		}

		onCacheFetched(result = {})
		{
			if (typeof result === 'object')
			{
				let list = result.items;
				if (list && list.length > 0)
				{
					this.items = list.map(item => WorkgroupList.prepareItem(item));
					BX.onViewLoaded(() =>
					{
						if (result['name'])
						{
							this.list.setTitle({
								text: result['name']
							});
						}

						this.setItems(this.items);
					});
				}
			}
		}

		onError(result, error, more)
		{
			console.error(error);
			this.showError();
		}

		onResult(result, more)
		{
			BX.onViewLoaded(
				() =>
				{
					if (result)
					{
						let items = result.workgroups || [];

						if (this.request.hasNext())
						{
							this.showMore();
						}
						else
						{
							this.list.setSectionItems([], 'service');
						}

						const workgroups = items.map(item => WorkgroupList.prepareItem(item));
						const isEmptyList = this.items.length === 0;
						this.items = more ? this.items.concat(workgroups) : workgroups;

						if (result.name)
						{
							this.list.setTitle({
								text: result.name
							});
						}

						if (this.items.length === 0)
						{
							this.showEmptyList();
						}
						else
						{
							if (isEmptyList || this.firstLoad)
							{
								this.firstLoad = false;
								this.setItems(this.items);
							}
							else
							{
								this.list.addItems(workgroups);
							}
						}
					}
				}
			);

		}

		handler(result, more, error)
		{
			this.list.stopRefreshing();
			dialogs.hideLoadingIndicator();
			if (error)
			{
				this.onError(result, more, error);
			}

			if (result && result.workgroups)
			{
				this.onResult(result, more)
			}
		}

		setItems(items = [])
		{
			this.list.setSectionItems(items, 'list');
		}

		showLoading()
		{
			let loading = ListHolder.Loading;
			loading.id = 'loading';
			loading.params = {};
			this.list.setSectionItems([ loading ], 'service');
		}

		showMore()
		{
			let more = ListHolder.MoreButton;
			more.id = 'more';
			more.params = {
				id: 'more'
			};
			this.list.setSectionItems([ more ], 'service');
		}

		showEmptyList()
		{
			this.showMessageButton({
				title: BX.message('MOBILE_PROJECT_LIST_EMPTY')
			});
		}

		showError()
		{
			this.showMessageButton({
				title: BX.message('MOBILE_PROJECT_LIST_ERROR')
			});
		}

		/**
		 * @param params
		 * @param {String} params.title button title
		 */
		showMessageButton(params)
		{
			this.list.setSectionItems([
				{
					title: params.title,
					type: 'button',
					styles: {
						title: {
							font: {
								size: 17,
								fontStyle: 'medium'
							}
						}
					},
					unselectable: true
				}], 'service');
		}

		destroy()
		{
			this._list = null;
		}

		static prepareItem(item)
		{
			const preparedItem = {
				id: item.id,
				title: item.name,
				sectionCode: 'list',
				useLetterImage: true,
				type: 'info',
				clientSort: {},
				styles: {
					title: {
						font: {
							color: '#333333',
							size: 17,
							fontStyle: 'medium',
						}
					}
				},
				actions: [],
				params: {
					avatar: (item.avatar || ''),
					features: (
						typeof item.additionalData !== 'undefined'
						&& typeof item.additionalData.features !== 'undefined'
							? item.additionalData.features
							: []
					),
					role: (
						typeof item.additionalData !== 'undefined'
						&& typeof item.additionalData.role !== 'undefined'
							? item.additionalData.role
							: ''
					),
					initiatedByType: (
						typeof item.additionalData !== 'undefined'
						&& typeof item.additionalData.initiatedByType !== 'undefined'
							? item.additionalData.initiatedByType
							: ''
					),
					opened: (item.opened === 'Y'),
					membersCount: (
						typeof item.numberOfMembers !== 'undefined'
							? parseInt(item.numberOfMembers)
							: 0
					)
				}
			};

			if (item.avatar)
			{
				preparedItem.params.previewUrl = item.avatar;
				preparedItem.imageUrl = item.avatar;
			}
			else
			{
				preparedItem.imageUrl = '';
			}

			return preparedItem;
		}

		static pathToIcon(iconName = null)
		{
			if (iconName == null)
			{
				return null;
			}

			return `${pathToExtension}/images/${iconName}`;
		}

		/**
		 *
		 * @param params
		 * @param params.siteId
		 * @param params.list
		 * @param params.title
		 */
		static open(params)
		{
			new WorkgroupList(params);
		}
	}

	this.WorkgroupList = WorkgroupList;
})();