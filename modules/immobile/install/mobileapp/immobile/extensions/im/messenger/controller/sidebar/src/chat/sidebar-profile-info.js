/**
 * @module im/messenger/controller/sidebar/chat/sidebar-profile-info
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-profile-info', (require, exports, module) => {
	const { Type } = require('type');
	const { Feature: MobileFeature } = require('feature');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-profile-info');
	const {
		Avatar: MessengerAvatarLegacy,
		AvatarSafe,
	} = require('im/messenger/lib/ui/base/avatar');
	const {
		ChatTitle,
		ChatAvatar,
	} = require('im/messenger/lib/element');
	const { EventType } = require('im/messenger/const');
	const { Theme } = require('im/lib/theme');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SidebarProfileUserCounter } = require('im/messenger/controller/sidebar/chat/sidebar-profile-user-counter');
	const { SidebarFriendlyDate } = require('im/messenger/controller/sidebar/chat/friendly-date');

	/**
	 * @class SidebarProfileInfo
	 * @typedef {LayoutComponent<SidebarProfileInfoProps, SidebarProfileInfoState>} SidebarProfileInfo
	 */
	class SidebarProfileInfo extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarProfileInfoProps} props
		 */
		constructor(props)
		{
			super(props);

			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.state = {
				userData: props.userData,
				title: props.headData.title,
				desc: props.headData.desc,
				imageUrl: props.headData.imageUrl,
			};

			this.setRestService();
		}

		setRestService()
		{
			/** @type {SidebarRestService} */
			this.sidebarRestServices = this.props.restService;
		}

		render()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						width: '100%',
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingBottom: 12,
					},
				},
				this.renderAvatar(),
				View(
					{
						style: {
							flex: 1,
							flexGrow: 1,
						},
					},
					this.renderTitle(),
					this.renderDescription(),
					this.renderDepartment(),
					this.renderCountersOrLastActivity(),
				),
			);
		}

		renderCountersOrLastActivity()
		{
			return this.props.isGroupDialog ? this.renderDialogUserCounter() : this.renderUserLastTime();
		}

		renderAvatar()
		{
			let avatar = null;
			if (this.props.isCopilot)
			{
				avatar = new AvatarSafe({
					text: this.state.title,
					uri: this.state.imageUrl,
					svg: this.props.headData.svg,
					color: this.props.headData.imageColor,
					size: 'XL',
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				});
			}
			else if (MobileFeature.isNativeAvatarSupported())
			{
				const avatarProps = ChatAvatar.createFromDialogId(this.props.dialogId).getSidebarTitleAvatarProps();
				avatar = Avatar(avatarProps);
			}
			else
			{
				avatar = new MessengerAvatarLegacy({
					text: this.state.title,
					uri: this.state.imageUrl,
					svg: this.props.headData.svg,
					color: this.props.headData.imageColor,
					size: 'XL',
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				});
			}

			return View(
				{
					style: {
						paddingLeft: 12,
						position: 'relative',
						zIndex: 1,
						marginRight: 24,
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				avatar,
				this.renderStatusImage(),
			);
		}

		renderStatusImage()
		{
			if (this.props.isGroupDialog || this.props.isNotes)
			{
				return null;
			}

			return View(
				{
					style: {
						position: 'absolute',
						zIndex: 2,
						flexDirection: 'row',
						alignSelf: 'flex-end',
					},
				},
				Image({
					style: {
						width: 24,
						height: 24,
					},
					svg: { content: this.state.userData.statusSvg },
					onFailure: (e) => logger.error(e),
				}),
			);
		}

		renderTitle()
		{
			return View(
				{
					onClick: () => this.props?.callbacks?.onClickInfoBLock(),
				},
				Text({
					style: {
						color: ChatTitle.createFromDialogId(this.props.dialogId).getTitleColor(),
						fontSize: 17,
						fontWeight: '500',
						textStyle: 'normal',
						align: 'baseline',
						marginBottom: 5,
						textAlign: 'start',
					},
					numberOfLines: 2,
					ellipsize: 'end',
					text: this.state.title,
					testId: 'SIDEBAR_TITLE',
				}),
			);
		}

		renderDescription()
		{
			if (this.props.isNotes)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				Text({
					style: {
						color: Theme.colors.base1,
						fontSize: 14,
						fontWeight: '400',
						textStyle: 'normal',
						textAlign: 'start',
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.state.desc,
					testId: 'SIDEBAR_DESCRIPTION',
				}),
				this.renderShevronImage(),
			);
		}

		renderShevronImage()
		{
			if (this.props.isGroupDialog)
			{
				return null;
			}

			return Image({
				style: {
					width: 20,
					height: 20,
					marginTop: 2,
					marginLeft: 4,
					alignSelf: 'center',
				},
				svg: { content: this.state.userData.chevron },
				onFailure: (e) => logger.error(e),
			});
		}

		renderDepartment()
		{
			if (this.props.isGroupDialog || this.props.isNotes || this.props.isBot)
			{
				return null;
			}

			const departmentName = this.getUserDepartmentName(this.state.userData.departmentName);

			const departmentView = departmentName
				? Text({
					style: {
						color: Theme.colors.base3,
						fontSize: 14,
						fontWeight: '400',
						textStyle: 'normal',
						textAlign: 'start',
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: departmentName,
				})
				: ShimmerView(
					{
						animating: true,
						style: {
							marginTop: 7,
							marginBottom: 4,
						},
					},
					View({
						style: {
							width: 80,
							height: 8,
							backgroundColor: Theme.colors.base7,
							borderRadius: 2,
						},
					}),
				);

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				departmentView,
			);
		}

		renderDialogUserCounter()
		{
			return new SidebarProfileUserCounter({ dialogId: this.props.dialogId, isCopilot: this.props.isCopilot });
		}

		renderUserLastTime()
		{
			const { userData } = this.state;

			const textStyle = {
				color: Theme.colors.base3,
				fontSize: 14,
				fontWeight: '400',
				textStyle: 'normal',
				textAlign: 'center',
			};

			if (Type.isUndefined(userData?.lastActivityDate) || Type.isNull(userData?.lastActivityDate))
			{
				return null;
			}

			return View(
				{
					style: {
						marginTop: 5,
						flexDirection: 'row',
					},
				},
				new SidebarFriendlyDate({
					moment: userData.lastActivityDate,
					style: textStyle,
					showTime: true,
					useTimeAgo: true,
					futureAllowed: true,
					userData: userData.userModelData,
				}),
			);
		}

		/**
		 * @desc Get users department name
		 * @param {string} departmentName
		 * @return {string|null}
		 */
		getUserDepartmentName(departmentName)
		{
			if (Type.isBoolean(departmentName) || Type.isUndefined(departmentName) || departmentName.length <= 1)
			{
				this.sidebarRestServices.getUserDepartment().then((departmentNameRes) => {
					const newState = { ...this.state.userData, departmentName: departmentNameRes };
					this.setState({ userData: newState });
				}).catch((err) => {
					logger.error(err);
				});

				return null;
			}

			return departmentName;
		}

		componentDidMount()
		{
			logger.log('SidebarProfileBtn.view.componentDidMount');
			this.bindMethods();
			this.subscribeEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onClose = this.onClose.bind(this);
			this.onUpdateCopilotState = this.onUpdateCopilotState.bind(this);
			this.onUpdateDialogState = this.onUpdateDialogState.bind(this);
		}

		subscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.subscribeEvents`);
			if (this.props.isCopilot)
			{
				this.storeManager.on('dialoguesModel/copilotModel/update', this.onUpdateCopilotState);
			}

			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeEvents);
			this.storeManager.on('dialoguesModel/update', this.onUpdateDialogState);
			BX.addCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		unsubscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.unsubscribeEvents`);
			this.storeManager.off('dialoguesModel/copilotModel/update', this.onUpdateCopilotState);
			this.storeManager.off('dialoguesModel/update', this.onUpdateDialogState);

			BX.removeCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		onClose()
		{
			logger.log(`${this.constructor.name}.onClose`);
			this.unsubscribeEvents();
		}

		/**
		 * @param {Object} mutation
		 * @param {MutationPayload<CopilotUpdateData>} mutation.payload
		 */
		onUpdateCopilotState(mutation)
		{
			if (mutation.payload.data)
			{
				const fields = mutation.payload.data.fields;
				const currentRole = this.state.desc;
				try
				{
					const eventRole = fields.roles[fields.chats[0].role]?.name;
					const eventImageUrl = fields.roles[fields.chats[0].role]?.avatar?.large;
					if (currentRole !== eventRole)
					{
						this.setState({ desc: eventRole, imageUrl: encodeURI(eventImageUrl) });
					}
				}
				catch (error)
				{
					logger.error(`${this.constructor.name}.onUpdateCopilotState.catch:`, error);
				}
			}
		}

		/**
		 * @param {Object} mutation
		 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} mutation.payload
		 */
		onUpdateDialogState(mutation)
		{
			const { dialogId, fields } = mutation.payload.data;
			if (dialogId !== this.props.dialogId)
			{
				return;
			}

			logger.info(`${this.constructor.name}.onUpdateDialogState---------->`, mutation);

			const newState = Object.create(null);
			if (Type.isString(fields?.name) && fields?.name !== this.state.title)
			{
				newState.title = fields.name;
			}

			if (Type.isString(fields?.avatar) && fields?.avatar !== this.state.imageUrl)
			{
				newState.imageUrl = fields.avatar;
			}

			if (Object.keys(newState).length > 0)
			{
				this.setState(newState);
			}
		}
	}

	module.exports = { SidebarProfileInfo };
});
