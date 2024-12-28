/**
 * @module im/messenger/view/recent
 */
jn.define('im/messenger/view/recent', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const AppTheme = require('apptheme');
	const { Runtime } = require('runtime');
	const { Loc } = require('loc');

	const { openIntranetInviteWidget } = require('intranet/invite-opener-new');

	const {
		EventType,
		FeatureFlag,
		ComponentCode,
		ActionByUserType,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { UserPermission } = require('im/messenger/lib/permission-manager');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('recent--view');

	const { View } = require('im/messenger/view/base');

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

			this.bindMethods();
			this.subscribeEvents();
			this.initTopMenu();
			this.initSections();
			this.renderChatCreateButton();
		}

		bindMethods()
		{
			this.itemWillDisplayHandler = this.itemWillDisplayHandler.bind(this);
			this.showSearchBarButtonTapHandler = this.showSearchBarButtonTapHandler.bind(this);
			this.createChatButtonTapHandler = this.createChatButtonTapHandler.bind(this);
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
				this.ui.on(EventType.recent.itemWillDisplay, this.itemWillDisplayHandler);
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
				topMenuButtons.push(
					{
						id: 'developer-menu',
						title: 'Developer menu',
						sectionCode: 'general',
						iconName: 'start',
					},
					{
						id: 'developer-reload',
						title: 'reload();',
						sectionCode: 'general',
					},
				);
			}

			const topMenuButtonHandler = (event, item) => {
				if (event === 'onItemSelected' && item.id === 'readAll')
				{
					this.emitCustomEvent(EventType.recent.readAll);

					return;
				}

				if (FeatureFlag.isDevelopmentEnvironment && event === 'onItemSelected')
				{
					if (item.id === 'developer-menu')
					{
						showMessengerDeveloperMenu();
					}

					if (item.id === 'developer-reload')
					{
						reload();
					}
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
					callback: this.showSearchBarButtonTapHandler,
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

		renderChatCreateButton()
		{
			if (!this.checkShouldRenderChatCreateButton())
			{
				return;
			}

			this.setFloatingButton(this.getChatCreateButtonOption());
		}

		renderChatCreateButtonForWelcomeScreen()
		{
			if (!this.checkShouldRenderChatCreateButton())
			{
				return;
			}

			this.setFloatingButton(this.getChatCreateButtonForWelcomeScreenOption());
		}

		checkShouldRenderChatCreateButton()
		{
			if (
				this.isCollabComponent()
				&& (
					!Feature.isCollabCreationAvailable
					|| !UserPermission.canPerformActionByUserType(ActionByUserType.createCollab)
				)
			)
			{
				return false;
			}

			if (
				this.isChannelComponent()
				&& !UserPermission.canPerformActionByUserType(ActionByUserType.createChannel)
			)
			{
				return false;
			}

			const userCanNotCreateChats = (
				!UserPermission.canPerformActionByUserType(ActionByUserType.createChat)
				&& !UserPermission.canPerformActionByUserType(ActionByUserType.createChannel)
				&& !UserPermission.canPerformActionByUserType(ActionByUserType.createCollab)
			);
			if (this.isMessengerComponent() && userCanNotCreateChats)
			{
				return false;
			}

			return true;
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

		isCollabComponent()
		{
			const componentCode = MessengerParams.getComponentCode();

			return componentCode === ComponentCode.imCollabMessenger;
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
				callback: this.createChatButtonTapHandler,
				icon: this.style.icon,
				animation: 'hide_on_scroll',
				color: this.style.chatCreateButtonColor,
				showLoader: false,
				accentByDefault: false,
			};
		}

		getChatCreateButtonForWelcomeScreenOption()
		{
			const button = this.getChatCreateButtonOption();
			button.accentByDefault = true;

			return button;
		}

		itemWillDisplayHandler(item)
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

		showSearchBarButtonTapHandler()
		{
			this.ui.showSearchBar();
		}

		setItems(items)
		{
			logger.log(`${this.constructor.name}.setItems`, items);

			this.ui.setItems(items);
			items.forEach((item) => {
				this.itemCollection[item.id] = item;
			});
		}

		addItems(items)
		{
			logger.log(`${this.constructor.name}.addItems`, items);

			this.ui.addItems(items, true);
			items.forEach((item) => {
				this.itemCollection[item.id] = item;
			});
		}

		updateItems(items)
		{
			logger.log(`${this.constructor.name}.updateItems`, items);

			this.ui.updateItems(items);
			items.forEach((item) => {
				if (!this.itemCollection[item.element.id])
				{
					logger.error(`${this.constructor.name}.updateItems: updating item not found`, item.element.id);

					return;
				}
				this.itemCollection[item.element.id] = item.element;
			});
		}

		updateItem(filter, fields)
		{
			logger.log(`${this.constructor.name}.updateItem`, filter, fields);

			this.ui.updateItem(filter, fields);
			if (!this.itemCollection[fields.id])
			{
				logger.error(`${this.constructor.name}.updateItem: updating item not found`, fields.id);

				return;
			}
			this.itemCollection[fields.id] = fields;
		}

		removeItem(itemFilter)
		{
			logger.log(`${this.constructor.name}.removeItem`, itemFilter);

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
					listener: this.createChatButtonTapHandler,
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

			if (this.isCollabComponent())
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_COLLAB_UPPER_TEXT'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_COLLAB_LOWER_TEXT'),
					iconName: 'ws_collabs',
				};
			}

			this.ui.welcomeScreen.show(options);
			this.renderChatCreateButtonForWelcomeScreen();
		}

		hideWelcomeScreen()
		{
			this.ui.welcomeScreen.hide();
			this.renderChatCreateButton();
		}

		createChatButtonTapHandler()
		{
			this.emitCustomEvent(EventType.recent.createChat);
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
