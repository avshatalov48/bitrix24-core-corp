/**
 * @module layout/ui/fields/user
 */
jn.define('layout/ui/fields/user', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { lock } = require('assets/common');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { ProfileView } = require('user/profile');
	const { UserListManager } = require('layout/ui/user-list');
	const { isNil } = require('utils/type');
	const { AnalyticsEvent } = require('analytics');
	const { isPhoneNumber } = require('utils/phone');
	const { Icon } = require('assets/icons');
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpsertedFromEntitySelector } = require('statemanager/redux/slices/users');

	const EMPTY_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/user/images/empty-avatar.png';
	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/user/images/default-avatar.png';
	const DEFAULT_SELECTOR_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/user.png';

	const Mode = {
		DEFAULT: 'default',
		ICONS: 'icons',
	};

	/**
	 * @class UserField
	 */
	class UserField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);

			if (this.isReadOnly() && this.isMultiple() && !this.isEmpty() && this.canOpenUserList())
			{
				this.customContentClickHandler = this.openUserList.bind(this);
			}

			this.state.showAll = false;
		}

		canOpenUserList()
		{
			return BX.prop.getBoolean(this.getConfig(), 'canOpenUserList', false);
		}

		prepareUserTitle(userTitle)
		{
			return isPhoneNumber(userTitle) ? Loc.getMessage('FIELDS_USER_NO_NAME') : userTitle;
		}

		getAnalytics()
		{
			const analytics = new AnalyticsEvent(
				isNil(this.props?.analytics) ? {} : this.props.analytics,
			);
			if (!isNil(this.props?.analytics?.module))
			{
				analytics.setSection(this.props.analytics.module);
			}

			return analytics;
		}

		getConfig()
		{
			const config = super.getConfig();
			const enableCreation = BX.prop.getBoolean(this.props.config, 'enableCreation', true);

			return {
				...config,
				analytics: this.getAnalytics(),
				enableCreation,
				selectorType: (config.selectorType || EntitySelectorFactory.Type.USER),
				mode: (config.mode && Object.values(Mode).includes(config.mode) ? config.mode : Mode.DEFAULT),
			};
		}

		renderEmptyContent()
		{
			if (this.isIconsMode())
			{
				return this.renderEmptyIcons();
			}

			return super.renderEmptyContent();
		}

		renderEmptyEntity()
		{
			if (this.isIconsMode())
			{
				return this.renderEmptyIcons();
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(DEFAULT_AVATAR),
				}),
				View(
					{
						style: {
							marginLeft: 5,
						},
					},
					Text({
						style: this.styles.emptyEntity,
						numberOfLines: 1,
						ellipsize: 'end',
						text: Loc.getMessage('FIELDS_USER_SELECT'),
					}),
				),
			);
		}

		renderEmptyIcons()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(EMPTY_AVATAR),
				}),
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(EMPTY_AVATAR),
				}),
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(EMPTY_AVATAR),
				}),
			);
		}

		renderEntityContent()
		{
			if (this.isIconsMode())
			{
				return View(
					{
						style: this.styles.entityContent,
					},
					...this.state.entityList.map((user, index) => (index > 4 ? null : this.renderEntity(user))),
					(this.state.entityList.length > 5 && Text({
						style: {
							fontSize: 11,
							fontWeight: '400',
							color: AppTheme.colors.base3,
							marginLeft: 6,
						},
						text: `+${this.state.entityList.length - 5}`,
					})),
				);
			}

			return super.renderEntityContent();
		}

		renderEntity(user = {}, showPadding = false)
		{
			const onClick = () => this.openEntity(user.id);
			const testId = `${this.testId}_USER_${user.id}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: showPadding ? 5 : undefined,
					},
					testId,
				},
				this.renderEntityIcon({ user, onClick, testId }),
				(!this.isIconsMode() && View(
					{
						style: {
							flexDirection: 'column',
							flexShrink: 2,
							marginLeft: 5,
						},
						onClick,
					},
					Text({
						style: this.styles.userTitle,
						numberOfLines: 1,
						ellipsize: 'end',
						text: this.prepareUserTitle(user.title),
						testId: `${testId}_TITLE`,
					}),
					(
						this.shouldShowSubtitle()
						&& user.customData
						&& user.customData.position
						&& Text({
							style: this.styles.userSubtitle,
							numberOfLines: 1,
							ellipsize: 'end',
							text: user.customData.position,
							testId: `${testId}_SUBTITLE`,
						})
					),
				)),
			);
		}

		renderEntityIcon({ user, onClick, testId })
		{
			const { width, marginRight } = this.styles.userImage;

			return Avatar({
				id: user.id,
				size: width,
				name: user.title,
				uri: user.imageUrl,
				withRedux: true,
				testId: `${testId}_ICON`,
				style: {
					marginRight,
				},
				onClick,
			});
		}

		getDefaultAvatar()
		{
			return DEFAULT_AVATAR;
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(String(currentDomain), '');
				imageUrl = (imageUrl.indexOf('http') === 0 ? imageUrl : `${currentDomain}${imageUrl}`);
			}

			if (imageUrl === (currentDomain + DEFAULT_SELECTOR_AVATAR))
			{
				imageUrl = currentDomain + DEFAULT_AVATAR;
			}

			return encodeURI(imageUrl);
		}

		canOpenEntity()
		{
			return true;
		}

		openEntity(userId)
		{
			if (!userId)
			{
				return;
			}

			this.getPageManager()
				.openWidget('list', {
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
				})
				.then((list) => ProfileView.open({ userId, isBackdrop: true }, list))
				.catch(console.error);
		}

		openUserList()
		{
			UserListManager.open({
				title: (this.getTitleText() === this.props.title ? this.props.title : null),
				users: this.state.entityList.map((user) => ({
					id: user.id,
					name: this.prepareUserTitle(user.title),
					avatar: user.imageUrl,
					workPosition: (user.customData && user.customData.position ? user.customData.position : ''),
				})),
				testId: this.testId,
			});
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		shouldShowSubtitle()
		{
			return BX.prop.getBoolean(this.getConfig(), 'showSubtitle', false);
		}

		isIconsMode()
		{
			return (this.getConfig().mode === Mode.ICONS);
		}

		renderLeftIcons()
		{
			if (this.isEmptyEditable())
			{
				return Image(
					{
						style: {
							width: 24,
							height: 24,
							marginRight: 8,
						},
						svg: {
							content: this.getSvgImages().defaultAvatar(this.getTitleColor()),
						},
					},
				);
			}

			return null;
		}

		renderRightIcons()
		{
			if (this.isEditRestricted())
			{
				return View(
					{
						style: {
							width: 24,
							height: 24,
							justifyContent: 'center',
							alignItems: 'center',
							marginLeft: 5,
						},
					},
					Image(
						{
							style: {
								width: 28,
								height: 29,
							},
							svg: {
								content: lock,
							},
						},
					),
				);
			}

			return super.renderRightIcons();
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				emptyEntity: {
					...styles.emptyValue,
					flex: null,
				},
				entityContent: {
					...styles.entityContent,
					flexDirection: (this.isIconsMode() ? 'row' : 'column'),
					flexWrap: 'no-wrap',
				},
				userImage: {
					width: 24,
					height: 24,
					borderRadius: 12,
					marginRight: (this.isIconsMode() ? 2 : 0),
				},
				userTitle: {
					color: AppTheme.colors.accentMainLinks,
					fontSize: 16,
					flexShrink: 2,
				},
				userSubtitle: {
					color: AppTheme.colors.base4,
					fontSize: 12,
					flexShrink: 2,
				},
			};
		}

		getDefaultLeftIcon()
		{
			return this.getConfig().defaultLeftIcon || Icon.PERSON;
		}

		setReduxState(userList)
		{
			dispatch(usersUpsertedFromEntitySelector(userList));
		}
	}

	UserField.defaultProps = {
		...EntitySelectorFieldClass.defaultProps,
		config: {
			...EntitySelectorFieldClass.defaultProps.config,
			selectorType: EntitySelectorFactory.Type.USER,
			mode: Mode.DEFAULT,
		},
	};

	module.exports = {
		UserType: 'user',
		UserFieldMode: Mode,
		UserFieldClass: UserField,
		UserField: (props) => new UserField(props),
	};
});
