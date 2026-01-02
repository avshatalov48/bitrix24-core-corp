/**
 * @module layout/ui/copilot-role-selector
 */
jn.define('layout/ui/copilot-role-selector', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');
	const { loadRoles, addRoleToFavorite, removeRoleFromFavorite } = require('layout/ui/copilot-role-selector/src/api');
	const { showToast } = require('toast');
	const { Color } = require('tokens');
	const { BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { qrauth } = require('qrauth/utils');
	const { ScopeType } = require('layout/ui/copilot-role-selector/src/type');
	const { CopilotRoleList } = require('layout/ui/copilot-role-selector/src/role-list');
	const { uniqBy } = require('utils/array');
	const { Type } = require('type');

	/**
	 * @class CopilotRoleSelector
	 */
	class CopilotRoleSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.selectedRole = null;
			this.rolesData = null;
			this.#initState(props);
		}

		#initState = (props) => {
			this.state = {
				selectedScopeId: props.selectedScopeId ?? ScopeType.RECENTS,
				pending: props.pending ?? true,
				showSkeletonOnlyForList: props.showSkeletonOnlyForList ?? false,
				scopes: props.scopes ?? null,
				items: props.items ?? null,
				searchString: props.searchString ?? '',
			};
		};

		async componentDidMount()
		{
			await this.fetchRolesIfNeeded();
			const scopes = this.getListScopes();
			const selectedScopeId = this.#isRecentsScopeHasOnlyUniversalRole()
				? ScopeType.EXPERTS
				: this.state.selectedScopeId;
			const items = await this.getListItems({ selectedScopeId });

			this.updateList({
				pending: false,
				scopes,
				selectedScopeId,
				items,
			}, () => {
				if (selectedScopeId === ScopeType.EXPERTS)
				{
					setTimeout(() => {
						this.#scrollToScopesEnd(true);
					}, 100);
				}
			});
		}

		#scrollToScopesEnd = (animate = false) => {
			this.listRef?.scrollToScopesEnd(animate);
		};

		#isRecentsScopeHasOnlyUniversalRole = () => {
			return this.rolesData[ScopeType.RECENTS].length === 0;
		};

		#getTestId = (suffix) => {
			const prefix = this.props.testId;

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		/**
		 * @public
		 * @function open
		 * @params {object} params
		 * @params {layout} [params.parentLayout = null]
		 * @params {boolean} [params.isControlRoot = true]
		 * @params {boolean} [params.closeLayoutAfterContextSelection = true]
		 * @params {object} [params.openWidgetConfig = {}]
		 * @params [boolean] [params.showSkipButton = {}]
		 * @params {string} [params.skipButtonText = null]
		 * @return Promise
		 */
		static open({
			parentLayout = null,
			isControlRoot = true,
			closeLayoutAfterContextSelection = true,
			openWidgetConfig = {},
			showSkipButton = false,
			skipButtonText = null,
		})
		{
			return new Promise((resolve) => {
				const config = {
					enableNavigationBarBorder: false,
					titleParams: {
						text: Loc.getMessage('COPILOT_CONTEXT_STEPPER_CHOOSE_ROLE_TITLE'),
					},
					...openWidgetConfig,
					onReady: (readyLayout) => {
						readyLayout.enableNavigationBarBorder(config.enableNavigationBarBorder);
						const selectorInstance = new CopilotRoleSelector({
							layout: readyLayout,
							contextSelectedHandler: (selectedContext) => {
								resolve(selectedContext);
								if (isControlRoot && closeLayoutAfterContextSelection)
								{
									readyLayout.close();
								}
							},
							skipButtonText,
						});
						if (showSkipButton)
						{
							readyLayout.setRightButtons([
								{
									name: skipButtonText ?? Loc.getMessage('COPILOT_CONTEXT_STEPPER_SKIP_BUTTON_TEXT'),
									type: 'text',
									color: Color.accentMainLinks.toHex(),
									callback: () => {
										resolve({
											role: selectorInstance.getUniversalRole(),
										});
									},
								},
							]);
						}
						readyLayout.showComponent(selectorInstance);
					},
				};

				if (parentLayout)
				{
					parentLayout.openWidget('layout', config);

					return;
				}

				PageManager.openWidget('layout', config);
			});
		}

		getUniversalRole()
		{
			return this.rolesData.universalRole;
		}

		render()
		{
			const { layout } = this.props;
			const {
				selectedScopeId,
				pending,
				showSkeletonOnlyForList,
				scopes,
				items,
				searchString,
			} = this.state;

			return new CopilotRoleList({
				ref: this.bindJnListRef,
				layout,
				selectedScopeId,
				pending,
				showSkeletonOnlyForList,
				scopes,
				items,
				searchString,
				contextMenuButtonDisplayField: 'displayContextMenuButton',
				enableItemContextMenu: true,
				onListItemClick: this.onListItemClick,
				getListItemContextMenuItems: this.getListItemContextMenuItems,
				badgeDisplayField: 'isNew',
				badgeText: Loc.getMessage('COPILOT_ROLE_SELECTOR_ITEM_BADGE_TEXT'),
				badgeDesign: BadgeCounterDesign.COPILOT,
				onScopeSelectionChanged: this.onScopeSelectionChanged,
				listItemImagePlaceholder: `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/copilot-role-selector/image/avatar_copilot.png`,
				onSearchStringChanged: this.onSearchStringChanged,
				searchMatchColor: Color.accentMainCopilot,
				displayFloatingButton: this.#shouldDisplayFloatingButton(),
				floatingButtonProps: {
					onClick: this.onFloatingButtonClick,
				},
			});
		}

		#shouldDisplayFloatingButton = () => {
			const { selectedScopeId } = this.state;

			return selectedScopeId === ScopeType.CUSTOMS;
		};

		bindJnListRef = (ref) => {
			this.listRef = ref;
		};

		onFloatingButtonClick = () => {
			const { layout } = this.props;

			qrauth.open({
				layout,
				redirectUrl: '/bitrix/components/bitrix/ai.role.library.grid/slider.php?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
				showHint: true,
			});
		};

		onSearchStringChanged = async (searchString) => {
			const items = await this.getListItems({
				searchString,
			});
			this.updateList({
				items,
				searchString,
			});
		};

		onListItemClick = (item) => {
			this.props.contextSelectedHandler({
				role: item,
			});
		};

		onScopeSelectionChanged = async (selectedScope) => {
			const selectedScopeId = selectedScope.id;
			const items = await this.getListItems({
				selectedScopeId,
				searchString: '',
			});
			this.updateList({
				selectedScopeId,
				searchString: '',
				items,
			});
		};

		updateList = (props = {}, callback = null) => {
			const newProps = {
				...this.state,
				...props,
			};

			this.setState({
				...newProps,
			}, callback);
		};

		#isRoleInFavorites = (role) => {
			return this.rolesData[ScopeType.FAVORITES].some((item) => item.code === role.code);
		};

		getListItemContextMenuItems = (role) => {
			const items = [];
			if (this.#isRoleInFavorites(role))
			{
				items.push({
					id: 'removeFromFavorites',
					testId: this.#getTestId('remove-from-favorites'),
					title: Loc.getMessage('COPILOT_ROLE_SELECTOR_CONTEXT_MENU_ITEM_REMOVE_FROM_FAVORITES'),
					iconName: Icon.FAVORITE,
					onItemSelected: async () => {
						const { selectedScopeId, searchString } = this.state;
						const response = await removeRoleFromFavorite(role.code);
						if (response.status === 'success')
						{
							this.rolesData[ScopeType.FAVORITES] = this.rolesData[ScopeType.FAVORITES].filter(
								(item) => item.code !== role.code,
							);
							showToast({
								message: Loc.getMessage('COPILOT_ROLE_SELECTOR_REMOVE_FAVORITES_TOAST'),
							}, this.props.layout);
							if (selectedScopeId === ScopeType.FAVORITES && searchString === '')
							{
								await this.listRef?.deleteItemsByKeys([role.key]);
								const leftItems = await this.getListItems();
								if (leftItems.length === 0)
								{
									this.updateList({
										items: leftItems,
									});
								}
							}
						}
					},
				});
			}
			else
			{
				items.push({
					id: 'addToFavorites',
					testId: this.#getTestId('add-to-favorites'),
					title: Loc.getMessage('COPILOT_ROLE_SELECTOR_CONTEXT_MENU_ITEM_ADD_TO_FAVORITES'),
					iconName: Icon.FAVORITE,
					onItemSelected: async () => {
						const response = await addRoleToFavorite(role.code);
						if (response.status === 'success')
						{
							this.rolesData[ScopeType.FAVORITES].push(role);
							showToast({
								message: Loc.getMessage('COPILOT_ROLE_SELECTOR_ADD_FAVORITES_TOAST'),
							}, this.props.layout);
						}
					},
				});
			}

			return items;
		};

		getListScopes = () => {
			return Object.values(ScopeType).map((type) => ({
				id: type,
				title: Loc.getMessage(`COPILOT_ROLE_SELECTOR_SECTION_${type.toUpperCase()}_TITLE`),
			}));
		};

		#isScopeWithUniversalRoleSelected = (selectedScopeId) => {
			return selectedScopeId === ScopeType.RECENTS
					|| selectedScopeId === ScopeType.RECOMMENDED
					|| selectedScopeId === ScopeType.EXPERTS;
		};

		fetchRolesIfNeeded = async () => {
			if (!this.rolesData)
			{
				const response = await loadRoles();
				if (response.status === 'success')
				{
					this.rolesData = this.#prepareResponseRoles(response.data);
				}
				else
				{
					return null;
				}
			}

			return this.rolesData;
		};

		isRoleMatchSearchString(role, searchString)
		{
			const lowerSearch = searchString.toLowerCase();
			const lowerName = role?.name.toLowerCase();

			return lowerName.includes(lowerSearch);
		}

		getListItems = async ({ selectedScopeId, searchString } = {}) => {
			const { selectedScopeId: scopeIdFromState, searchString: searchStringFromState } = this.state;
			const scopeId = selectedScopeId ?? scopeIdFromState;
			const search = (searchString ?? searchStringFromState).trim();
			if (!(await this.fetchRolesIfNeeded()))
			{
				return [];
			}

			const preparedItem = (item) => ({
				...item,
				type: this.#getKeyForRoleListItem(item, search),
			});

			const { universalRole } = this.rolesData;
			if (search === '')
			{
				if (this.#isScopeWithUniversalRoleSelected(scopeId))
				{
					return [universalRole, ...this.rolesData[scopeId]].map((item) => preparedItem(item));
				}

				return this.rolesData[scopeId].map((item) => preparedItem(item));
			}

			const items = [];
			if (this.isRoleMatchSearchString(universalRole, search))
			{
				items.push(preparedItem(universalRole));
			}
			Object.values(ScopeType).forEach((scopeType) => {
				items.push(
					...this.rolesData[scopeType]
						.filter((item) => this.isRoleMatchSearchString(item, search))
						.map((item) => preparedItem(item)),
				);
			});

			return uniqBy(items, 'code');
		};

		#getKeyForRoleListItem = (role, searchString) => {
			return `${role.code}_${searchString}`;
		};

		#prepareResponseRoles = (responseData) => {
			const result = { ...responseData };
			const { items } = responseData;
			if (Type.isArrayFilled(items))
			{
				result[ScopeType.EXPERTS] = items[0].roles;
			}

			Object.values(ScopeType).forEach((scopeType) => {
				result[scopeType] = result[scopeType].map((role) => ({
					...role,
					id: role.code,
					displayContextMenuButton: true,
				}));
			});
			result.universalRole.id = result.universalRole.code;
			result.universalRole.displayContextMenuButton = false;

			return result;
		};
	}

	module.exports = { CopilotRoleSelector };
});
