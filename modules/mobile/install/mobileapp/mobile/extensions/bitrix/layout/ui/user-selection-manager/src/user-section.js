/**
 * @module layout/ui/user-selection-manager/src/user-section
 */
jn.define('layout/ui/user-selection-manager/src/user-section', (require, exports, module) => {
	const { Color } = require('tokens');
	const { withPressed } = require('utils/color');
	const { PropTypes } = require('utils/validation');
	const { OutlineIconTypes } = require('assets/icons/types');
	const { Loc } = require('loc');
	const { IconView } = require('ui-system/blocks/icon');
	const { Avatar } = require('layout/ui/user/avatar');

	const ROW_HEIGHT = 40;
	const SECTION_HEADER_HEIGHT = 38;

	/**
	 * @class UserSection
	 */
	class UserSection extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				users: props.users,
				isExpanded: false,
			};
		}

		setUsers(users)
		{
			this.setState({ users });
		}

		/**
		 * @private
		 * @returns {View}
		 */
		render()
		{
			return View(
				{
					style: {
						marginTop: 8,
					},
				},
				this.renderSectionHeader(),
				this.renderSectionUsers(),
			);
		}

		renderSectionHeader()
		{
			return View(
				{
					style: {
						height: SECTION_HEADER_HEIGHT,
						justifyContent: 'center',
						marginLeft: 18,
					},
				},
				Text({
					style: {
						color: Color.base4.toHex(),
					},
					text: this.props.sectionTitle,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
			);
		}

		renderSectionUsers()
		{
			const { users, isExpanded } = this.state;
			const { canChange, isMultiple } = this.props;

			const usersCountToShow = 5;
			const visibleUsers = users.slice(0, usersCountToShow).map((user, index) => {
				return this.renderUserRow({ ...user, isFirstInSection: index === 0 });
			});
			const hiddenUsers = users.slice(usersCountToShow).map((user) => this.renderUserRow(user));

			return View(
				{},
				...visibleUsers,
				...(isExpanded || hiddenUsers.length === 1 ? hiddenUsers : []),
				(hiddenUsers.length > 1 && this.renderMoreRow(hiddenUsers.length)),
				(canChange && isMultiple && this.renderAddUserButton()),
			);
		}

		/**
		 * @private
		 * @param {object} params
		 * @param {number} params.id
		 * @param {string} params.title
		 * @param {object} params.image
		 * @param {object} [params.isFirstInSection]
		 * @returns {View}
		 */
		renderUserRow(params)
		{
			const { id, title, image, isFirstInSection = false } = params;
			if (!id || !title)
			{
				return null;
			}

			const { isMultiple, canChange, canBeEmpty } = this.props;
			const { users } = this.state;
			const testIdPrefix = `user_${id}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						height: ROW_HEIGHT,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							alignItems: 'center',
							paddingLeft: 18,
							backgroundColor: withPressed(Color.bgContentPrimary.toHex()),
						},
						testId: testIdPrefix,
						onClick: () => this.props.onAddUser(),
					},
					Avatar({
						id,
						image,
						name: title,
						size: 28,
					}),
					Text({
						style: {
							flex: 1,
							height: ROW_HEIGHT,
							marginLeft: 12,
							borderTopWidth: (isFirstInSection ? 0 : 1),
							borderTopColor: Color.bgSeparatorSecondary.toHex(),
							fontSize: 16,
							fontWeight: '400',
						},
						ellipsize: 'end',
						numberOfLines: 1,
						text: title,
						testId: `${testIdPrefix}_name`,
					}),
					(canChange && !isMultiple && this.renderUpdateUserButton(id, testIdPrefix)),
				),
				(
					canChange
					&& isMultiple
					&& (canBeEmpty || users.length > 1)
					&& this.renderRemoveUserButton(id, isFirstInSection, testIdPrefix)
				),
			);
		}

		renderUpdateUserButton(userId, testIdPrefix)
		{
			return View(
				{
					style: {
						marginRight: 6,
						height: '100%',
						paddingHorizontal: 12,
						justifyContent: 'center',
					},
					testId: `${testIdPrefix}_update`,
					onClick: () => this.props.onAddUser(),
				},
				IconView({
					icon: OutlineIconTypes.edit,
					color: Color.base4,
				}),
			);
		}

		/**
		 * @private
		 * @param {number} userId
		 * @param {bool} isWithoutBorder
		 * @param {string} testIdPrefix
		 * @returns {View}
		 */
		renderRemoveUserButton(userId, isWithoutBorder, testIdPrefix)
		{
			const { sectionId, onRemoveUser } = this.props;

			return View(
				{
					style: {
						marginRight: 6,
						height: '100%',
						paddingHorizontal: 12,
						justifyContent: 'center',
						backgroundColor: withPressed(Color.bgContentPrimary.toHex()),
						borderTopWidth: (isWithoutBorder ? 0 : 1),
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
					},
					testId: `${testIdPrefix}_remove`,
					onClick: () => onRemoveUser({ userId, sectionId }),
				},
				IconView({
					icon: OutlineIconTypes.cross,
					color: Color.base4,
				}),
			);
		}

		renderMoreRow(moreCount)
		{
			return View(
				{
					style: {
						height: ROW_HEIGHT,
						paddingLeft: 18,
						backgroundColor: withPressed(Color.bgContentPrimary.toHex()),
						justifyContent: 'center',
						alignItems: 'flex-start',
					},
					testId: `section_${this.props.sectionId}_more`,
					onClick: () => this.setState({ isExpanded: !this.state.isExpanded }),
				},
				Text({
					style: {
						fontSize: 14,
						fontWeight: '500',
						color: Color.accentMainPrimary.toHex(),
					},
					text: (
						this.state.isExpanded
							? Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_USERS_HIDE')
							: Loc.getMessage(
								'MOBILE_USER_SELECTION_MANAGER_USERS_MORE',
								{ '#COUNT#': moreCount },
							)
					),
				}),
			);
		}

		renderAddUserButton()
		{
			return View(
				{
					style: {
						height: ROW_HEIGHT,
						flexDirection: 'row',
						alignItems: 'center',
						paddingLeft: 18,
						backgroundColor: withPressed(Color.bgContentPrimary.toHex()),
					},
					testId: `section_${this.props.sectionId}_add`,
					onClick: () => this.props.onAddUser(),
				},
				IconView({
					icon: OutlineIconTypes.plus,
					size: 28,
					color: Color.base4,
				}),
				Text({
					style: {
						marginLeft: 12,
						fontSize: 16,
						color: Color.base4.toHex(),
					},
					ellipsize: 'end',
					numberOfLines: 1,
					text: this.getAddButtonText(),
				}),
			);
		}

		getAddButtonText()
		{
			if (this.props.addButtonText)
			{
				return this.props.addButtonText;
			}

			return Loc.getMessage('MOBILE_USER_SELECTION_MANAGER_USERS_ADD');
		}
	}

	UserSection.propTypes = {
		users: PropTypes.arrayOf(
			PropTypes.shape({
				id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
				title: PropTypes.string,
				image: PropTypes.string,
				section: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
			}),
		),
		sectionId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		sectionTitle: PropTypes.string,
		addButtonText: PropTypes.string,
		canChange: PropTypes.bool,
		canBeEmpty: PropTypes.bool,
		isMultiple: PropTypes.bool,
		onAddUser: PropTypes.func,
		onRemoveUser: PropTypes.func,
		getParentWidget: PropTypes.func,
	};

	module.exports = { UserSection };
});
