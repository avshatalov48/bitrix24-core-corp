/**
 * @module layout/ui/user-selection-manager/src/selection-manager
 */
jn.define('layout/ui/user-selection-manager/src/selection-manager', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { BottomToolbar } = require('layout/ui/bottom-toolbar');
	const { PropTypes } = require('utils/validation');
	const { withCurrentDomain } = require('utils/url');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { UserSection } = require('layout/ui/user-selection-manager/src/user-section');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { Color } = require('tokens');

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
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
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

			this.sections = props.sections;
			this.usersBySections = this.getUsersBySections(props.users, props.sections);

			this.onAddUserClick = this.onAddUserClick.bind(this);
			this.onRemoveUserClick = this.onRemoveUserClick.bind(this);
		}

		getUsersBySections(users, sections)
		{
			const usersBySections = Object.fromEntries(
				Object.keys(sections).map((sectionId) => [sectionId, []]),
			);

			users.forEach((user) => {
				const { section } = user;
				if (Array.isArray(usersBySections[section]))
				{
					usersBySections[section].push(user);
				}
			});

			return usersBySections;
		}

		updateSectionUsers(sectionId)
		{
			if (this.sections[sectionId].ref)
			{
				this.sections[sectionId].ref.setUsers(this.usersBySections[sectionId]);
			}
		}

		/**
		 * @param {object} params
		 * @param {string} params.sectionId
		 * @param {boolean} params.isMultiple
		 * @param {boolean} params.canChange
		 * @param {boolean} params.canBeEmpty
		 * @param {string} params.prohibitedChangingText
		 * @param {string} params.providerContext
		 * @returns {(function(): void)|*}
		 */
		onAddUserClick({
			sectionId,
			sectionTitle,
			isMultiple,
			canChange,
			canBeEmpty,
			prohibitedChangingText,
			providerContext,
		})
		{
			return () => {
				if (!canChange)
				{
					const defaultToastText = Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_PROHIBITED_ACTION');

					Haptics.notifyWarning();
					showToast(
						{
							code: sectionId,
							message: (prohibitedChangingText || defaultToastText),
						},
						this.parentWidget,
					);

					return;
				}

				void this.showUserSelector({
					sectionId,
					sectionTitle,
					isMultiple,
					canBeEmpty,
					providerContext,
					onClose: (users) => {
						this.usersBySections[sectionId] = users.map(({ id, title, imageUrl }) => ({
							id,
							title,
							image: withCurrentDomain(imageUrl),
							section: sectionId,
						}));

						this.filterUniqueUserInSections(sectionId);

						Object.keys(this.usersBySections).forEach((userSectionId) => {
							this.updateSectionUsers(userSectionId);
						});
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
		 * @param {string} params.providerContext
		 * @returns {Promise<void>}
		 */
		showUserSelector(params)
		{
			const { sectionId, sectionTitle, isMultiple, canBeEmpty, onClose, providerContext } = params;

			const selector = EntitySelectorFactory.createByType(EntitySelectorFactoryType.USER, {
				provider: {
					context: providerContext,
					options: {
						useLettersForEmptyAvatar: Boolean(this.props.useLettersForEmptyAvatar),
					},
				},
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: canBeEmpty,
				},
				initSelectedIds: this.usersBySections[sectionId].map(({ id }) => id),
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

		filterUniqueUserInSections(changedSectionId)
		{
			const { uniqueUserInSections } = this.props;
			if (!uniqueUserInSections)
			{
				return;
			}

			const changedUsers = this.usersBySections[changedSectionId];
			Object.keys(this.usersBySections).forEach((sectionId) => {
				if (sectionId !== changedSectionId)
				{
					this.usersBySections[sectionId] = this.usersBySections[sectionId].filter(({ id: userId }) => {
						return !changedUsers.some(({ id: newUseId }) => newUseId === userId);
					});
				}
			});
		}

		/**
		 * @param {object} params
		 * @param {boolean} [params.userId]
		 * @param {string} [params.sectionId]
		 * @returns {void}
		 */
		onRemoveUserClick({ userId, sectionId })
		{
			this.usersBySections[sectionId] = this.usersBySections[sectionId].filter(({ id }) => id !== userId);
			this.updateSectionUsers(sectionId);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
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
					children: [
						...this.renderSections(),
						View({ style: { height: 20 } }),
					],
				}),
				this.renderSaveButton(),
			);
		}

		renderSections()
		{
			return Object.keys(this.sections).map((sectionId) => {
				const {
					title: sectionTitle,
					isMultiple = true,
					isExpanded = false,
					canChange = true,
					canBeEmpty = true,
					prohibitedChangingText = '',
					providerContext = null,
				} = this.sections[sectionId];

				return new UserSection({
					sectionId,
					sectionTitle,
					isMultiple,
					isExpanded,
					canChange,
					canBeEmpty,
					users: this.usersBySections[sectionId],
					onAddUser: this.onAddUserClick({
						sectionId,
						sectionTitle,
						isMultiple,
						canChange,
						canBeEmpty,
						prohibitedChangingText,
						providerContext,
					}),
					onRemoveUser: this.onRemoveUserClick,
					getParentWidget: this.getParentWidget.bind(this),
					ref: (ref) => {
						this.sections[sectionId].ref = ref;
					},
				});
			});
		}

		renderSaveButton()
		{
			const { onChange, onClose } = this.props;

			return new BottomToolbar({
				style: {
					borderRadius: 0,
				},
				items: [
					View(
						{
							style: {
								flex: 1,
								marginHorizontal: 24,
								marginVertical: 12,
							},
						},
						Button({
							stretched: true,
							text: Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_SAVE'),
							size: ButtonSize.L,
							color: Color.baseWhiteFixed,
							border: true,
							borderColor: Color.accentMainPrimary,
							backgroundColor: Color.accentMainPrimary,
							onClick: () => {
								const users = this.getUsers();

								if (onChange)
								{
									onChange(users);
								}

								this.parentWidget.close(() => {
									if (onClose)
									{
										onClose(users);
									}
								});
							},
						}),
					),
				],
			});
		}

		getUsers()
		{
			const users = [];

			Object.values(this.usersBySections).forEach((sectionUsers) => users.push(...sectionUsers));

			return users;
		}

		getParentWidget()
		{
			return this.parentWidget;
		}

		setParentWidget(layoutWidget)
		{
			this.parentWidget = layoutWidget;
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
		sections: PropTypes.objectOf(
			PropTypes.shape({
				title: PropTypes.string,
				isMultiple: PropTypes.bool,
				canChange: PropTypes.bool,
				canBeEmpty: PropTypes.bool,
				prohibitedChangingText: PropTypes.string,
				providerContext: PropTypes.string,
			}),
		),
		uniqueUserInSections: PropTypes.bool,
		useLettersForEmptyAvatar: PropTypes.bool,
		parentWidget: PropTypes.object,
		onChange: PropTypes.func,
	};

	module.exports = { UserSelectionManager };
});
