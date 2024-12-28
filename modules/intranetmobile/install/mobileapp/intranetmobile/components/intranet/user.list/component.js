(() => {
	const require = (ext) => jn.require(ext);

	const { selectWholeUserById } = require('intranet/statemanager/redux/slices/employees/selector');
	const { observeListChange } = require('intranet/statemanager/redux/slices/employees/observers/stateful-list-observer');
	const {
		usersUpserted: intranetUsersUpserted,
		usersAdded: intranetUsersAdded,
	} = require('intranet/statemanager/redux/slices/employees');
	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const { makeLibraryImagePath, downloadImages } = require('asset-manager');
	const { Color, Component, Indent } = require('tokens');
	const { AnalyticsEvent } = require('analytics');
	const { Loc } = require('loc');

	const { TypeGenerator } = require('layout/ui/stateful-list/type-generator');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Box } = require('ui-system/layout/box');
	const { ProfileView } = require('user/profile');

	const { UserListSorting, UserListMoreMenu, UserListFilter, DepartmentButton } = require('intranet/user-list');
	const { ListItemType, ListItemsFactory } = require('intranet/simple-list/items');
	const { openIntranetInviteWidget } = require('intranet/invite-opener-new');

	const isAndroid = (Application.getPlatform() === 'android');

	class UserList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.stateFulListRef = null;

			this.userListFilter = new UserListFilter({
				presetId: UserListFilter.presetType.default,
				department: UserListFilter.defaultDepartment,
			});

			this.search = new SearchLayout({
				layout,
				id: 'users',
				cacheId: `users_${env.userId}`,
				presetId: this.userListFilter.getPresetId(),
				searchDataAction: 'intranetmobile.employees.getSearchBarPresets',
				searchDataActionParams: {},
				onSearch: this.onSearch,
				onCancel: this.onSearch,
			});

			this.sorting = new UserListSorting({ type: UserListSorting.types.WORK_DEPARTMENT, isASC: false });

			this.moreMenu = new UserListMoreMenu(
				this.sorting.getType(),
				this.sorting.getOrder(),
				{
					onSelectSorting: this.onSelectSorting,
				},
			);

			this.state = {
				sorting: this.sorting.getType(),
				order: this.sorting.getOrder(),
				department: this.userListFilter.getDefaultDepartment(),
			};

			// todo need to use the Application.getApiVersion() when it becomes clear which one
			// todo bug on android when opening the keyboard a scroll appears and hides the departments area
			this.scrollOffset = Animated.newCalculatedValue2D(0, Indent.M.toNumber());
			if (this.scrollOffset.getValue2().diffClampWithConfig)
			{
				this.departmentTop = this.scrollOffset.getValue2().diffClampWithConfig(-52, Indent.M.toNumber(), { direction: 'reverse', option: 'disableTopBounce' });
			}
			else
			{
				this.departmentTop = Indent.M.toNumber();
			}
		}

		onMomentumScrollEnd = () => {
			const initialValue = Indent.M.toNumber();
			const departmentTopValue = this.departmentTop.getValue();

			if (departmentTopValue !== initialValue && departmentTopValue !== -52)
			{
				if (departmentTopValue > -26)
				{
					const dY = departmentTopValue - Indent.M.toNumber();
					this.stateFulListRef.scrollBy({ x: 0, y: dY, animated: true, duration: 350 });
				}
				else
				{
					const dY = -departmentTopValue;
					this.stateFulListRef.scrollBy({ x: 0, y: dY, animated: true, duration: 350 });
				}
			}
		};

		onScrollEndDrag = () => {
			this.scrollEndTimer = setTimeout(this.onMomentumScrollEnd, 250);
		};

		onMomentumScrollBegin = () => {
			clearTimeout(this.scrollEndTimer);
		};

		componentDidMount()
		{
			this.unsubscribeEmployeesObserver = observeListChange(
				store,
				this.userListFilter.getEmployeesFilter,
				this.onVisibleEmployeesChange,
			);
			this.prefetchAssets();
		}

		componentWillUnmount()
		{
			if (this.unsubscribeEmployeesObserver)
			{
				this.unsubscribeEmployeesObserver();
			}
		}

		prefetchAssets()
		{
			void downloadImages([
				makeLibraryImagePath('install-desktop.svg', 'recommendation-box', 'intranet'),
				makeLibraryImagePath('enable-2fa.svg', 'recommendation-box', 'intranet'),
				makeLibraryImagePath('admin.svg', 'recommendation-box', 'intranet'),
				makeLibraryImagePath('install-mobile.svg', 'recommendation-box', 'intranet'),
				makeLibraryImagePath('empty_employee.svg', 'empty-states', 'intranet'),
				makeLibraryImagePath('empty_search.svg', 'empty-states', 'intranet'),
			]);
		}

		get canInvite()
		{
			return BX.componentParameters.get('canInvite', false);
		}

		setSorting(sorting)
		{
			if (this.sorting.getType() !== sorting)
			{
				this.sorting.setType(sorting);
				this.moreMenu.setSelectedSorting(sorting);
				this.setState({ sorting }, () => this.stateFulListRef.reload());
			}
		}

		setDepartment = (department) => {
			if (this.userListFilter.getDepartment() !== department)
			{
				this.userListFilter.setDepartment(department);
				this.setState({ department: this.userListFilter.getDepartment() }, () => this.stateFulListRef.reload());
			}
		};

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgPrimary,
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.renderList(),
				),
				this.renderDepartmentButton(),
			);
		}

		renderList()
		{
			return new StatefulList({
				testId: 'user-list',
				showAirStyle: true,
				layout,
				menuButtons: this.getLayoutMenuButtons(),
				needInitMenu: true,
				typeGenerator: {
					generator: TypeGenerator.generators.bySelectedProperties,
					properties: [
						'isCollaber',
						'isExtranet',
						'isAdmin',
						'workPosition',
						'employeeStatus',
						'department',
					],
					callbacks: {},
				},
				actions: {
					loadItems: 'intranetmobile.employees.getUserList',
				},
				actionParams: {
					loadItems: {
						filterParams: this.getSearchParams(),
						sortingParams: {
							type: this.state.sorting,
						},
					},
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				itemType: ListItemType.USER,
				itemFactory: ListItemsFactory,
				itemDetailOpenHandler: this.openUserDetail,
				getItemCustomStyles: this.getItemCustomStyles,
				isShowFloatingButton: this.canInvite,
				onFloatingButtonClick: this.onFloatingButtonClick,
				onPanListHandler: this.onPanList,
				getEmptyListComponent: this.getEmptyListComponent,
				ref: this.onListRef,
				sortingConfig: this.sorting.getSortingConfig(),
				showEmptySpaceItem: true,
				pull: {
					shouldReloadDynamically: true,
				},
				onBeforeItemsRender: this.onBeforeItemsRender,

				onScrollCalculated: {
					contentOffsetWithoutOverscroll: this.scrollOffset,
				},
				onMomentumScrollEnd: isAndroid ? this.onMomentumScrollEnd : null,
				onMomentumScrollBegin: isAndroid ? this.onMomentumScrollBegin : null,
				onScrollEndDrag: isAndroid ? this.onScrollEndDrag : null,
			});
		}

		renderDepartmentButton()
		{
			// Workaround to avoid a bug with hiding departments on iOS with a small number of items
			if (this.state.itemsCount > 4 && !isAndroid)
			{
				return View(
					{
						style: {
							position: 'absolute',
							top: this.departmentTop,
							width: '100%',
							alignItems: 'center',
							paddingHorizontal: Component.paddingLr.toNumber(),
						},
					},
					new DepartmentButton({
						department: this.state.department,
						onSelect: this.setDepartment,
						layout,
					}),
				);
			}

			return View(
				{
					style: {
						position: 'absolute',
						top: Indent.M.toNumber(),
						width: '100%',
						alignItems: 'center',
						paddingHorizontal: Component.paddingLr.toNumber(),
					},
				},
				new DepartmentButton({
					department: this.state.department,
					onSelect: this.setDepartment,
					layout,
				}),
			);
		}

		getEmptyListComponent = () => {
			const isSearchActive = !(this.userListFilter.isDefault() || this.userListFilter.isEmpty());

			let title = Loc.getMessage('M_INTRANET_USER_LIST_EMPTY_EMPLOYEE_TITLE');
			let description = Loc.getMessage('M_INTRANET_USER_LIST_EMPTY_EMPLOYEE_DESCRIPTION');
			let imageUri = makeLibraryImagePath('empty_employee.svg', 'empty-states', 'intranet');

			if (isSearchActive)
			{
				title = Loc.getMessage('M_INTRANET_USER_LIST_SEARCH_EMPTY_TITLE');
				description = Loc.getMessage('M_INTRANET_USER_LIST_SEARCH_EMPTY_DESCRIPTION');
				imageUri = makeLibraryImagePath('empty_search.svg', 'empty-states', 'intranet');
			}

			return StatusBlock({
				testId: 'empty-state',
				title,
				description,
				emptyScreen: true,
				onRefresh: () => {},
				image: Image({
					resizeMode: 'contain',
					style: {
						width: 202,
						height: 172,
					},
					svg: {
						uri: imageUri,
					},
				}),
			});
		};

		getLayoutMenuButtons()
		{
			return [
				this.search.getSearchButton(),
				this.moreMenu.getMenuButton(),
			];
		}

		getSearchParams()
		{
			return {
				department: this.userListFilter.getDepartment().id,
				presetId: this.userListFilter.getPresetId(),
				searchString: this.userListFilter.getSearchString(),
			};
		}

		static get presetsIds()
		{
			return {
				company: 'company',
				extranet: 'extranet',
				fired: 'fired',
				invited: 'invited',
				waitConfirmation: 'wait_confirmation',
			};
		}

		getItemCustomStyles = (item, section, row) => {
			if (item.key === 'EmptySpace_top')
			{
				return {
					minHeight: Indent.XL.toNumber() + 52 + Indent.M.toNumber(),
				};
			}

			return {};
		};

		onBeforeItemsRender = (items) => items.map((item, index) => ({
			...item,
			showBorder: index !== items.length - 1,
			canInvite: this.canInvite,
		}));

		onVisibleEmployeesChange = ({ moved, removed, added, created }) => {
			if (!this.stateFulListRef || this.stateFulListRef.isLoading())
			{
				// delay until list is loaded to prevent race-condition with addItems loading
				setTimeout(() => {
					this.onVisibleEmployeesChange({ moved, removed, added, created });
				}, 30);

				return;
			}

			if (removed.length > 0)
			{
				void this.removeEmployees(removed);
				// need to update counters
				if (this.search?.searchLayoutView)
				{
					this.search.fetchPresets(true);
				}
			}

			if (added.length > 0)
			{
				void this.addOrRestoreEmployees(added);
			}

			if (moved.length > 0)
			{
				void this.updateEmployees(moved);
			}

			// if (created.length > 0)
			// {
			// 	void this.replaceEmployees(created);
			// }
		};

		removeEmployees(removed)
		{
			if (removed.length > 0)
			{
				const removedIds = removed.map(({ id }) => id);

				return this.stateFulListRef.deleteItem(removedIds);
			}

			return Promise.resolve();
		}

		addOrRestoreEmployees(added)
		{
			// this.search.fetchPresets(true);
		}

		updateEmployees(moved)
		{
			return this.stateFulListRef.updateItemsData(moved);
		}

		// replaceEmployees(created)
		// {}

		onSelectSorting = (sortingType) => {
			this.setSorting(sortingType);
		};

		onFloatingButtonClick = () => {
			openIntranetInviteWidget({
				analytics: new AnalyticsEvent().setSection('userList'),
				parentLayout: layout,
				onInviteSentHandler: this.onInviteSuccess,
			});
		};

		onInviteSuccess = (newEmployees) => {
			this.stateFulListRef.addItemsFromPull(newEmployees);
		};

		onPanList = () => {
			this.search.close();
		};

		onListRef = (ref) => {
			this.stateFulListRef = ref;
		};

		onItemsLoaded = (responseData, context) => {
			const { items = [], users = [] } = responseData || {};
			const isCache = context === 'cache';
			const usersMainInfo = items;
			const usersIntranetInfo = users;

			const actions = [];

			if (usersMainInfo.length > 0)
			{
				actions.push(isCache ? usersAdded(usersMainInfo) : usersUpserted(usersMainInfo));
			}

			if (usersIntranetInfo.length > 0)
			{
				actions.push(isCache ? intranetUsersAdded(usersIntranetInfo) : intranetUsersUpserted(usersIntranetInfo));
			}

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}

			this.setState({itemsCount: items.length});
		};

		openUserDetail = (userId) => {
			const user = selectWholeUserById(store.getState(), userId);

			if (!user)
			{
				return;
			}

			const { id, avatarSize100, fullName, link, workPosition } = user;
			ProfileView.open(
				{
					userId: id,
					imageUrl: encodeURI(avatarSize100),
					title: Loc.getMessage('PROFILE_INFO'),
					workPosition,
					name: fullName,
					url: link,
				},
			);
		};

		showSearch = () => {
			this.search.show();
		};

		onSearch = ({ text, presetId }) => {
			this.userListFilter.setPresetId(presetId);
			this.userListFilter.setSearchString(text);

			this.setState({}, () => this.stateFulListRef.reload());
		};
	}

	layout.showComponent(
		new UserList({
			currentUserId: Number(env.userId),
		}),
	);
})();
