/**
 * @module im/messenger/controller/sidebar/sidebar-view
 */
jn.define('im/messenger/controller/sidebar/sidebar-view', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');
	const { SidebarFriendlyDate } = require('im/messenger/controller/sidebar/friendly-date');
	const { SidebarProfileBtn } = require('im/messenger/controller/sidebar/sidebar-profile-btn');
	const { SidebarTabView } = require('im/messenger/controller/sidebar/tabs/tab-view');
	const { SidebarProfileUserCounter } = require('im/messenger/controller/sidebar/sidebar-profile-user-counter');
	const { Type } = require('type');
	const { ChatTitle } = require('im/messenger/lib/element');
	const AppTheme = require('apptheme');

	/**
	 * @class SidebarView
	 * @typedef {LayoutComponent<SidebarViewProps, SidebarViewState>} SidebarView
	 */
	class SidebarView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				userData: props.userData,
			};

			this.setRestService();
		}

		setRestService()
		{
			this.sidebarRestServices = this.props.restService;
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
					Logger.error(err);
				});

				return null;
			}

			return departmentName;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						justifyContent: 'flex-start',
						alignItems: 'center',
						flexDirection: 'column',
					},
				},
				this.renderProfile(),
				this.renderTabs(),
			);
		}

		renderProfile()
		{
			return View(
				{
					style: {
						justifyContent: 'flex-start',
						alignItems: 'center',
						flexDirection: 'column',
					},
				},
				this.renderInfoBlock(),
				this.renderButtonsBlock(),
			);
		}

		renderInfoBlock()
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
				this.props.isGroupDialog ? this.renderDialogUserCounter() : this.renderUserLastTime(),
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
				new Avatar({
					text: this.props.headData.title,
					uri: this.props.headData.imageUrl,
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
					onFailure: (e) => Logger.error(e),
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
			if (this.props.isNotes || this.props.isBot)
			{
				return null;
			}

			const styleText = {
				color: AppTheme.colors.base1,
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			return View(
				{
					style: {
						marginHorizontal: 42.5,
						flexDirection: 'row',
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				Text({
					style: this.props.isGroupDialog ? styleText : { ...styleText, marginLeft: 24 },
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.headData.desc,
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
				onFailure: (e) => Logger.error(e),
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
				color: AppTheme.colors.base3,
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
							backgroundColor: AppTheme.colors.base7,
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
			return new SidebarProfileUserCounter({ dialogId: this.props.dialogId });
		}

		renderUserLastTime()
		{
			const { userData } = this.state;

			const textStyle = {
				color: AppTheme.colors.base3,
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			if (Type.isUndefined(userData.lastActivityDate) || Type.isNull(userData.lastActivityDate))
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

		renderButtonsBlock()
		{
			return new SidebarProfileBtn({ buttonElements: this.props.buttonElements });
		}

		renderTabs()
		{
			return new SidebarTabView({
				dialogId: this.props.dialogId,
				isNotes: this.props.isNotes,
				isCopilot: this.props.isCopilot,
			});
		}

		/**
		 * @desc Method update state component
		 * @param {object} newState
		 * @void
		 */
		updateStateView(newState)
		{
			this.setState(newState);
		}
	}

	module.exports = { SidebarView };
});
