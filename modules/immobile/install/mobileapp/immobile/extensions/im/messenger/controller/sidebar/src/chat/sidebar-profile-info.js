/**
 * @module im/messenger/controller/sidebar/chat/sidebar-profile-info
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-profile-info', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-profile-info');
	const { Type } = require('type');
	const { Avatar, AvatarSafe } = require('im/messenger/lib/ui/base/avatar');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { Theme } = require('im/lib/theme');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

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
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'column',
					},
				},
				this.renderAvatar(),
				this.renderTitle(),
				this.renderDescription(),
				this.renderDepartment(),
			);
		}

		renderAvatar()
		{
			return View(
				{
					style: {
						marginTop: 12,
						marginBottom: 12,
						paddingHorizontal: 2,
						paddingVertical: 2,
						position: 'relative',
						zIndex: 1,
						flexDirection: 'column',
						justifyContent: 'flex-end',
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				this.props.isCopilot ? new AvatarSafe({
					text: this.props.headData.title,
					uri: this.state.imageUrl,
					svg: this.props.headData.svg,
					color: this.props.headData.imageColor,
					size: 'XL',
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				}) : new Avatar({
					text: this.props.headData.title,
					uri: this.state.imageUrl,
					svg: this.props.headData.svg,
					color: this.props.headData.imageColor,
					size: 'XL',
					isSuperEllipse: this.props.isSuperEllipseAvatar,
				}),
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
					onClick: () => this.props.callbacks.onClickInfoBLock(),
					flexDirection: 'row',
					style: {
						marginHorizontal: 70,
					},
				},
				Text({
					style: {
						color: ChatTitle.createFromDialogId(this.props.dialogId).getTitleColor(),
						fontSize: 18,
						fontWeight: 500,
						textStyle: 'normal',
						align: 'baseline',
						marginBottom: 5,
						textAlign: 'center',
					},
					numberOfLines: 2,
					ellipsize: 'end',
					text: this.props.headData.title,
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
						marginHorizontal: 42.5,
						flexDirection: 'row',
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				Text({
					style: this.getStyleDescText(),
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.state.desc,
					testId: 'SIDEBAR_DESCRIPTION',
				}),
				this.renderShevronImage(),
			);
		}

		getStyleDescText()
		{
			const styleText = {
				color: Theme.colors.base1,
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			return this.props.isGroupDialog ? styleText : { ...styleText, marginLeft: 24 };
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

			const styleText = {
				color: Theme.colors.base3,
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			const departmentView = departmentName
				? Text({
					style: styleText,
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
			this.bindListener();
			this.subscribeEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.unsubscribeEvents = this.unsubscribeEvents.bind(this);
			this.onUpdateCopilotState = this.onUpdateCopilotState.bind(this);
		}

		subscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.subscribeEvents`);
			if (this.props.isCopilot)
			{
				this.storeManager.on('dialoguesModel/copilotModel/update', this.onUpdateCopilotState);
			}

			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeEvents);
		}

		unsubscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.unsubscribeEvents`);
			this.storeManager.off('dialoguesModel/copilotModel/update', this.onUpdateCopilotState);

			BX.removeCustomEvent('onCloseSidebarWidget', this.unsubscribeEvents);
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
	}

	module.exports = { SidebarProfileInfo };
});
