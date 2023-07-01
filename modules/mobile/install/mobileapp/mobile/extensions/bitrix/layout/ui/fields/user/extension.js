/**
 * @module layout/ui/fields/user
 */
jn.define('layout/ui/fields/user', (require, exports, module) => {
	const { lock } = require('assets/common');
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { ProfileView } = require('user/profile');
	const { UserListManager } = require('layout/ui/user-list');

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

			if (this.isReadOnly() && this.isMultiple() && this.isIconsMode() && !this.isEmpty())
			{
				this.customContentClickHandler = () => {
					UserListManager.open({
						title: (this.getTitleText() === this.props.title ? this.props.title : null),
						users: this.state.entityList.map((user) => ({
							id: user.id,
							name: user.title,
							avatar: user.imageUrl,
							workPosition: (user.customData && user.customData.position ? user.customData.position : ''),
						})),
						testId: this.testId,
					});
				};
			}

			this.state.showAll = false;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
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
						text: BX.message('FIELDS_USER_SELECT'),
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
							color: '#828b95',
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
			const onClick = this.openEntity.bind(this, user.id);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: showPadding ? 5 : undefined,
					},
					testId: `${this.testId}_USER_${user.id}`,
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(user.imageUrl || DEFAULT_AVATAR),
					onClick,
				}),
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
						text: user.title,
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
						})
					),
				)),
			);
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
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
			this
				.getPageManager()
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
				.then(list => ProfileView.open({ userId, isBackdrop: true }, list))
			;
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
					color: '#0b66c3',
					fontSize: 16,
					flexShrink: 2,
				},
				userSubtitle: {
					color: '#a8adb4',
					fontSize: 12,
					flexShrink: 2,
				},
			};
		}
	}

	module.exports = {
		UserType: 'user',
		UserFieldMode: Mode,
		UserField: props => new UserField(props),
	};
});
