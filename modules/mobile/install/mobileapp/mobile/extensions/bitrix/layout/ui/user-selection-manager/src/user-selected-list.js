/**
 * @module layout/ui/user-selection-manager/src/user-selected-list
 */
jn.define('layout/ui/user-selection-manager/src/user-selected-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { withPressed } = require('utils/color');
	const { openUserProfile } = require('user/profile');
	const { PropTypes } = require('utils/validation');
	const { SafeImage } = require('layout/ui/safe-image');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const { outline: { chevronDown, cross, lock } } = require('assets/icons');
	const { Loc } = require('loc');

	const DEFAULT_SELECTOR_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/user.png';

	/**
	 * @class UserSelectedList
	 */
	class UserSelectedList extends LayoutComponent
	{
		static prepareImageUrl(imageUrl)
		{
			if (!imageUrl)
			{
				return '';
			}

			let preparedImageUrl = imageUrl;

			if (preparedImageUrl.indexOf(currentDomain) !== 0)
			{
				preparedImageUrl = preparedImageUrl.replace(String(currentDomain), '');
				preparedImageUrl = (
					preparedImageUrl.indexOf('http') === 0 ? preparedImageUrl : `${currentDomain}${preparedImageUrl}`
				);
			}

			if (preparedImageUrl === (currentDomain + DEFAULT_SELECTOR_AVATAR))
			{
				preparedImageUrl = '';
			}

			return encodeURI(preparedImageUrl);
		}

		constructor(props)
		{
			super(props);

			this.handleOpenUserProfile = this.handleOpenUserProfile.bind(this);
		}

		handleOpenUserProfile(userId)
		{
			return () => openUserProfile({ userId, parentWidget: this.props.getParentWidget() });
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
						flexDirection: 'column',
					},
				},
				this.renderSectionHeader(),
				this.renderSectionUsers(),
			);
		}

		renderSectionHeader()
		{
			const { sectionId, sectionTitle } = this.props;
			const testIdPrefix = `section_${sectionId}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						height: 38,
						justifyContent: 'space-between',
						alignItems: 'center',
						marginHorizontal: 18,
						testId: `${testIdPrefix}_header`,
					},
				},
				Text({
					style: {
						flex: 1,
						marginRight: 10,
						color: AppTheme.colors.base3,
					},
					text: sectionTitle,
					ellipsize: 'end',
					numberOfLines: 1,
					testId: `${testIdPrefix}_title`,
				}),
				this.renderSectionHeaderButton(testIdPrefix),
			);
		}

		renderSectionHeaderButton(testIdPrefix)
		{
			const { isMultiple, canChange, onAddUser } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						height: 38,
						backgroundColor: (
							canChange
								? withPressed(AppTheme.colors.bgContentPrimary)
								: AppTheme.colors.bgContentPrimary
						),
					},
					testId: `${testIdPrefix}_button`,
					onClick: onAddUser,
				},
				(!canChange && Image({
					style: {
						width: 18,
						height: 18,
						marginRight: 4,
					},
					svg: {
						content: lock({ color: AppTheme.colors.base5 }),
					},
					testId: `${testIdPrefix}_button_icon`,
				})),
				Text({
					style: {
						color: (canChange ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base5),
					},
					text: Loc.getMessage(`MOBILE_USER_SELECTION_MANAGER_USERS_${isMultiple ? 'ADD' : 'CHANGE'}`),
					testId: `${testIdPrefix}_button_text`,
				}),
			);
		}

		renderSectionUsers()
		{
			const { users, isExpanded } = this.props;

			if (users.length === 0)
			{
				return View({
					style: {
						height: 58,
					},
				});
			}

			const firstFiveUsersRows = users.slice(0, 5).map((user, index) => {
				return this.renderUserRow({ ...user, isFirstInSection: index === 0 });
			});
			const restUsers = users.slice(5);
			const restUsersRows = (
				!isExpanded && restUsers.length > 1
					? [this.renderMoreRow(restUsers.length)]
					: restUsers.map((user) => this.renderUserRow(user))
			);

			return View(
				{},
				...firstFiveUsersRows,
				...restUsersRows,
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

			const testIdPrefix = `user_${id}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						height: 58,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							alignItems: 'center',
							paddingLeft: 18,
							backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
						},
						testId: testIdPrefix,
						onClick: this.handleOpenUserProfile(id),
					},
					SafeImage({
						style: {
							width: 24,
							height: 24,
							borderRadius: 12,
						},
						resizeMode: 'contain',
						uri: UserSelectedList.prepareImageUrl(image),
						testId: `${testIdPrefix}_avatar`,
						renderPlaceholder: () => EmptyAvatar({
							id,
							name: title,
							testId: `${testIdPrefix}_letters_avatar`,
						}),
					}),
					Text({
						style: {
							flex: 1,
							height: 58,
							marginLeft: 8,
							borderTopWidth: (isFirstInSection ? 0 : 1),
							borderTopColor: AppTheme.colors.bgSeparatorSecondary,
						},
						ellipsize: 'end',
						numberOfLines: 1,
						text: title,
						testId: `${testIdPrefix}_name`,
					}),
				),
				this.renderRemoveUserButton(id, isFirstInSection, testIdPrefix),
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
			const { users, sectionId, isMultiple, canChange, canBeEmpty, onRemoveUser } = this.props;

			if (!isMultiple || !canChange || (!canBeEmpty && users.length <= 1))
			{
				return null;
			}

			return View(
				{
					style: {
						marginRight: 6,
						height: '100%',
						paddingHorizontal: 12,
						justifyContent: 'center',
						backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
						borderTopWidth: (isWithoutBorder ? 0 : 1),
						borderTopColor: AppTheme.colors.bgSeparatorSecondary,
					},
					testId: `${testIdPrefix}_remove`,
					onClick: () => onRemoveUser({ userId, sectionId }),
				},
				Image({
					style: {
						width: 24,
						height: 24,
					},
					svg: {
						content: cross({ color: AppTheme.colors.base5 }),
					},
				}),
			);
		}

		renderMoreRow(moreCount)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						height: 58,
						paddingLeft: 18,
						backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
					},
					onClick: this.props.onMoreToggle,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Button({
						style: {
							fontSize: 12,
							fontWeight: '500',
							color: AppTheme.colors.accentMainPrimary,
						},
						text: Loc.getMessage(
							'MOBILE_USER_SELECTION_MANAGER_USERS_MORE',
							{ '#COUNT#': moreCount },
						),
					}),
					Image({
						style: {
							width: 20,
							height: 20,
							marginLeft: 4,
						},
						svg: {
							content: chevronDown({ color: AppTheme.colors.accentMainPrimary }),
						},
					}),
				),
			);
		}
	}

	UserSelectedList.propTypes = {
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
		canChange: PropTypes.bool,
		canBeEmpty: PropTypes.bool,
		isMultiple: PropTypes.bool,
		isExpanded: PropTypes.bool,
		onAddUser: PropTypes.func,
		onRemoveUser: PropTypes.func,
		onMoreToggle: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = { UserSelectedList };
});
