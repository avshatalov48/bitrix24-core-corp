/**
 * @module layout/ui/user-selection-manager/src/selection-manager
 */
jn.define('layout/ui/user-selection-manager/src/selection-manager', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { clone } = require('utils/object');
	const { BottomSheet } = require('bottom-sheet');
	const { BottomToolbar } = require('layout/ui/bottom-toolbar');
	const { PropTypes } = require('utils/validation');
	const { withCurrentDomain } = require('utils/url');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { UserSelectedList } = require('layout/ui/user-selection-manager/src/user-selected-list');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { withPressed } = require('utils/color');

	/**
	 * @class UserSelectionManager
	 */
	class UserSelectionManager extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {boolean} [props.uniqueUserInSections]
		 * @param {PageManager} [props.parentWidget=PageManager]
		 * @param {PageManager} [props.title=Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_NAVIGATION_TITLE')]
		 * @param {...*} props.restProps
		 * @returns Promise
		 */
		static async open(props)
		{
			const {
				parentWidget = PageManager,
				title = Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_NAVIGATION_TITLE'),
				...restProps
			} = props;

			const component = new UserSelectionManager(restProps);
			const userSelectionBottomSheet = new BottomSheet({ title, component });
			const userSelectionWidget = await userSelectionBottomSheet
				.setParentWidget(parentWidget)
				.setMediumPositionPercent(70)
				.setBackgroundColor(AppTheme.colors.bgSecondary)
				.setNavigationBarColor(AppTheme.colors.bgContentPrimary)
				.showNavigationBarBorder()
				.open()
			;
			component.setParentWidget(userSelectionWidget);

			return component;
		}

		constructor(props)
		{
			super(props);

			this.parentWidget = null;

			this.handleOnAddUser = this.handleOnAddUser.bind(this);
			this.handleOnRemoveUser = this.handleOnRemoveUser.bind(this);
			this.handleOnMoreToggle = this.handleOnMoreToggle.bind(this);

			this.initialParams(props);
		}

		componentWillReceiveProps(props)
		{
			this.initialParams(props);
		}

		initialParams(props)
		{
			this.setParentWidget(props.parentWidget);

			this.state = this.prepareStateParams(props);
		}

		updateState(newState)
		{
			this.setState(this.prepareStateParams(newState));
		}

		prepareStateParams({ users, sectionsData })
		{
			return {
				userSections: this.getUserSections(users),
				sectionsData: Object.fromEntries(
					Object.entries(sectionsData).map(([sectionId, sectionData]) => {
						const currentIsExpanded = (this.state.sectionsData && this.state.sectionsData[sectionId]?.isExpanded);
						const isExpanded = Boolean(currentIsExpanded || sectionData.isExpanded);

						return [sectionId, { ...sectionData, isExpanded }];
					}),
				),
			};
		}

		getParentWidget()
		{
			return this.parentWidget;
		}

		setParentWidget(layoutWidget)
		{
			this.parentWidget = layoutWidget;
		}

		getUserSections(users)
		{
			const sections = {};

			users.forEach((user) => {
				const { section } = user;
				if (Array.isArray(sections[section]))
				{
					sections[section].push(user);
				}
				else
				{
					sections[section] = [user];
				}
			});

			return sections;
		}

		/**
		 * @param {string} sectionId
		 * @param sectionId
		 * @returns {object[]}
		 */
		getSelectedIds(sectionId)
		{
			const { userSections } = this.state;
			if (!Array.isArray(userSections[sectionId]))
			{
				return [];
			}

			return userSections[sectionId].map(({ id }) => id);
		}

		getUsers()
		{
			const { userSections } = this.state;

			const users = [];
			Object.keys(userSections).forEach((sectionId) => {
				users.push(...userSections[sectionId]);
			});

			return users;
		}

		/**
		 * @param {object} params
		 * @param {string} params.sectionId
		 * @param {boolean} params.isMultiple
		 * @param {boolean} params.canChange
		 * @param {boolean} params.canBeEmpty
		 * @param {string} [params.prohibitedChangingText]
		 * @returns {(function(): void|Promise)}
		 */
		handleOnAddUser({ sectionId, sectionTitle, isMultiple, canChange, canBeEmpty, prohibitedChangingText })
		{
			if (!canChange)
			{
				const defaultToastText = Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_PROHIBITED_ACTION');

				return () => {
					Haptics.notifyWarning();
					showToast(
						{
							code: sectionId,
							message: (prohibitedChangingText || defaultToastText),
						},
						this.parentWidget,
					);
				};
			}

			const { userSections: stateUserSections } = this.state;
			const addUser = (users) => {
				const userSections = clone(stateUserSections);
				userSections[sectionId] = users.map(({ id, title, imageUrl }) => ({
					id,
					title,
					image: withCurrentDomain(imageUrl),
					section: sectionId,
				}));

				this.setState({
					userSections: this.filterUniqueUserInSections({ userSections, sectionId }),
				});
			};

			return () => this.showUserSelector({
				sectionTitle,
				isMultiple,
				canBeEmpty,
				onClose: addUser,
				sectionId: sectionId.toUpperCase(),
			});
		}

		filterUniqueUserInSections({ userSections, sectionId })
		{
			const { uniqueUserInSections } = this.props;

			if (!uniqueUserInSections)
			{
				return userSections;
			}

			const sections = {};
			const changedUsers = userSections[sectionId];

			Object.keys(userSections).forEach((id) => {
				const isChangeSections = sectionId === id;
				if (isChangeSections)
				{
					sections[id] = userSections[sectionId];
				}
				else
				{
					sections[id] = userSections[id].filter(({ id: userId }) => {
						return !changedUsers.some(({ id: newUseId }) => newUseId === userId);
					});
				}
			});

			return sections;
		}

		/**
		 * @param {object} params
		 * @param {boolean} [params.userId]
		 * @param {string} [params.sectionId]
		 * @returns {void}
		 */
		handleOnRemoveUser({ userId, sectionId })
		{
			const { userSections: stateUserSections } = this.state;

			const userSections = clone(stateUserSections);
			userSections[sectionId] = userSections[sectionId].filter(({ id }) => id !== userId);

			this.setState({ userSections });
		}

		handleOnMoreToggle({ sectionId })
		{
			const { sectionsData } = this.state;

			return () => {
				const sectionCurrentData = sectionsData[sectionId];

				this.setState({
					sectionsData: {
						...sectionsData,
						[sectionId]: {
							...sectionCurrentData,
							isExpanded: !sectionCurrentData.isExpanded,
						},
					},
				});
			};
		}

		/**
		 * @param {object} params
		 * @param {string} params.sectionId
		 * @param {string} params.sectionTitle
		 * @param {boolean} params.isMultiple
		 * @param {boolean} params.canBeEmpty
		 * @param {function} params.onClose
		 * @returns {Promise<void>}
		 */
		showUserSelector(params)
		{
			const { sectionId, sectionTitle, isMultiple, canBeEmpty, onClose } = params;

			const selector = EntitySelectorFactory.createByType('user', {
				provider: {
					context: `TASKS_MEMBER_SELECTOR_EDIT_${sectionId}`,
				},
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: canBeEmpty,
				},
				initSelectedIds: this.getSelectedIds(sectionId.toLowerCase()),
				allowMultipleSelection: isMultiple,
				closeOnSelect: true,
				events: { onClose },
				widgetParams: {
					title: sectionTitle,
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
			});

			return selector.show({}, this.parentWidget);
		}

		renderSections()
		{
			const { userSections, sectionsData } = this.state;

			return Object.keys(sectionsData).map((sectionId) => {
				const {
					title: sectionTitle,
					isMultiple = true,
					isExpanded = false,
					canChange = true,
					canBeEmpty = true,
					prohibitedChangingText = '',
				} = sectionsData[sectionId];

				return new UserSelectedList({
					sectionId,
					sectionTitle,
					isMultiple,
					isExpanded,
					canChange,
					canBeEmpty,
					users: userSections[sectionId] || [],
					onAddUser: this.handleOnAddUser({
						sectionId,
						sectionTitle,
						isMultiple,
						canChange,
						canBeEmpty,
						prohibitedChangingText,
					}),
					onRemoveUser: this.handleOnRemoveUser,
					onMoreToggle: this.handleOnMoreToggle({ sectionId }),
					getParentWidget: this.getParentWidget.bind(this),
				});
			});
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						paddingBottom: 66,
					},
					safeArea: {
						bottom: true,
					},
				},
				UIScrollView({
					style: {
						height: '100%',
					},
					children: this.renderSections(),
				}),
				new BottomToolbar({
					style: {
						borderRadius: 0,
					},
					items: [
						View(
							{
								style: {
									flex: 1,
									height: 42,
									marginHorizontal: 24,
									marginVertical: 12,
									alignItems: 'center',
									justifyContent: 'center',
									borderRadius: 8,
									backgroundColor: withPressed(AppTheme.colors.accentMainPrimary),
								},
								onClick: () => {
									if (this.props.onChange)
									{
										this.props.onChange(this.getUsers());
									}
									this.parentWidget.close();
								},
							},
							Text({
								style: {
									fontSize: 16,
									fontWeight: '500',
									color: AppTheme.colors.baseWhiteFixed,
								},
								text: Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_SAVE'),
							}),
						),
					],
				}),
			);
		}
	}

	UserSelectionManager.propTypes = {
		users: PropTypes.arrayOf(
			PropTypes.shape({
				id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
				title: PropTypes.string,
				image: PropTypes.string,
				section: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
			}),
		),
		sectionsData: PropTypes.objectOf(
			PropTypes.shape({
				title: PropTypes.string,
				isMultiple: PropTypes.bool,
				canChange: PropTypes.bool,
				canBeEmpty: PropTypes.bool,
				prohibitedChangingText: PropTypes.string,
			}),
		),
		uniqueUserInSections: PropTypes.bool,
		parentWidget: PropTypes.object,
		onChange: PropTypes.func,
	};

	module.exports = { UserSelectionManager };
});
