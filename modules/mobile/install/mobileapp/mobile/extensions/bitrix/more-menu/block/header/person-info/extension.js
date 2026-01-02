/**
 * @module more-menu/block/header/person-info
 */
jn.define('more-menu/block/header/person-info', (require, exports, module) => {
	const { Indent, Color, Corner } = require('tokens');

	const { Avatar } = require('ui-system/blocks/avatar');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { BadgeButton, BadgeButtonDesign, BadgeButtonSize } = require('ui-system/blocks/badges/button');
	const { Text2, Text4 } = require('ui-system/typography/text');

	const { Alert } = require('alert');
	const { AvatarPicker } = require('avatar-picker');
	const { FileConverter } = require('files/converter');
	const { getFile } = require('files/entry');
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');

	const { usersSelector, updateProfilePhoto } = require('statemanager/redux/slices/users');
	const { connect } = require('statemanager/redux/connect');

	const { transparent } = require('utils/color');
	const { Line, Circle } = require('utils/skeleton');
	const { createTestIdGenerator } = require('utils/test');
	const { PropTypes } = require('utils/validation');

	const AVATAR_SIZE = 48;

	/**
	 * @class PersonInfo
	 */
	class PersonInfo extends LayoutComponent
	{
		/**
		 * @param props
		 * @param props.testId {string}
		 * @param props.userId {number}
		 * @param props.canEditProfile {boolean}
		 * @param props.user {Object}
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});

			this.avatarPicker = new AvatarPicker();

			this.onAvatarClick = this.onAvatarClick.bind(this);
		}

		render()
		{
			const { user, canEditProfile } = this.props;

			if (!user)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							marginBottom: Indent.XL4,
						},
					},
					View(
						{
							style: {
								paddingRight: Indent.XS2.toNumber(),
								paddingTop: Indent.XS2.toNumber(),
							},
						},
						Circle(AVATAR_SIZE),
					),
					View(
						{
							style: {
								marginLeft: Indent.XL2.toNumber(),
							},
						},
						Line(148, 9),
					),
				);
			}

			const status = this.getStatus();

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: Indent.XL4,
					},
					onClick: () => {
						inAppUrl.open('/bitrix24/profile', {
							canEditProfile,
						});
					},
				},
				this.renderAvatar(),
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
							paddingTop: Indent.XS2.toNumber(),
							flex: 2,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						Text2({
							text: user.fullName,
							testId: this.getTestId('full_name'),
							color: Color.base1,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
						IconView({
							icon: Icon.CHEVRON_TO_THE_RIGHT,
							size: 20,
							color: Color.base1,
							testId: this.getTestId('chevron'),
							resizeMode: 'cover',
							style: {
								width: 10,
								height: 21,
								marginLeft: Indent.XS.toNumber(),
								alignSelf: 'flex-end',
							},
						}),
					),
					status && Text4({
						text: status,
						testId: this.getTestId('status'),
						color: Color.base3,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							marginTop: Indent.XS2.toNumber(),
						},
					}),
				),
			);
		}

		getStatus()
		{
			if (env.isAdmin)
			{
				return Loc.getMessage('MORE_MENU_HEADER_PERSONAL_INFO_ADMIN_STATUS');
			}

			if (env.isCollaber)
			{
				return Loc.getMessage('MORE_MENU_HEADER_PERSONAL_INFO_COLLABER_STATUS');
			}

			if (env.extranet)
			{
				return Loc.getMessage('MORE_MENU_HEADER_PERSONAL_INFO_EXTRANET_STATUS');
			}

			return null;
		}

		renderAvatar()
		{
			const { user, canEditProfile } = this.props;

			const hasUserAvatar = user?.avatarSizeOriginal || user?.avatarSize100;

			return View(
				{
					style: {
						position: 'relative',
						paddingRight: Indent.XS2.toNumber(),
						paddingTop: Indent.XS2.toNumber(),
						marginRight: Indent.XL2.toNumber(),
					},
				},
				Avatar({
					id: user.id,
					name: user.name,
					size: AVATAR_SIZE,
					testId: this.getTestId('avatar'),
					image: user.avatar,
					withRedux: true,
					accent: env.isCollaber,
					onClick: canEditProfile && !hasUserAvatar && this.onAvatarClick,
				}),
				canEditProfile && !hasUserAvatar && Shadow(
					{
						radius: Corner.XS.toNumber(),
						color: transparent(Color.baseBlackFixed.toHex(), 0.12),
						offset: { x: 0, y: Indent.XS2.toNumber() },
						inset: {
							top: 0,
							right: 3,
							left: 3,
						},
						style: {
							borderRadius: Corner.M.toNumber(),
							position: 'absolute',
							top: 0,
							right: 0,
						},
						clickable: false,
					},
					BadgeButton({
						testId: this.getTestId('avatar-pen'),
						size: BadgeButtonSize.S,
						icon: Icon.EDIT,
						design: BadgeButtonDesign.WHITE,
						onClick: this.onAvatarClick,
					}),
				),
			);
		}

		async onAvatarClick()
		{
			try
			{
				const { user } = this.props;

				const image = await this.avatarPicker.open();

				if (!image)
				{
					return;
				}

				const base64 = await this.prepareUserPhoto(image);
				await this.updateProfilePhoto(user.id, image, base64);
			}
			catch (error)
			{
				console.error(error);
				this.onAvatarSelectError(error);
			}
		}

		onAvatarSelectError()
		{
			Alert.alert(
				Loc.getMessage('MORE_MENU_HEADER_PERSONAL_INFO_AVATAR_CHOOSE_ERROR_TITLE'),
				Loc.getMessage('MORE_MENU_HEADER_PERSONAL_INFO_AVATAR_CHOOSE_ERROR_TEXT'),
				() => {},
			);
		}

		/**
		 * @param {object} image
		 * @return {Promise<string>}
		 */
		async prepareUserPhoto(image)
		{
			const imagePath = image.previewUrl;
			const preparedPath = imagePath.replace('file://', '');

			const resizedPath = await new FileConverter().resize('avatarResize', {
				url: preparedPath,
				width: 1000,
				height: 1000,
			});

			const file = await getFile(resizedPath);

			file.readMode = BX.FileConst.READ_MODE.DATA_URL;

			const fileData = await file.readNext();

			if (fileData.content)
			{
				const content = fileData.content;

				return content.slice(content.indexOf('base64,') + 7);
			}

			throw new Error('File content is empty');
		}

		/**
		 * @param {number} userId
		 * @param {object} image
		 * @param {string} base64
		 * @return {Promise<*>}
		 */
		async updateProfilePhoto(userId, image, base64)
		{
			const action = await this.props.updateProfilePhoto(userId, image, base64);
			if (action.error)
			{
				throw action.error;
			}

			return action;
		}
	}

	const mapStateToProps = (state, props) => {
		return {
			user: usersSelector.selectById(state, props.userId),
		};
	};

	const mapDispatchToProps = (dispatch) => {
		return {
			updateProfilePhoto: (userId, image, base64) => dispatch(updateProfilePhoto({ userId, image, base64 })),
		};
	};

	PersonInfo.propTypes = {
		testId: PropTypes.string.isRequired,
		userId: PropTypes.number.isRequired,
		canEditProfile: PropTypes.bool,
		user: PropTypes.object,
	};

	PersonInfo.defaultProps = {
		canEditProfile: false,
	};

	module.exports = {
		PersonInfo: connect(mapStateToProps, mapDispatchToProps)(PersonInfo),
	};
});
