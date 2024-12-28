(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const colorUtils = require('utils/color');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { RequestExecutor } = require('rest');
	const platform = Application.getPlatform();
	const caches = new Map();

	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/project/member.list/';
	const imagePrefix = `${pathToExtension}images/mobile-project-member-list-`;

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
			return true;
		}

		showForList()
		{
			if (this.isEnabled())
			{
				dialogs.showSpinnerIndicator({
					color: AppTheme.colors.base3,
					backgroundColor: colorUtils.transparent(AppTheme.colors.base8, 0.7),
				});
			}
		}

		hideForList()
		{
			dialogs.hideSpinnerIndicator();
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
				default: 'default',
				owners: 'owners',
				moderators: 'moderators',
				departments: 'departments',
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
				backgroundColor: AppTheme.colors.bgPrimary,
				height: 40,
				styles: { title: { font: { size: 12 } } },
			};

			this.items = {
				default: {
					id: SectionHandler.sections.default,
					...defaultSectionParams,
				},
				owners: {
					id: SectionHandler.sections.owners,
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_OWNER'),
				},
				moderators: {
					id: SectionHandler.sections.moderators,
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_MODERATORS'),
				},
				departments: {
					id: SectionHandler.sections.departments,
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_DEPARTMENTS'),
				},
				members: {
					id: SectionHandler.sections.members,
					...defaultSectionParams,
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SECTION_MEMBERS'),
				},
				more: { id: SectionHandler.sections.more, ...defaultSectionParams },
				empty: { id: SectionHandler.sections.empty, ...defaultSectionParams },
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
		constructor(cacheKey, projectId)
		{
			this.cacheKey = cacheKey;

			this.storage = Application.sharedStorage(`projectMemberList_${projectId}`);
			this.defaultData = {};
		}

		static getInstance(id, projectId)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new Cache(id, projectId)));
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
				AUTO_MEMBER: 'ASC',
				'USER.UF_DEPARTMENT': 'ASC',
			};
		}

		getForMembers()
		{
			return {
				ROLE: 'DESC',
				AUTO_MEMBER: 'ASC',
				'USER.UF_DEPARTMENT': 'ASC',
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
			return {
				GROUP_ID: this.projectId,
				ROLE: [
					ProjectMember.roles.owner,
					ProjectMember.roles.moderator,
				],
			};
		}

		getForMembers()
		{
			const filter = {
				GROUP_ID: this.projectId,
				ROLE: [
					ProjectMember.roles.member,
				],
			};

			switch (this.requestInitiatingType)
			{
				case ProjectMember.requestInitiatingType.user:
					if (!this.list.isOwner)
					{
						break;
					}
					filter.ROLE = ProjectMember.roles.request;
					filter.INITIATED_BY_TYPE = this.requestInitiatingType;
					break;

				case ProjectMember.requestInitiatingType.group:
					if (!this.list.isOwner)
					{
						filter.INITIATED_BY_USER_ID = this.userId;
					}
					filter.ROLE = ProjectMember.roles.request;
					filter.INITIATED_BY_TYPE = this.requestInitiatingType;
					break;

				default:
					if (this.list.isOwner)
					{
						filter.ROLE.push(ProjectMember.roles.request);
					}
					else
					{
						filter.INVITED_BY_ME = 'Y';
					}
					break;
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
			const actions = [
				{
					id: 'addMembers',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_ADD_MEMBERS'),
					sectionCode: 'action',
					data: {
						svgIcon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.1257 5.42838H19.0769V7.82727H21.5758V9.7941H19.0769V12.3123H17.1257V9.7941H14.7465V7.82727H17.1257V5.42838Z" fill="#828b95"/><path d="M15.0596 18.1339C15.7375 17.9091 16.1091 17.1961 15.9716 16.4905L15.7784 15.4988C15.6812 14.8788 14.9664 14.1829 13.3676 13.774C12.8259 13.6246 12.311 13.3929 11.8412 13.0871C11.7385 13.0291 11.7541 12.4927 11.7541 12.4927L11.2392 12.4151C11.2392 12.3716 11.1951 11.7283 11.1951 11.7283C11.8112 11.5236 11.7479 10.3157 11.7479 10.3157C12.1391 10.5304 12.3939 9.57454 12.3939 9.57454C12.8567 8.24658 12.1635 8.32688 12.1635 8.32688C12.2848 7.51619 12.2848 6.69226 12.1635 5.88158C11.8553 3.1923 7.21492 3.92237 7.76514 4.80068C6.40895 4.55362 6.71841 7.60545 6.71841 7.60545L7.01257 8.39574C6.60484 8.6573 6.68491 8.95746 6.77435 9.29277C6.81164 9.43255 6.85055 9.57844 6.85643 9.73019C6.88485 10.4918 7.35607 10.3339 7.35607 10.3339C7.38511 11.5909 8.01184 11.7546 8.01184 11.7546C8.12956 12.5439 8.05618 12.4096 8.05618 12.4096L7.49846 12.4763C7.50601 12.6558 7.49122 12.8355 7.45443 13.0115C7.13039 13.1543 6.93201 13.268 6.73558 13.3805C6.5345 13.4957 6.33548 13.6097 6.0058 13.7527C4.74672 14.2984 3.48398 14.6038 3.24072 15.5593C3.18478 15.7791 3.10399 16.1569 3.02633 16.5565C2.89499 17.2323 3.26494 17.9 3.91345 18.1174C5.49455 18.6475 7.25066 18.9599 9.104 19H9.91858C11.7526 18.9604 13.4914 18.6539 15.0596 18.1339Z" fill="#828b95"/></svg>',
					},
					isDisabled: !this.list.canInvite,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.onAddMembersClick());
						resolve({ closeMenu: false });
					}),
				},
				{
					id: 'waiting',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_WAITING'),
					sectionCode: 'filter',
					isSelected: (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.user),
					isDisabled: !this.list.isOwner,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.onWaitingClick());
						resolve({ closeMenu: false });
					}),
				},
				{
					id: 'invited',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_INVITED'),
					sectionCode: 'filter',
					isSelected: (this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.group),
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.onInvitedClick());
						resolve({ closeMenu: false });
					}),
				},
				{
					id: 'clearFilter',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_CLEAR_FILTER'),
					sectionCode: 'clear',
					data: {
						svgIcon: '<svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.5832 0.0833041L13.4166 1.91663L2.41664 12.9166L0.583314 11.0832L11.5832 0.0833041Z" fill="#525C69"/><path d="M13.4166 11.0832L11.5832 12.9166L0.583314 1.91662L2.41664 0.0833032L13.4166 11.0832Z" fill="#525C69"/></svg>',
					},
					isDisabled: !this.filter.getRequestInitiatingType(),
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.onClearFilterClick());
						resolve({ closeMenu: false });
					}),
				},
			];
			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: false,
				},
				actions,
			});
			contextMenu.show(this.list.list);
		}

		async onAddMembersClick()
		{
			const recipients = (
				await new RecipientSelector('GROUP_INVITE', ['user', 'department'])
					.setEntitiesOptions({
						user: {
							options: {
								intranetUsersOnly: !this.list.isExtranet,
							},
							searchable: true,
							dynamicLoad: true,
							dynamicSearch: true,
						},
						department: {
							options: {
								selectMode: 'departmentsOnly',
								allowFlatDepartments: true,
							},
							searchable: true,
							dynamicLoad: true,
							dynamicSearch: true,
						},
					})
					.setTitle(BX.message('MOBILE_PROJECT_MEMBER_LIST_FILTER_ADD_MEMBERS'))
					.open()
			);
			if (recipients.user && recipients.user.length > 0)
			{
				new RequestExecutor('sonet_group.user.invite', {
					GROUP_ID: this.list.projectId,
					USER_ID: recipients.user.map((user) => user.id),
				})
					.call()
					.then(() => this.list.reload())
					.catch(console.error)
				;
			}
		}

		onWaitingClick()
		{
			this.filter.setRequestInitiatingType(
				this.filter.getRequestInitiatingType() === ProjectMember.requestInitiatingType.user
					? null
					: ProjectMember.requestInitiatingType.user,
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
					: ProjectMember.requestInitiatingType.group,
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
					color: AppTheme.colors.accentMainLinks,
				},
				exclude: {
					identifier: 'exclude',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_EXCLUDE'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: AppTheme.colors.accentMainAlert,
				},
				repeatInvite: {
					identifier: 'repeatInvite',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_REPEAT_INVITE'),
					iconName: 'action_reload',
					iconUrl: `${imagePrefix}swipe-repeat.png`,
					color: AppTheme.colors.accentExtraAqua,
				},
				cancelInvite: {
					identifier: 'cancelInvite',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_CANCEL_INVITE'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: AppTheme.colors.accentMainAlert,
				},
				acceptRequest: {
					identifier: 'acceptRequest',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_ACCEPT_REQUEST'),
					iconName: 'action_accept',
					iconUrl: `${imagePrefix}swipe-accept-request.png`,
					color: AppTheme.colors.accentExtraAqua,
				},
				denyRequest: {
					identifier: 'denyRequest',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_DENY_REQUEST'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: AppTheme.colors.accentMainAlert,
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
					color: AppTheme.colors.accentExtraAqua,
				},
				setModerator: {
					identifier: 'setModerator',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_SET_MODERATOR'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}swipe-moderator.png`,
					color: AppTheme.colors.accentExtraAqua,
				},
				removeModerator: {
					identifier: 'removeModerator',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_REMOVE_MODERATOR'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-moderator.png`,
					color: AppTheme.colors.accentExtraAqua,
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

		fillForMember(member, itemData)
		{
			if (platform === 'ios')
			{
				itemData.menuMode = 'swipe';
				itemData.actions = Object.values(Action.swipeActions).filter((action) => member.actions[action.identifier]);
			}
			else
			{
				const actions = Object.values({ ...Action.popupActions, ...Action.swipeActions })
					.filter((action) => member.actions[action.identifier])
				;
				const appointActionIndex = actions.findIndex((action) => action.identifier === 'appoint');
				if (appointActionIndex >= 0)
				{
					actions.splice(appointActionIndex, 1);
				}

				itemData.menuMode = 'dialog';
				itemData.actions = actions;
			}

			return itemData;
		}

		fillForDepartment(department, itemData)
		{
			if (!this.list.isOwner)
			{
				return itemData;
			}

			itemData.actions = [
				{
					identifier: 'excludeDepartment',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SWIPE_EXCLUDE_DEPARTMENT'),
					iconName: 'action_delete',
					iconUrl: `${imagePrefix}swipe-cancel.png`,
					color: AppTheme.colors.accentMainAlert,
				},
			];
			itemData.menuMode = (platform === 'ios' ? 'swipe' : 'dialog');

			return itemData;
		}

		onItemAction(event)
		{
			if (event.item.params.type === 'user')
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
			else if (
				event.item.params.type === 'department'
				&& event.action.identifier === 'excludeDepartment'
			)
			{
				this.onExcludeDepartmentAction(event.item.id);
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
			this.popupMenu.setData(popupItems, [{ id: 'default' }], (eventName, item) => {
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
			Notify.showIndicatorSuccess({
				text: BX.message('MOBILE_PROJECT_MEMBER_LIST_ACTION_INVITE_NOTIFICATION'),
				hideAfter: 1500,
			});
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

		onExcludeDepartmentAction(departmentId)
		{
			if (this.list.departmentList.has(departmentId))
			{
				(new RequestExecutor('socialnetwork.api.workgroup.disconnectDepartments', {
					groupId: this.list.projectId,
					departmentIds: [departmentId],
				}))
					.call()
					.then(
						() => {
							this.list.departmentList.delete(departmentId);
							this.list.reload();
						},
						(error) => console.error(error),
					)
				;
			}
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

			this.minSize = parseInt((this.list.minSearchSize || 3), 10);
			this.maxUsersCount = 30;
			this.text = '';

			this.memberList = new Map();
			this.departmentList = new Map();

			this.debounceFunction = this.getDebounceFunction();
		}

		getDebounceFunction()
		{
			return Util.debounce((text) => {
				const searchResultItems = [].concat(
					this.renderMemberItems(),
					this.renderDepartmentItems(),
					this.renderLoadingItems(),
				);
				const sections = [
					{
						id: 'default',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_RESULTS'),
					},
				];
				this.setSearchResultItems(searchResultItems, sections);

				(new RequestExecutor('socialnetwork.api.usertogroup.list', {
					select: ProjectMemberList.select,
					filter: this.list.filter.getForSearch(text),
					order: this.list.order.getForSearch(),
				}))
					.call()
					.then((response) => this.onSearchSuccess(response, text))
				;
			}, 100, this);
		}

		onSearchSuccess(response, text)
		{
			this.memberList.clear();
			this.departmentList.clear();

			this.fillList(response.result.relations);
			this.getLocalDepartmentSearches(text).forEach((department) => {
				this.departmentList.set(department.ID, department);
			});
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
			console.log('ProjectMemberList.Search:renderList', { projects: this.memberList.size });

			let searchResultItems = this.renderEmptyResultItems();

			if (this.memberList.size > 0)
			{
				searchResultItems = this.renderMemberItems();
				if (this.departmentList.size > 0)
				{
					searchResultItems = searchResultItems.concat(this.renderDepartmentItems());
				}
			}
			else if (this.departmentList.size > 0)
			{
				searchResultItems = this.renderDepartmentItems();
			}
			else if (fromCache)
			{
				searchResultItems = this.renderEmptyCacheItems();
			}

			const sections = [
				{
					id: 'default',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_RESULTS'),
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
			];
			this.setSearchResultItems(searchResultItems, sections);
		}

		renderMemberItems()
		{
			const memberItems = [];

			this.memberList.forEach((member) => {
				const item = this.list.prepareMemberListItem(member);
				item.sectionCode = 'default';
				memberItems.push(item);
			});

			return memberItems;
		}

		renderDepartmentItems()
		{
			const departmentItems = [];

			this.departmentList.forEach((department) => {
				const item = this.list.prepareDepartmentListItem(department);
				item.sectionCode = 'default';
				departmentItems.push(item);
			});

			return departmentItems;
		}

		renderLoadingItems()
		{
			return [
				{
					id: 0,
					type: 'loading',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_LOADING'),
					sectionCode: 'default',
					unselectable: true,
				},
			];
		}

		renderEmptyCacheItems()
		{
			return [
				{
					id: 0,
					type: 'button',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_HINT'),
					sectionCode: 'default',
					unselectable: true,
				},
			];
		}

		renderEmptyResultItems()
		{
			return [
				{
					id: 0,
					type: 'button',
					title: BX.message('MOBILE_PROJECT_MEMBER_LIST_SEARCH_EMPTY_RESULT'),
					sectionCode: 'default',
					unselectable: true,
				},
			];
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
					this.departmentList.clear();

					if (this.text !== '')
					{
						this.fillList(this.getLocalMemberSearches(this.text));
						this.getLocalDepartmentSearches(this.text).forEach((department) => {
							this.departmentList.set(department.ID, department);
						});
					}
					this.renderList(this.text === '');

					return;
				}
				this.debounceFunction(this.text);
			});
		}

		getLocalMemberSearches(text)
		{
			const localSearches = [];
			const added = {};

			this.list.memberList.forEach((member) => {
				added[member.id] = false;
				const searchString = String(member.name).toLowerCase();
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

		getLocalDepartmentSearches(text)
		{
			const localSearches = [];
			const added = {};

			this.list.departmentList.forEach((name, id) => {
				added[id] = false;
				const searchString = String(name).toLowerCase();
				searchString.split(' ').forEach((word) => {
					if (!added[id] && word.search(text.toLowerCase()) === 0)
					{
						localSearches.push({ ID: id, NAME: name });
						added[id] = true;
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
			this.departmentList.clear();
		}
	}

	/**
	 * @class ProjectMemberList
	 */
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

		constructor(list, userId, projectId, params)
		{
			this.list = list;
			this.userId = userId;
			this.projectId = projectId;
			this.isOwner = params.isOwner;
			this.isExtranet = params.isExtranet;
			this.canInvite = params.canInvite;
			this.minSearchSize = params.minSearchSize;

			this.start = 0;
			this.pageSize = 50;

			this.memberList = new Map();
			this.departmentList = new Map();

			this.cache = Cache.getInstance(`memberList_${this.userId}_${this.projectId}`, this.projectId);
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
					type: 'more',
					accent: Boolean(this.filter.getRequestInitiatingType()),
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
				users: [
					'socialnetwork.api.usertogroup.list', {
						select: ProjectMemberList.select,
						filter: this.filter.getForMembers(),
						order: this.order.getForMembers(),
						start: offset,
					},
				],
			};

			if (!this.filter.getRequestInitiatingType())
			{
				batchOperations.group = [
					'socialnetwork.api.workgroup.get', {
						params: {
							groupId: this.projectId,
							select: ['DEPARTMENTS'],
						},
					},
				];
				batchOperations.headers = [
					'socialnetwork.api.usertogroup.list', {
						select: ProjectMemberList.select,
						filter: this.filter.getForHeaders(),
						order: this.order.getForHeaders(),
					},
				];
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
				this.departmentList.clear();
			}
			this.updateSections(isFirstPage);

			const { group, headers, users } = response;
			const departments = (group ? group.answer.result.DEPARTMENTS : []) || [];
			const headerMembers = (headers ? headers.answer.result.relations : []) || [];
			const userMembers = (users ? users.answer.result.relations : []) || [];
			const relations = headerMembers.concat(userMembers);

			const items = [];
			relations.forEach((row) => {
				const member = new ProjectMember(this.userId);
				member.setData(row);

				this.memberList.set(String(member.id), member);
				items.push(this.prepareMemberListItem(member));
			});
			departments.forEach((department) => {
				this.departmentList.set(String(department.ID), department.NAME);
				items.push(this.prepareDepartmentListItem(department));
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
		prepareMemberListItem(member, withActions = true)
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
					title: { font: { size: 16 } },
					subtitle: { font: { size: 13 } },
				},
				params: {
					type: 'user',
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
					itemData.styles.subtitle.font.color = AppTheme.colors.accentMainAlert;
					itemData.styles.subtitle.backgroundColor = AppTheme.colors.accentSoftRed2;
				}
				else
				{
					itemData.subtitle = BX.message('MOBILE_PROJECT_MEMBER_LIST_TAG_INVITED');
					itemData.styles.subtitle.font.color = AppTheme.colors.accentSoftElementGreen1;
					itemData.styles.subtitle.backgroundColor = AppTheme.colors.accentSoftGreen2;
				}
			}

			if (withActions)
			{
				itemData = this.action.fillForMember(member, itemData);
			}

			return itemData;
		}

		prepareDepartmentListItem(department)
		{
			let itemData = {
				id: String(department.ID),
				title: (department.NAME || ''),
				height: 80,
				useEstimatedHeight: true,
				useLetterImage: true,
				sectionCode: SectionHandler.sections.departments,
				type: 'info',
				styles: {
					title: { font: { size: 16 } },
				},
				params: {
					type: 'department',
				},
			};

			itemData = this.action.fillForDepartment(department, itemData);

			return itemData;
		}

		fillCache(list)
		{
			this.cache.update('members', list);
		}

		renderMemberListItems(items, isFirstPage, isNextPageExist)
		{
			if (items.length <= 0)
			{
				this.list.setItems([
					{
						id: '-none-',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_NOTHING_FOUND'),
						type: 'button',
						sectionCode: SectionHandler.sections.default,
						unselectable: true,
					},
				]);

				return;
			}

			if (isFirstPage)
			{
				this.list.setItems(items);
			}
			else
			{
				this.list.removeItem({ id: '-more-' });
				this.list.addItems(items);
			}

			if (isNextPageExist)
			{
				this.list.addItems([
					{
						id: '-more-',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_NEXT_PAGE'),
						type: 'button',
						sectionCode: SectionHandler.sections.more,
					},
				]);
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
			if (item.id === '-more-')
			{
				this.list.updateItem(
					{ id: '-more-' },
					{
						type: 'loading',
						title: BX.message('MOBILE_PROJECT_MEMBER_LIST_LOADING'),
					},
				);
				this.reload(this.start);
			}
			else if (item.params.type === 'user')
			{
				const userId = String(item.id);

				if (this.memberList.has(userId))
				{
					this.memberList.get(userId).open(this.list);
				}
			}
		}
	}

	jnexport([ProjectMemberList, 'ProjectMemberList']);
})();
