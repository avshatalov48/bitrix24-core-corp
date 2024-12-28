/**
 * @module crm/communication/button
 */
jn.define('crm/communication/button', (require, exports, module) => {
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const communicationIcons = require('assets/communication');
	const { PhoneType, ImType, EmailType, isExistContacts } = require('communication/connection');
	const { CommunicationMenu } = require('communication/menu');
	const { Loc } = require('loc');
	const { get, isObjectLike, mergeImmutable } = require('utils/object');

	const connections = [PhoneType, EmailType, ImType];

	const ICON_SIZE = 28;
	const ICON_COLOR = {
		ENABLED: AppTheme.colors.accentExtraDarkblue,
		DISABLED: AppTheme.colors.base6,
	};

	const TelegramConnectorManagerOpener = () => {
		try
		{
			const { TelegramConnectorManager } = require('imconnector/connectors/telegram');

			return new TelegramConnectorManager();
		}
		catch (e)
		{
			console.warn(e, 'TelegramConnectorManager not found');

			return null;
		}
	};

	/**
	 * @class CommunicationButton
	 */
	class CommunicationButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.communicationMenu = null;
			this.telegramConnectorManager = null;
			this.onClickTelegramConnection = this.onClickTelegramConnection.bind(this);
		}

		get permissions()
		{
			return BX.prop.getObject(this.props, 'permissions', {});
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (this.communicationMenu)
			{
				const { uid, value } = this.props;

				this.communicationMenu.setUid(uid);
				this.communicationMenu.setValue(value);
			}
		}

		render()
		{
			this.availableConnections = this.getExistConnections();

			const { viewRef, testId } = this.props;
			const { main, shadow, wrapper } = this.styles();
			// const WrapperView = showShadow ? Shadow : View;

			return View(
				{
					ref: viewRef,
					testId,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: main,
					onClick: this.showMenu.bind(this),
				},
				View(
					{
						radius: 2,
						offset: {
							y: 2,
						},
						inset: {
							left: 2,
							right: 2,
						},
						style: shadow,
					},
					View(
						{ style: wrapper },
						...this.getCommunicationIcons(),
					),
				),
			);
		}

		getCommunicationIcons()
		{
			const { icon: iconStyle } = this.styles();

			return connections.map((connectionType) => {
				const icon = communicationIcons[connectionType];
				const iconColor = this.availableConnections[connectionType]
					? ICON_COLOR.ENABLED
					: ICON_COLOR.DISABLED;

				return View(
					{
						style: iconStyle,
					},
					Image({
						style: {
							flex: 1,
						},
						svg: {
							content: icon(iconColor),
						},
					}),
				);
			});
		}

		getExistConnections()
		{
			const { value } = this.props;

			return Object.fromEntries(
				connections.map((connectionType) => [connectionType, isExistContacts(value, connectionType)]),
			);
		}

		showMenu()
		{
			const { value, ownerInfo, showConnectionStubs, clientOptions, uid } = this.props;

			if (!this.showHighlighted())
			{
				return;
			}

			this.communicationMenu = new CommunicationMenu({
				value,
				ownerInfo,
				connections,
				additionalItems: this.getAdditionalItems(),
				permissions: this.permissions,
				showConnectionStubs,
				clientOptions,
				uid,
				analyticsSection: 'crm',
			});

			this.communicationMenu.show();
		}

		showHighlighted()
		{
			return (
				this.hasPermissionsForAdd()
				|| this.hasPermissionsForEdit()
				|| this.hasConnections()
			);
		}

		hasPermissionsForAdd()
		{
			return Object.values(this.permissions)
				.filter((item) => isObjectLike(item))
				.some(({ add = false }) => Boolean(add));
		}

		hasPermissionsForEdit()
		{
			return Object.values(this.permissions)
				.filter((item) => isObjectLike(item))
				.some(({ update = false }) => Boolean(update));
		}

		hasPermissionsForRead()
		{
			return Object.values(this.permissions)
				.filter((item) => isObjectLike(item))
				.some(({ read = false }) => Boolean(read));
		}

		hasConnections()
		{
			return Object.values(this.availableConnections).some(Boolean);
		}

		getAdditionalItems()
		{
			const items = [];

			this.telegramConnectorManager = TelegramConnectorManagerOpener();

			if (this.telegramConnectorManager && this.props.showTelegramConnection)
			{
				items.push(this.getTelegramConnectionItem());
			}

			return items;
		}

		getTelegramConnectionItem()
		{
			return {
				id: 'telegram-connect',
				sectionCode: 'telegram-connect',
				title: Loc.getMessage('MCRM_COMMUNICATION_BUTTON_TELEGRAM_CONNECT_TITLE'),
				subtitle: Loc.getMessage('MCRM_COMMUNICATION_BUTTON_TELEGRAM_CONNECT_SUBTITLE'),
				isSelected: false,
				showSelectedImage: false,
				data: {
					svgIcon: `<svg width="28" height="29" viewBox="0 0 28 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.8008 10.412L11.3633 16.3686C11.137 16.5781 10.9912 16.859 10.9498 17.1634L10.7305 18.7811C10.7015 18.9971 10.3968 19.0185 10.3366 18.8095L9.49325 15.8597C9.39689 15.5232 9.53759 15.1637 9.83666 14.9801L17.637 10.1979C17.7769 10.1124 17.9213 10.3011 17.8008 10.412ZM20.9254 7.12059L4.54554 13.4107C4.14117 13.5657 4.14468 14.1355 4.55008 14.2865L8.54132 15.7693L10.0862 20.7148C10.1851 21.0312 10.5741 21.1486 10.8324 20.9382L13.0571 19.1331C13.2902 18.9439 13.6226 18.9346 13.8661 19.1104L17.8788 22.0105C18.1551 22.2101 18.5465 22.0596 18.6158 21.7275L21.5553 7.6527C21.631 7.28973 21.2726 6.9869 20.9254 7.12059Z" fill="${AppTheme.colors.base2}"/></svg>`,
					style: {
						container: {
							backgroundColor: AppTheme.colors.accentSoftBlue1,
						},
						item: {
							subtitle: {
								color: AppTheme.colors.base2,
							},
						},
					},
				},
				onClickCallback: this.onClickTelegramConnection,
			};
		}

		onClickTelegramConnection(resolve)
		{
			if (!this.telegramConnectorManager)
			{
				this.showTelegramConnectionAccessDeniedError();

				resolve({ closeMenu: false });
			}

			const openLinesAccess = get(this.permissions, 'openLinesAccess', null);

			const promise = openLinesAccess === null
				? this.telegramConnectorManager.hasAccess(env.userId)
				: Promise.resolve();

			return new Promise((resolve) => {
				promise
					.then((data) => {
						if (openLinesAccess === true || (data && data.canEditConnector))
						{
							resolve();
							this.telegramConnectorManager.openEditor();
						}
						else
						{
							this.showTelegramConnectionAccessDeniedError();
							resolve({ closeMenu: false });
						}
					})
					.catch(() => {
						this.showTelegramConnectionAccessDeniedError();
						resolve({ closeMenu: false });
					})
				;
			});
		}

		showTelegramConnectionAccessDeniedError()
		{
			Alert.alert(
				Loc.getMessage('MCRM_COMMUNICATION_BUTTON_TELEGRAM_CONNECT_ACCESS_DENIED_TITLE'),
				Loc.getMessage('MCRM_COMMUNICATION_BUTTON_TELEGRAM_CONNECT_ACCESS_DENIED_DESCRIPTION'),
				null,
				Loc.getMessage('MCRM_COMMUNICATION_BUTTON_TELEGRAM_CONNECT_ACCESS_DENIED_CONFIRM'),
			);
		}

		styles()
		{
			const { horizontal = true, styles } = this.props;
			const width = horizontal ? 100 : 36;
			const height = horizontal ? 36 : 100;

			const defaultStyles = {
				main: {
					display: 'flex',
					width,
					justifyContent: 'center',
					alignItems: 'center',
				},
				shadow: {
					borderRadius: height / 2,
				},
				wrapper: {
					height,
					width,
					paddingHorizontal: horizontal ? 6 : 4,
					paddingVertical: horizontal ? 4 : 6,
					borderRadius: height / 2,
					backgroundColor: this.showHighlighted() ? AppTheme.colors.accentSoftBlue2 : AppTheme.colors.bgContentTertiary,
					flexShrink: 2,
					justifyContent: 'space-evenly',
					flexDirection: horizontal ? 'row' : 'column',
					alignItems: 'center',
					...this.getBorder(),
				},
				icon: {
					width: ICON_SIZE,
					height: ICON_SIZE,
				},
			};

			return mergeImmutable(defaultStyles, styles);
		}

		getBorder()
		{
			const { border } = this.props;

			if (!border)
			{
				return {};
			}

			return {
				borderColor: this.showHighlighted() ? AppTheme.colors.accentExtraAqua : ICON_COLOR.DISABLED,
				borderWidth: 1,
			};
		}
	}

	module.exports = { CommunicationButton };
});
