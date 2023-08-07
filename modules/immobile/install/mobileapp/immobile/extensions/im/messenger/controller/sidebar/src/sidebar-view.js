/**
 * @module im/messenger/controller/sidebar/sidebar-view
 */
jn.define('im/messenger/controller/sidebar/sidebar-view', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { Avatar } = require('im/messenger/lib/ui/base/avatar');
	const { SidebarFriendlyDate } = require('im/messenger/controller/sidebar/friendly-date');

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
				dialogData: props.dialogData,
				buttonElements: props.buttonElements,
				isVisibleProfile: true,
			};

			this.onPanTabView = this.onPanTabView.bind(this);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#FFFFFF',
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
			const heightOptions = this.state.isVisibleProfile ? {} : { height: 0, maxHeight: 0 };

			return View(
				{
					style: {
						justifyContent: 'flex-start',
						alignItems: 'center',
						flexDirection: 'column',
						...heightOptions,
					},
					ref: (ref) => {
						this.profileView = ref;
					},
				},
				this.renderInfoBlock(),
				this.renderButtonsBlock(),
			);
		}

		animateProfile()
		{
			const heightOptions = this.state.isVisibleProfile
				? { height: 0, maxHeight: 0 }
				: {
					height: 260, // FIXME need set the real value height by device
					maxHeight: 280,
				};

			const animator = this.profileView.animate(
				{
					duration: 300,
					option: 'linear',
					...heightOptions,
				},
				() => {
					this.setState({ isVisibleProfile: !this.state.isVisibleProfile });
				},
			);
			animator.start();
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
				},
				new Avatar({
					text: this.props.headData.title,
					uri: this.props.headData.imageUrl,
					svg: this.props.headData.svg,
					color: this.props.headData.imageColor,
					size: 'XL',
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
					onClick: () => this.props.callbacks.onClickInfoBLock(),
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
						color: '#333333',
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
				color: '#333333',
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

			const styleText = {
				color: '#959CA4',
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.state.userData.department
					? Text({
						style: styleText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: this.state.userData.department,
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
								backgroundColor: '#A8ADB4',
								borderRadius: 2,
							},
						}),
					),
			);
		}

		renderDialogUserCounter()
		{
			return Text({
				style: {
					color: '#959CA4',
					fontSize: 14,
					fontWeight: 400,
					textStyle: 'normal',
					textAlign: 'center',
				},
				text: this.state.dialogData.userCounterLocal,
			});
		}

		renderUserLastTime()
		{
			const { userData } = this.state;

			const textStyle = {
				color: '#959CA4',
				fontSize: 14,
				fontWeight: 400,
				textStyle: 'normal',
				textAlign: 'center',
			};

			if (!userData.lastActivityDate)
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
			return View(
				{
					style: {
						marginTop: 16,
						marginHorizontal: 14,
						marginBottom: 12,
						flexDirection: 'row',
					},
				},
				...this.state.buttonElements,
			);
		}

		renderTabs()
		{
			// tabView.off('onPan', this.onPanTabView);
			// tabView.once('onPan', this.onPanTabView); // TODO ref sidebar/src/tab-view.js:35
			return this.props.tabView;
		}

		/**
		 * @desc Handler pan event in tab view component, starting animate visible profile view
		 * @private
		 * @void
		 */
		onPanTabView()
		{
			this.animateProfile();
		}
	}

	module.exports = { SidebarView };
});
