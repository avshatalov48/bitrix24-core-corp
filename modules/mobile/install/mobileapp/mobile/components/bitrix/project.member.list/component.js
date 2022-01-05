(() => {
	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();
	const caches = new Map();

	const pathToComponent = '/bitrix/mobileapp/mobile/components/bitrix/project.member.list/';
	const imagePrefix = `${pathToComponent}images/mobile-project-member-list-`;

	class Util
	{
		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}
	}

	class Loading
	{
		/**
		 * @param {ProjectMemberList} list
		 */
		constructor(list)
		{
			this.list = list.list;
		}

		isEnabled()
		{
			return (apiVersion >= 34);
		}

		showForList()
		{
			if (this.isEnabled())
			{
				dialogs.showSpinnerIndicator({
					color: '#777777',
					backgroundColor: '#77ffffff',
				});
			}
		}

		hideForList()
		{
			if (this.isEnabled())
			{
				dialogs.hideSpinnerIndicator();
			}
		}

		showForTitle()
		{
			this.list.setTitle({
				useProgress: true,
				largeMode: true,
			});
		}

		hideForTitle()
		{
			this.list.setTitle({
				useProgress: false,
				largeMode: true,
			});
		}
	}

	class SectionHandler
	{
		static getInstance()
		{
			if (SectionHandler.instance == null)
			{
				SectionHandler.instance = new SectionHandler();
			}

			return SectionHandler.instance;
		}

		static get sections()
		{
			return {
				owners: 'owners',
				moderators: 'moderators',
				members: 'members',
				more: 'more',
				empty: 'empty',
			};
		}

		constructor()
		{
			this.clear();
		}

		clear()
		{
			const defaultSectionParams = {
				foldable: false,
				folded: false,
				badgeValue: 0,
				sortItemParams: {},
				backgroundColor: '#f0f2f5',
				height: 40,
				styles: {title: {font: {size: 12}}},
			};

			this.items = {
				owners: {
					...{id: SectionHandler.sections.owners},
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_OWNER'),
				},
				moderators: {
					...{id: SectionHandler.sections.moderators},
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_MODERATORS'),
				},
				members: {
					...{id: SectionHandler.sections.members},
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_MEMBERS'),
				},
				more: {...{id: SectionHandler.sections.more}, ...defaultSectionParams},
				empty: {...{id: SectionHandler.sections.empty}, ...defaultSectionParams},
			};
		}

		setSortItemParams(sectionId, sortItemParams)
		{
			if (this.has(sectionId))
			{
				this.items[sectionId].sortItemParams = sortItemParams;
			}
		}

		has(id)
		{
			return (id in this.items);
		}

		get list()
		{
			return Object.values(this.items);
		}
	}

	class Cache
	{
		constructor(cacheKey)
		{
			this.cacheKey = cacheKey;

			this.storage = Application.sharedStorage(`projectMemberList_${BX.componentParameters.get('PROJECT_ID')}`);
			this.defaultData = {};
		}

		static getInstance(id)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new Cache(id)));
			}

			return caches.get(id);
		}

		get()
		{
			const cache = this.storage.get(this.cacheKey);

			if (typeof cache === 'string')
			{
				return JSON.parse(cache);
			}

			return this.defaultData;
		}

		set(data)
		{
			this.storage.set(this.cacheKey, JSON.stringify(data));
		}

		update(key, value)
		{
			const currentCache = this.get();
			currentCache[key] = value;
			this.set(currentCache);
		}

		clear()
		{
			this.set({});
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	class Order
	{
		constructor(list)
		{
			this.list = list;
		}

		getForHeaders()
		{
			return {
				ROLE: 'ASC',
			};
		}

		getForMembers()
		{
			return {
				ROLE: 'DESC',
				INITIATED_BY_TYPE: 'DESC',
			};
		}

		getForSearch()
		{
			return {
				ROLE: 'ASC',
			};
		}
	}

	class Filter
	{
		/**
		 * @param {ProjectMemberList} list
		 * @param {Integer} userId
		 * @param {Integer} projectId
		 */
		constructor(list, userId, projectId)
		{
			this.list = list;
			this.userId = userId;
			this.projectId = projectId;

			this.requestInitiatingType = null;
		}

		getForHeaders()
		{
			const filter = {
				GROUP_ID: this.projectId,
				ROLE: [
					ProjectMember.roles.owner,
					ProjectMember.roles.moderator,
				],
			};

			if (
				this.requestInitiatingType === ProjectMember.requestInitiatingType.user
				|| this.requestInitiatingType === ProjectMember.requestInitiatingType.group
			)
			{
				filter.INITIATED_BY_TYPE = this.requestInitiatingType;
			}

			return filter;
		}

		getForMembers()
		{
			const filter = {
				GROUP_ID: this.projectId,
				ROLE: [
					ProjectMember.roles.member,
					ProjectMember.roles.request,
				],
			};

			if (
				this.requestInitiatingType === ProjectMember.requestInitiatingType.user
				|| this.requestInitiatingType === ProjectMember.requestInitiatingType.group
			)
			{
				filter.ROLE = ProjectMember.roles.request;
				filter.INITIATED_BY_TYPE = this.requestInitiatingType;
			}

			return filter;
		}

		getForSearch(text)
		{
			return {
				GROUP_ID: this.projectId,
				SEARCH_INDEX: text,
			};
		}

		getRequestInitiatingType()
		{
			return this.requestInitiatingType;
		}

		setRequestInitiatingType(requestInitiatingType)
		{
			this.requestInitiatingType = requestInitiatingType;
		}
	}

	class MoreMenu
	{
		/**
		 * @param {ProjectMemberList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.filter = list.filter;
		}

		show()
		{
			const menuItems = this.prepareItems();
			const menuSections = this.prepareSections();

			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			this.popupMenu.setData(menuItems, menuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onItemSelected(item);
				}
			});
			this.popupMenu.show();
		}

		prepareSections()
		{
			return [{id: 'default'}];
		}

		prepareItems()
		{
			return [
				{
					id: 'addMembers',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_ADD_MEMBERS'),
					sectionCode: 'default',
					iconUrl: `${imagePrefix}more-add-members.png`,
				},
				{
					id: 'waiting',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_WAITING'),
					sectionCode: 'default',
					checked: (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.user),
				},
				{
					id: 'invited',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_INVITED'),
					sectionCode: 'default',
					checked: (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.group),
				},
				{
					id: 'clearFilter',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_CLEAR_FILTER'),
					sectionCode: 'default',
					iconUrl: `${imagePrefix}more-clear-filter.png`,
					disable: !this.filter.getRequestInitiatingType(),
				},
			];
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case 'addMembers':
					this.onAddMembersClick();
					break;

				case 'waiting':
					this.onWaitingClick();
					break;

				case 'invited':
					this.onInvitedClick();
					break;

				case 'clearFilter':
					this.onClearFilterClick();
					break;
			}
		}

		onAddMembersClick()
		{
			(new RecipientSelector('GROUP_INVITE', ['user']))
				.setTitle(BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_ADD_MEMBERS'))
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						(new RequestExecutor('sonet_group.user.invite', {
							GROUP_ID: this.list.projectId,
							USER_ID: recipients.user.map(user => user.id),
						}))
							.call()
							.then(() => this.list.reload())
						;
					}
				})
			;
		}

		onWaitingClick()
		{
			this.filter.setRequestInitiatingType(
				this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.user
					? null
					: ProjectMember.requestInitiatingType.user
			);
			this.list.updateTitle();
			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onInvitedClick()
		{
			this.filter.setRequestInitiatingType(
				this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.group
					? null
					: ProjectMember.requestInitiatingType.group
			);
			this.list.updateTitle();
			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onClearFilterClick()
		{
			this.filter.setRequestInitiatingType(null);
			this.list.updateTitle();
			this.list.setTopButtons();
			this.list.reload(0, true);
		}
	}

	class Action
	{
		static get swipeActions()
		{
			return {
				appoint: {
					identifier: 'appoint',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_APPOINT'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}swipe-appoint.png`,
					color: '#2f72b9',
				},
				exclude: {
					identifier: 'exclude',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_EXCLUDE'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: '#ff5752',
				},
				repeatInvite: {
					identifier: 'repeatInvite',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_REPEAT_INVITE'),
					iconName: 'action_reload',
					iconUrl: `${imagePrefix}swipe-repeat.png`,
					color: '#00b4ac',
				},
				cancelInvite: {
					identifier: 'cancelInvite',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_CANCEL_INVITE'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: '#ff5752',
				},
				acceptRequest: {
					identifier: 'acceptRequest',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_ACCEPT_REQUEST'),
					iconName: 'action_accept',
					iconUrl: `${imagePrefix}swipe-accept-request.png`,
					color: '#00b4ac',
				},
				denyRequest: {
					identifier: 'denyRequest',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_DENY_REQUEST'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: '#ff5752',
				},
			};
		}

		static get popupActions()
		{
			return {
				setOwner: {
					identifier: 'setOwner',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_SET_OWNER'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}swipe-appoint.png`,
					color: '#00b4ac',
				},
				setModerator: {
					identifier: 'setModerator',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_SET_MODERATOR'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}swipe-moderator.png`,
					color: '#00b4ac',
				},
				removeModerator: {
					identifier: 'removeModerator',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_REMOVE_MODERATOR'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-moderator.png`,
					color: '#00b4ac',
				},
			};
		}

		/**
		 * @param {ProjectMemberList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		fill(member, itemData)
		{
			if (platform !== 'ios')
			{
				let actions =
					Object.values({...Action.popupActions, ...Action.swipeActions})
						.filter(action => member.actions[action.identifier])
				;
				const appointActionIndex = actions.findIndex(action => action.identifier === 'appoint');
				if (appointActionIndex >= 0)
				{
					actions = actions.splice(appointActionIndex, 1);
				}

				itemData.menuMode = 'dialog';
				itemData.actions = actions;
			}
			else
			{
				itemData.menuMode = 'swipe';
				itemData.actions = Object.values(Action.swipeActions).filter(action => member.actions[action.identifier]);
			}

			return itemData;
		}

		onItemAction(event)
		{
			const member = this.list.memberList.get(event.item.id);

			switch (event.action.identifier)
			{
				case 'appoint':
					this.onAppointAction(member);
					break;

				case 'setOwner':
					this.onSetOwnerAction(member);
					break;

				case 'setModerator':
					this.onSetModeratorAction(member);
					break;

				case 'removeModerator':
					this.onRemoveModeratorAction(member);
					break;

				case 'exclude':
					this.onExcludeAction(member);
					break;

				case 'repeatInvite':
					this.onRepeatInviteAction(member);
					break;

				case 'cancelInvite':
					this.onCancelInviteAction(member);
					break;

				case 'acceptRequest':
					this.onAcceptRequestAction(member);
					break;

				case 'denyRequest':
					this.onDenyRequestAction(member);
					break;

				default:
					break;
			}
		}

		onAppointAction(member)
		{
			const popupItems = Object.values(Action.popupActions).map((action) => {
				if (member.actions[action.identifier])
				{
					return {
						id: action.identifier,
						title: action.title,
						sectionCode: 'default',
						iconUrl: action.iconUrl,
					};
				}
			});

			this.popupMenu = dialogs.createPopupMenu();
			this.popupMenu.setData(popupItems, [{id: 'default'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onPopupItemSelected(item, member);
				}
			});
			this.popupMenu.setPosition('center');
			this.popupMenu.show();
		}

		onPopupItemSelected(item, member)
		{
			switch (item.id)
			{
				case 'setOwner':
					this.onSetOwnerAction(member);
					break;

				case 'setModerator':
					this.onSetModeratorAction(member);
					break;

				case 'removeModerator':
					this.onRemoveModeratorAction(member);
					break;
			}
		}

		onSetOwnerAction(member)
		{
			member.setOwner().then(() => this.list.reload());
		}

		onSetModeratorAction(member)
		{
			member.setModerator().then(() => this.list.reload());
		}

		onRemoveModeratorAction(member)
		{
			member.removeModerator().then(() => this.list.reload());
		}

		onExcludeAction(member)
		{
			member.exclude().then(() => this.list.reload());
		}

		onRepeatInviteAction(member)
		{
			member.repeatInvite().then(() => this.list.reload());
		}

		onCancelInviteAction(member)
		{
			member.cancelInvite().then(() => this.list.reload());
		}

		onAcceptRequestAction(member)
		{
			member.acceptRequest().then(() => this.list.reload());
		}

		onDenyRequestAction(member)
		{
			member.denyRequest().then(() => this.list.reload());
		}
	}

	class Search
	{
		/**
		 * @param {ProjectMemberList} list
		 * @param {Integer} userId
		 */
		constructor(list, userId)
		{
			this.list = list;
			this.userId = userId;

			this.minSize = parseInt(BX.componentParameters.get('MIN_SEARCH_SIZE', 3), 10);
			this.maxUsersCount = 30;
			this.text = '';

			this.memberList = new Map();

			this.debounceFunction = this.getDebounceFunction();
		}

		getDebounceFunction()
		{
			return Util.debounce((text) => {
				const searchResultItems = [].concat(
					this.renderMemberItems(),
					this.renderLoadingItems()
				);
				const sections = [{
					id: 'default',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_RESULTS'),
				}];
				this.setSearchResultItems(searchResultItems, sections);

				(new RequestExecutor('socialnetwork.api.usertogroup.list', {
					select: ProjectMemberList.select,
					filter: this.list.filter.getForSearch(text),
					order: this.list.order.getForSearch(),
				}))
					.call()
					.then(response => this.onSearchSuccess(response))
				;
			}, 100, this);
		}

		onSearchSuccess(response)
		{
			this.memberList.clear();

			this.fillList(response.result.relations);
			this.renderList();
		}

		fillList(rows)
		{
			rows.forEach((row) => {
				const memberId = row.id.toString();
				let member;

				if (this.list.memberList.has(memberId))
				{
					member = this.list.memberList.get(memberId);
				}
				else
				{
					member = new ProjectMember(this.userId);
					member.setData(row);
				}

				this.memberList.set(String(member.id), member);
			});
		}

		renderList(fromCache = false)
		{
			console.log('ProjectMemberList.Search:renderList', {projects: this.memberList.size});

			let searchResultItems = this.renderEmptyResultItems();

			if (this.memberList.size > 0)
			{
				searchResultItems = this.renderMemberItems();
			}
			else if (fromCache)
			{
				searchResultItems = this.renderEmptyCacheItems();
			}

			const sections = [{
				id: 'default',
				title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_RESULTS'),
				backgroundColor: '#ffffff',
			}];
			this.setSearchResultItems(searchResultItems, sections);
		}

		renderMemberItems()
		{
			const memberItems = [];

			this.memberList.forEach((member) => {
				const item = this.list.prepareListItem(member, true);
				item.sectionCode = 'default';
				memberItems.push(item);
			});

			return memberItems;
		}

		renderLoadingItems()
		{
			return [{
				id: 0,
				type: 'loading',
				title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_LOADING'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		renderEmptyCacheItems()
		{
			return [{
				id: 0,
				type: 'button',
				title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_HINT'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		renderEmptyResultItems()
		{
			return [{
				id: 0,
				type: 'button',
				title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_EMPTY_RESULT'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		setSearchResultItems(items, sections)
		{
			this.list.list.setSearchResultItems(items, sections);
		}

		onUserTypeText(event)
		{
			BX.onViewLoaded(() => {
				const text = event.text.trim();
				if (this.text === text)
				{
					return;
				}
				this.text = text;
				if (this.text.length < this.minSize)
				{
					this.memberList.clear();

					if (this.text !== '')
					{
						this.fillList(this.getLocalSearches(this.text));
					}
					this.renderList(this.text === '');
					return;
				}
				this.debounceFunction(this.text);
			});
		}

		getLocalSearches(text)
		{
			const localSearches = [];
			const added = {};

			this.list.memberList.forEach((member) => {
				added[member.id] = false;
				const searchString = `${member.name}`.toLowerCase();
				searchString.split(' ').forEach((word) => {
					if (!added[member.id] && word.search(text.toLowerCase()) === 0)
					{
						localSearches.push(member);
						added[member.id] = true;
					}
				});
			});

			return localSearches;
		}

		onSearchShow()
		{
			this.renderList(true);
		}

		onSearchHide()
		{
			this.memberList.clear();
		}
	}

	class ProjectMemberList
	{
		static get select()
		{
			return [
				'ID',
				'USER_ID',
				'GROUP_ID',
				'ROLE',
				'AUTO_MEMBER',
				'INITIATED_BY_TYPE',
				'USER_NAME',
				'USER_LAST_NAME',
				'USER_SECOND_NAME',
				'USER_LOGIN',
				'USER_WORK_POSITION',
				'USER_PERSONAL_PHOTO',
				'ACTIONS',
			];
		}

		constructor(list, userId, projectId)
		{
			this.list = list;
			this.userId = userId;
			this.projectId = projectId;

			this.start = 0;
			this.pageSize = 50;

			this.memberList = new Map();

			this.cache = Cache.getInstance(`memberList_${this.userId}`);
			this.order = new Order(this);
			this.filter = new Filter(this, this.userId, this.projectId);
			this.moreMenu = new MoreMenu(this);
			this.loading = new Loading(this);
			this.action = new Action(this);
			this.search = new Search(this, this.userId);

			BX.onViewLoaded(() => {
				this.list.setItems([
					{
						type: 'loading',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_LOADING'),
					},
				]);

				this.setTopButtons();
				this.setListListeners();

				this.updateSections();
				this.loadMembersFromCache();
				this.reload();
			});
		}

		setTopButtons()
		{
			this.list.setRightButtons([
				{
					type: 'search',
					callback: () => this.list.showSearchBar(),
				},
				{
					type: (this.filter.getRequestInitiatingType() ? 'more_active' : 'more'),
					badgeCode: `projectMemberList_${this.userId}`,
					callback: () => this.moreMenu.show(),
				},
			]);
		}

		setListListeners()
		{
			const eventHandlers = {
				onRefresh: {
					callback: () => this.reload(),
					context: this,
				},
				onUserTypeText: {
					callback: this.search.onUserTypeText,
					context: this.search,
				},
				onSearchShow: {
					callback: this.search.onSearchShow,
					context: this.search,
				},
				onSearchHide: {
					callback: this.search.onSearchHide,
					context: this.search,
				},
				onSearchItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemAction: {
					callback: this.action.onItemAction,
					context: this.action,
				},
			};

			this.list.setListener((event, data) => {
				console.log(`Fire event: app.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		loadMembersFromCache()
		{
			const cache = this.cache.get();
			const cachedMembers = (cache.members || []);

			if (Array.isArray(cachedMembers) && cachedMembers.length > 0)
			{
				this.list.setItems(cachedMembers, null, false);
			}
		}

		reload(offset = 0, showLoading = false)
		{
			if (showLoading)
			{
				this.loading.showForList();
			}
			this.updateTitle(true);

			const batchOperations = {
				users: ['socialnetwork.api.usertogroup.list', {
					select: ProjectMemberList.select,
					filter: this.filter.getForMembers(),
					order: this.order.getForMembers(),
					start: offset,
				}],
			};

			if (!this.filter.getRequestInitiatingType())
			{
				batchOperations.headers = ['socialnetwork.api.usertogroup.list', {
					select: ProjectMemberList.select,
					filter: this.filter.getForHeaders(),
					order: this.order.getForHeaders(),
				}];
			}

			BX.rest.callBatch(batchOperations, (result) => {
				this.onReloadSuccess(result, showLoading, offset);
			});
		}

		onReloadSuccess(response, showLoading, offset)
		{
			console.log('ProjectMemberList.onReloadSuccess', response);

			this.start = offset + this.pageSize;

			const isFirstPage = (offset === 0);
			if (isFirstPage)
			{
				this.memberList.clear();
			}
			this.updateSections(isFirstPage);

			const {headers, users} = response;
			const headerMembers = (headers ? headers.answer.result.relations : []) || [];
			const userMembers = (users ? users.answer.result.relations : []) || [];
			const relations = headerMembers.concat(userMembers);

			const items = [];
			relations.forEach((row) => {
				const member = new ProjectMember(this.userId);
				member.setData(row);

				this.memberList.set(String(member.id), member);
				items.push(this.prepareListItem(member));
			});

			if (isFirstPage && !this.filter.getRequestInitiatingType())
			{
				this.fillCache(items);
			}

			const isNextPageExist = (userMembers.length < users.answer.total);
			this.renderMemberListItems(items, isFirstPage, isNextPageExist);

			if (showLoading)
			{
				this.loading.hideForList();
			}
			this.updateTitle();

			this.list.stopRefreshing();
		}

		updateSections(clear = true)
		{
			const sectionHandler = SectionHandler.getInstance();

			if (clear)
			{
				sectionHandler.clear();
			}

			this.list.setSections(sectionHandler.list);
		}

		/**
		 * @param {ProjectMember} member
		 * @param {Boolean} withActions
		 */
		prepareListItem(member, withActions = true)
		{
			let itemData = {
				id: String(member.id),
				title: (member.name || ''),
				subtitle: (member.workPosition || ''),
				imageUrl: (member.image || ''),
				height: 80,
				useEstimatedHeight: true,
				useLetterImage: true,
				sectionCode: SectionHandler.sections.members,
				type: 'info',
				styles: {
					title: {font: {size: 16}},
					subtitle: {font: {size: 13}},
				},
			};

			if (member.isOwner())
			{
				itemData.sectionCode = SectionHandler.sections.owners;
			}
			else if (member.isModerator())
			{
				itemData.sectionCode = SectionHandler.sections.moderators;
			}

			if (member.isAccessRequesting() || member.isAccessRequestingByMe())
			{
				itemData.styles.subtitle = {
					font: {
						size: 12,
						fontStyle: 'medium',
					},
					cornerRadius: 12,
					padding: {
						top: 4,
						right: 12,
						bottom: 4,
						left: 12,
					},
				};

				if (member.isAccessRequestingByMe())
				{
					itemData.subtitle = BX.message('MOBILE_PROJECT_MEMBER_LIST_TAG_WAITING');
					itemData.styles.subtitle.font.color = '#ff5752';
					itemData.styles.subtitle.backgroundColor = '#ffe6e5';
				}
				else
				{
					itemData.subtitle = BX.message('MOBILE_PROJECT_MEMBER_LIST_TAG_INVITED');
					itemData.styles.subtitle.font.color = '#739f00';
					itemData.styles.subtitle.backgroundColor = '#e3f3cc';
				}
			}

			if (withActions)
			{
				itemData = this.action.fill(member, itemData);
			}

			return itemData;
		}

		fillCache(list)
		{
			this.cache.update('members', list);
		}

		renderMemberListItems(items, isFirstPage, isNextPageExist)
		{
			if (isFirstPage)
			{
				this.list.setItems(items);
			}
			else
			{
				this.list.removeItem({id: '-more-'});
				this.list.addItems(items);
			}

			if (isNextPageExist)
			{
				this.list.addItems([{
					id: '-more-',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_NEXT_PAGE'),
					type: 'button',
					sectionCode: SectionHandler.sections.more,
				}]);
			}
		}

		updateTitle(useProgress = false)
		{
			const titleParams = {
				useProgress,
				largeMode: true,
				text: BX.message('MOBILE_PROJECT_MEMBER_LIST_HEADER_MEMBERS'),
			};

			if (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.user)
			{
				titleParams.text = BX.message('MOBILE_PROJECT_MEMBER_LIST_HEADER_WAITING');
			}
			else if (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.group)
			{
				titleParams.text = BX.message('MOBILE_PROJECT_MEMBER_LIST_HEADER_INVITED');
			}

			this.list.setTitle(titleParams);
		}

		onItemSelected(item)
		{
			const userId = String(item.id);

			if (userId === '-more-')
			{
				this.list.updateItem(
					{id: '-more-'},
					{
						type: 'loading',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_LOADING'),
					}
				);
				this.reload(this.start);
			}
			else if (this.memberList.has(userId))
			{
				this.memberList.get(userId).open();
			}
		}
	}

	return new ProjectMemberList(
		list,
		BX.componentParameters.get('USER_ID'),
		BX.componentParameters.get('PROJECT_ID')
	);
})();