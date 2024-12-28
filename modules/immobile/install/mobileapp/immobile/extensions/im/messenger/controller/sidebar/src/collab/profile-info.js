/**
 * @module im/messenger/controller/sidebar/collab/profile-info
 */
jn.define('im/messenger/controller/sidebar/collab/profile-info', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Theme } = require('im/lib/theme');
	const { SidebarProfileInfo } = require('im/messenger/controller/sidebar/chat/sidebar-profile-info');
	const { EventType } = require('im/messenger/const');
	const { ChatAvatar } = require('im/messenger/lib/element');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-profile-info');

	/**
	 * @class ProfileInfo
	 * @typedef {LayoutComponent<SidebarProfileInfoProps, SidebarProfileInfoState>} SidebarProfileInfo
	 */
	class ProfileInfo extends SidebarProfileInfo
	{
		constructor(props)
		{
			super(props);

			this.state = {
				userData: props.userData,
				desc: props.headData.desc,
				imageUrl: props.headData.imageUrl,
				guestCount: props.guestCount,
				title: props.headData.title,
			};
		}

		renderAvatar()
		{
			return View(
				{
					style: {
						paddingLeft: 12,
						position: 'relative',
						zIndex: 1,
						marginRight: 24,
					},
				},
				Avatar(ChatAvatar.createFromDialogId(this.props.dialogId).getSidebarTitleAvatarProps()),
			);
		}

		renderDescription()
		{
			return Text({
				style: {
					color: Theme.colors.base1,
					fontSize: 15,
					fontWeight: 400,
					textStyle: 'normal',
					align: 'baseline',
					marginBottom: 4,
					textAlign: 'start',
				},
				numberOfLines: 1,
				ellipsize: 'end',
				text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_COLLAB_DESCRIPTION'),
				testId: 'SIDEBAR_DESCRIPTION',
			});
		}

		renderCountersOrLastActivity()
		{
			const { guestCount } = this.state;
			let text = '';
			if (guestCount > 0)
			{
				const questText = Loc.getMessagePlural(
					'IMMOBILE_DIALOG_SIDEBAR_COLLAB_GUEST_COUNT',
					guestCount,
					{
						'#COUNT#': guestCount,
					},
				);

				text = ` ${questText}`;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderDialogUserCounter(),
				Text({
					style: {
						color: Theme.colors.collabAccentPrimaryAlt,
						fontSize: 13,
						fontWeight: 400,
						textAlign: 'start',
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text,
				}),
			);
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<CollabSetGuestCountData, CollabSetGuestCountActions>} mutation.payload
		 * @param {CollabSetGuestCountData} mutation.payload.data
		 */
		onUpdateGuestCount(mutation)
		{
			logger.info(`${this.constructor.name}.onUpdateGuestCount---------->`, mutation);

			const { dialogId, guestCount } = mutation.payload.data;

			if (this.props.dialogId !== dialogId)
			{
				return;
			}

			this.setState({
				guestCount,
			});
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onClose = this.onClose.bind(this);
			this.onUpdateDialogState = this.onUpdateDialogState.bind(this);
			this.onUpdateGuestCount = this.onUpdateGuestCount.bind(this);
		}

		subscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.subscribeEvents`);
			this.storeManager.on('dialoguesModel/collabModel/setGuestCount', this.onUpdateGuestCount);
			this.storeManager.on('dialoguesModel/update', this.onUpdateDialogState);

			BX.addCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		unsubscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.unsubscribeEvents`);
			this.storeManager.off('dialoguesModel/collabModel/setGuestCount', this.onUpdateGuestCount);
			this.storeManager.off('dialoguesModel/update', this.onUpdateDialogState);

			BX.removeCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		renderDepartment()
		{
			return null;
		}
	}

	module.exports = { ProfileInfo };
});
