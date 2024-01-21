/**
 * @module layout/ui/user-selection-manager/src/selection-manager
 */
jn.define('layout/ui/user-selection-manager/src/selection-manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { withCurrentDomain } = require('utils/url');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { showSelectionManagerBackdrop } = require('layout/ui/user-selection-manager/src/backdrop');
	const { UserSelectedList } = require('layout/ui/user-selection-manager/src/user-selected-list');

	class UserSelectionManager extends LayoutComponent
	{
		static async open(props)
		{
			const { parentWidget, ...restProps } = props;
			const component = new UserSelectionManager(restProps);
			const layoutWidget = await showSelectionManagerBackdrop({ component, parentWidget });

			component.setParentWidget(layoutWidget);
		}

		constructor(props)
		{
			super(props);

			this.parentWidget = null;
			this.handleOnAddUser = this.handleOnAddUser.bind(this);
			this.handleOnRemoveUser = this.handleOnRemoveUser.bind(this);
			this.bindInitialParams(props);
		}

		componentWillReceiveProps(props)
		{
			this.bindInitialParams(props);
		}

		bindInitialParams(props)
		{
			this.setParentWidget(props.parentWidget);

			this.state = {
				userSections: this.getUserSections(props.users),
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

		handleOnAddUser(sectionId)
		{
			const { userSections: stateUserSections } = this.state;
			const addUser = (users) => {
				const userSections = clone(stateUserSections);
				userSections[sectionId] = users.map(({ id, title, imageUrl }) => ({
					id,
					title,
					image: withCurrentDomain(imageUrl),
					section: sectionId,
				}));

				this.onChange(userSections);
			};

			return () => {
				this.showUserSelector({ sectionId: sectionId.toUpperCase(), onClose: addUser });
			};
		}

		handleOnRemoveUser({ userId, sectionId })
		{
			const { userSections: stateUserSections } = this.state;

			const userSections = clone(stateUserSections);
			userSections[sectionId] = userSections[sectionId].filter(({ id }) => id !== userId);

			this.onChange(userSections);
		}

		onChange(userSections)
		{
			const { onChange } = this.props;

			this.setState({ userSections }, () => {
				if (onChange)
				{
					onChange(this.getUsers());
				}
			});
		}

		showUserSelector(params)
		{
			const { sectionId, onClose } = params;

			const selector = EntitySelectorFactory.createByType('user', {
				provider: {
					context: `TASKS_MEMBER_SELECTOR_EDIT_${sectionId}`,
				},
				createOptions: {
					enableCreation: false,
				},
				initSelectedIds: this.getSelectedIds(sectionId.toLowerCase()),
				allowMultipleSelection: true,
				closeOnSelect: true,
				events: { onClose },
				widgetParams: {
					title: Loc.getMessage(`TASKSMOBILE_LAYOUT_CHECKLIST_${sectionId}_SELECTOR_TITLE`),
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
			});

			return selector.show({}, this.parentWidget);
		}

		renderSections()
		{
			const { sectionsData } = this.props;
			const { userSections } = this.state;

			return Object.keys(sectionsData).map((sectionId) => {
				const sectionInfo = sectionsData[sectionId];

				return new UserSelectedList({
					users: userSections[sectionId] || [],
					sectionId,
					sectionTitle: sectionInfo.title,
					addButtonText: sectionInfo.addButtonText,
					onAddUser: this.handleOnAddUser(sectionId),
					onRemoveUser: this.handleOnRemoveUser,
					getParentWidget: this.getParentWidget.bind(this),
				});
			});
		}

		render()
		{
			return View(
				{
					style: {
						padding: 18,
					},
				},
				...this.renderSections(),
			);
		}
	}

	UserSelectionManager.propTypes = {
		users: PropTypes.arrayOf(
			PropTypes.shape({
				title: PropTypes.string,
				image: PropTypes.string,
				section: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
			}),
		),
		sectionsData: PropTypes.objectOf(
			PropTypes.shape({
				title: PropTypes.string,
				addButtonText: PropTypes.string,
			}),
		),
		onChange: PropTypes.func,
	};

	module.exports = { UserSelectionManager };
});
