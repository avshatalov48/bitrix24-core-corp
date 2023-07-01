/**
 * @module layout/ui/detail-card/floating-button
 */
jn.define('layout/ui/detail-card/floating-button', (require, exports, module) => {
	const { AnalyticsLabel } = require('analytics-label');
	const { Feature } = require('feature');
	const { FloatingButtonMenu } = require('layout/ui/detail-card/floating-button/menu');

	const isIOS = Application.getPlatform() === 'ios';

	/**
	 * @class FloatingButton
	 */
	class FloatingButton extends LayoutComponent
	{
		/**
		 * @param props
		 * @param {DetailCardComponent} props.detailCard
		 * @param {function} props.provider
		 */
		constructor(props)
		{
			super(props);

			this.menu = null;

			/** @type {UI.FloatingButtonComponent} */
			this.floatingButtonRef = null;

			this.handleOnClick = this.handleOnClick.bind(this);
			this.handleOnLongClick = this.handleOnLongClick.bind(this);
			this.handleRecentItemAction = this.handleRecentItemAction.bind(this);
			this.handleRecentAdd = this.handleRecentAdd.bind(this);
		}

		get detailCard()
		{
			return this.props.detailCard;
		}

		get eventEmitter()
		{
			return this.detailCard.customEventEmitter;
		}

		get provider()
		{
			return this.props.provider;
		}

		get analyticsLabel()
		{
			return this.detailCard.getEntityAnalyticsData();
		}

		componentDidMount()
		{
			this.eventEmitter.on('DetailCard.FloatingMenu.Item::onRecentAction', this.handleRecentItemAction);
			this.eventEmitter.on('DetailCard.FloatingMenu.Item::onSaveInRecent', this.handleRecentAdd);

			if (isIOS && Feature.isKeyboardEventsSupported())
			{
				Keyboard.on(Keyboard.Event.WillShow, () => {
					if (this.floatingButtonRef)
					{
						this.floatingButtonRef.hide();
					}
				});
				Keyboard.on(Keyboard.Event.WillHide, () => {
					if (this.floatingButtonRef)
					{
						this.floatingButtonRef.show();
					}
				});
			}
		}

		componentWillUnmount()
		{
			this.eventEmitter.off('DetailCard.FloatingMenu.Item::onRecentAction', this.handleRecentItemAction);
			this.eventEmitter.off('DetailCard.FloatingMenu.Item::onSaveInRecent', this.handleRecentAdd);
		}

		/**
		 * @param {string} actionId
		 * @param {?string} tabId
		 * @return void
		 */
		handleRecentItemAction({ actionId, tabId = null })
		{
			const menu = this.getMenu();
			const menuItem = menu.getMenuItem(actionId, tabId);

			if (menuItem)
			{
				menu
					.closeContextMenu()
					.then(() => menuItem.execute())
					.then(() => AnalyticsLabel.send({
						...this.analyticsLabel,
						source: 'detail-card-recent-menu',
						entityTypeId: this.detailCard.getEntityTypeId(),
						tabId,
						actionId,
						position: menu.getRecentPosition(actionId, tabId),
					}))
				;
			}
		}

		/**
		 * @param {string} actionId
		 * @param {?string} tabId
		 * @return void
		 */
		handleRecentAdd({ actionId, tabId = null })
		{
			this.getMenu().onAddToRecent(actionId, tabId);
		}

		animateOnScroll(scrollParams, scrollViewHeight)
		{
			if (!this.isVisible())
			{
				return;
			}

			if (this.floatingButtonRef)
			{
				this.floatingButtonRef.animateOnScroll(scrollParams, scrollViewHeight);
			}
		}

		actualize()
		{
			if (!this.floatingButtonRef)
			{
				return;
			}

			if (this.isVisible())
			{
				this.floatingButtonRef.show();
			}
			else
			{
				this.floatingButtonRef.hide();
			}
		}

		render()
		{
			this.menu = null;

			return new UI.FloatingButtonComponent({
				testId: 'detail-card-floating-button',
				position: this.getPosition(),
				ref: (ref) => this.floatingButtonRef = ref,
				onClick: this.handleOnClick,
				onLongClick: this.handleOnLongClick,
			});
		}

		isVisible()
		{
			return this.detailCard.hasEntityModel() && this.getItemsToShow().length;
		}

		getPosition()
		{
			if (this.isVisible())
			{
				return null;
			}

			return { bottom: -100 };
		}

		handleOnClick()
		{
			const activeTabMenuItem = this.getMenu().getActiveTabMenuItem();
			if (activeTabMenuItem)
			{
				void activeTabMenuItem.execute();
			}
			else
			{
				void this.showMenu();
			}
		}

		handleOnLongClick()
		{
			void this.showMenu();
		}

		showMenu()
		{
			return this.getMenu().showContextMenu();
		}

		/**
		 * @return {FloatingButtonMenu}
		 */
		getMenu()
		{
			if (this.menu === null)
			{
				this.menu = new FloatingButtonMenu({
					detailCard: this.detailCard,
					items: this.getItems(),
					useRecent: true,
				});
			}

			return this.menu;
		}

		/**
		 * @return {FloatingMenuItem[]}
		 */
		getItems()
		{
			const items = [
				...this.getTabItems(),
				...this.getProviderItems(),
			];

			items.forEach((item) => item.setDetailCard(this.detailCard));

			return items;
		}

		getItemsToShow()
		{
			return this.getItems().filter((item) => item.isAvailable());
		}

		/**
		 * @private
		 * @return {FloatingMenuItem[]}
		 */
		getTabItems()
		{
			const items = [];

			this.detailCard.tabRefMap.forEach((tabRef) => {
				items.push(...tabRef.getFloatingMenuItems());
			});

			return items;
		}

		/**
		 * @private
		 * @return {FloatingMenuItem[]}
		 */
		getProviderItems()
		{
			if (this.provider)
			{
				return this.provider(this.detailCard);
			}

			return [];
		}

		showMenuInTab(activeTabHandler)
		{
			return (
				activeTabHandler()
					.then(({ closeCallback }) => closeCallback && closeCallback())
			);
		}
	}

	module.exports = { FloatingButton };
});
