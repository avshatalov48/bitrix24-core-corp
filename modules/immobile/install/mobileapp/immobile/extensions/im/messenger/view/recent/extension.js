/**
 * @module im/messenger/view/recent
 */
jn.define('im/messenger/view/recent', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Runtime } = require('runtime');

	const { View } = require('im/messenger/view/base');
	const { EventType, FeatureFlag, ComponentCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const AppTheme = require('apptheme');
	const { openIntranetInviteWidget } = require('intranet/invite-opener-new');
	const { AnalyticsEvent } = require('analytics');

	class RecentView extends View
	{
		constructor(options = {})
		{
			super(options);

			this.setCustomEvents([
				EventType.recent.createChat,
				EventType.recent.readAll,
				EventType.recent.loadNextPage,
			]);

			this.loaderShown = false;
			this.loadNextPageItemId = 'loadNextPage';
			this.itemCollection = {};
			this.style = {
				chatCreateButtonColor: AppTheme.colors.accentBrandBlue,
				icon: 'plus',
				showLoader: false,
				...options.style,
			};

			this.subscribeEvents();
			this.initTopMenu();
			this.initSections();
			this.initChatCreateButton();
		}

		get isLoaderShown()
		{
			return this.loaderShown;
		}

		subscribeEvents()
		{
			this.ui.on(EventType.recent.scroll, Runtime.throttle(this.onScroll, 50, this));

			if (FeatureFlag.list.itemWillDisplaySupported)
			{
				this.ui.on(EventType.recent.itemWillDisplay, this.onItemWillDisplay.bind(this));
			}
			else
			{
				this.ui.on(EventType.recent.scroll, Runtime.debounce(this.onScroll, 50, this));
			}
		}

		initTopMenu()
		{
			const topMenuPopup = dialogs.createPopupMenu();
			const topMenuButtons = [];

			if (this.isMessengerComponent())
			{
				topMenuButtons.push(
					{
						id: 'readAll',
						title: Loc.getMessage('IMMOBILE_RECENT_VIEW_READ_ALL'),
						sectionCode: 'general',
						iconName: 'read',
					},
				);
			}

			if (FeatureFlag.isDevelopmentEnvironment)
			{
				topMenuButtons.push({
					id: 'developer-menu',
					title: 'Developer menu',
					sectionCode: 'general',
					iconName: 'start',
				});
			}

			const topMenuButtonHandler = (event, item) => {
				if (event === 'onItemSelected' && item.id === 'readAll')
				{
					this.emitCustomEvent(EventType.recent.readAll);

					return;
				}

				if (
					FeatureFlag.isDevelopmentEnvironment
					&& event === 'onItemSelected'
					&& item.id === 'developer-menu'
				)
				{
					showMessengerDeveloperMenu();
				}
			};

			const buttons = [];
			if (topMenuButtons.length > 0)
			{
				topMenuPopup.setData(topMenuButtons, [{ id: 'general' }], topMenuButtonHandler);
				buttons.push(
					{
						type: 'more',
						callback: () => topMenuPopup.show(),
					},
				);
			}

			if (this.isMessengerComponent())
			{
				buttons.unshift({
					type: 'search',
					callback: this.showSearchBar.bind(this),
				});
			}

			this.setRightButtons(buttons);
		}

		initSections()
		{
			this.setSections([
				{
					title: '',
					id: 'call',
					backgroundColor: '#ffffff',
					sortItemParams: { order: 'desc' },
				},
				{
					title: '',
					id: 'pinned',
					backgroundColor: '#ffffff',
					sortItemParams: { order: 'desc' },
				},
				{
					title: '',
					id: 'general',
					backgroundColor: '#ffffff',
					sortItemParams: { order: 'desc' },
				},
			]);
		}

		initChatCreateButton()
		{
			this.setFloatingButton(this.getChatCreateButtonOption());
		}

		isCopilotComponent()
		{
			const componentCode = MessengerParams.getComponentCode();

			return componentCode === ComponentCode.imCopilotMessenger;
		}

		isChannelComponent()
		{
			const componentCode = MessengerParams.getComponentCode();

			return componentCode === ComponentCode.imChannelMessenger;
		}

		isMessengerComponent()
		{
			const componentCode = MessengerParams.getComponentCode();

			return componentCode === ComponentCode.imMessenger;
		}

		getChatCreateButtonOption()
		{
			return {
				type: 'plus',
				callback: this.sendCreateChatEvent.bind(this),
				icon: this.style.icon,
				animation: 'hide_on_scroll',
				color: this.style.chatCreateButtonColor,
				showLoader: false,
			};
		}

		onItemWillDisplay(item)
		{
			if (item.id === this.loadNextPageItemId)
			{
				this.emitCustomEvent(EventType.recent.loadNextPage);
			}
		}

		onScroll(event)
		{
			if (event.offset.y >= event.contentSize.height * 0.8)
			{
				this.emitCustomEvent(EventType.recent.loadNextPage);
			}
		}

		setFloatingButton(floatingButton)
		{
			this.ui.setFloatingButton(floatingButton);
		}

		setRightButtons(buttonList)
		{
			this.ui.setRightButtons(buttonList);
		}

		setSections(sectionList)
		{
			this.ui.setSections(sectionList);
		}

		showSearchBar()
		{
			this.ui.showSearchBar();
		}

		setItems(items)
		{
			this.ui.setItems(items);
			items.forEach((item) => {
				this.itemCollection[item.id] = item;
			});
		}

		addItems(items)
		{
			this.ui.addItems(items, true);
			items.forEach((item) => {
				this.itemCollection[item.id] = item;
			});
		}

		updateItems(items)
		{
			this.ui.updateItems(items);
			items.forEach((item) => {
				this.itemCollection[item.element.id] = item.element;
			});
		}

		updateItem(filter, fields)
		{
			this.ui.updateItem(filter, fields);
			this.itemCollection[fields.id] = fields;
		}

		removeItem(itemFilter)
		{
			this.ui.removeItem(itemFilter);
			if (itemFilter.id)
			{
				delete this.itemCollection[itemFilter.id];
			}
		}

		findItem(filter, callback)
		{
			this.ui.findItem(filter, callback);
		}

		getItem(id)
		{
			return this.itemCollection[id];
		}

		stopRefreshing()
		{
			this.ui.stopRefreshing();
		}

		showLoader()
		{
			const loader = {
				id: this.loadNextPageItemId,
				title: Loc.getMessage('IMMOBILE_RECENT_VIEW_ITEMS_LOADING'),
				type: 'loading',
				unselectable: true,
				params: {
					disableTap: true,
				},
				sectionCode: 'general',
			};

			this.addItems([loader]);
			this.loaderShown = true;
		}

		hideLoader()
		{
			if (this.loaderShown)
			{
				this.removeItem({ id: this.loadNextPageItemId });
				this.loaderShown = false;
			}
		}

		showWelcomeScreen()
		{
			let options;

			if (MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false))
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_1'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_INVITE'),
					iconName: 'ws_employees',
					listener: () => openIntranetInviteWidget({
						analytics: new AnalyticsEvent().setSection('chat'),
					}),
				};
			}
			else
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_1'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_CREATE'),
					iconName: 'ws_employees',
					listener: this.sendCreateChatEvent.bind(this),
				};
			}

			options.startChatButton = {
				text: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_EMPTY_BUTTON'),
				iconName: 'ws_plus',
			};

			if (this.isCopilotComponent())
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_COPILOT_UPPER_TEXT'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_COPILOT_LOWER_TEXT'),
					iconName: 'ws_copilot',
				};
			}

			if (this.isChannelComponent())
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_CHANNEL_UPPER_TEXT'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_CHANNEL_LOWER_TEXT'),
					iconName: 'ws_channels',
				};
			}

			this.ui.welcomeScreen.show(options);
		}

		hideWelcomeScreen()
		{
			this.ui.welcomeScreen.hide();
		}

		sendCreateChatEvent()
		{
			this.emitCustomEvent(EventType.recent.createChat);

			// if (this.style.showLoader) disable, because available copilot role control
			// {
			// 	const chatCreateButton = this.getChatCreateButtonOption();
			// 	chatCreateButton.icon = Application.getPlatform() === 'ios' ? null : this.style.icon;
			// 	chatCreateButton.showLoader = this.style.showLoader;
			//
			// 	this.setFloatingButton(chatCreateButton);
			// }
		}
	}

	window.showMessengerDeveloperMenu = () => {
		jn.import('im:messenger/lib/dev/menu')
			.then(() => {
				const { showDeveloperMenu } = require('im/messenger/lib/dev/menu');
				showDeveloperMenu();
			})
			.catch((error) => {
				// eslint-disable-next-line no-console
				console.error(error);
			})
		;
	};

	module.exports = {
		RecentView,
	};
});
