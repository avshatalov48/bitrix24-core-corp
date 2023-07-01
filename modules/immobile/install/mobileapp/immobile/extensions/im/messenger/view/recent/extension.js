/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/view/recent
 */
jn.define('im/messenger/view/recent', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Runtime } = require('runtime');
	const { View } = require('im/messenger/view/base');
	const { EventType, FeatureFlag } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');

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

			this._isLoaderShown = false;
			this.loadNextPageItemId = 'loadNextPage';

			this.subscribeEvents();
			this.initTopMenu();
			this.initSections();
			this.initChatCreateButton();

			IntranetInvite.init();
		}

		get isLoaderShown()
		{
			return this._isLoaderShown;
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

			const topMenuButtons = [
				{
					id: 'readAll',
					title: Loc.getMessage('IMMOBILE_RECENT_VIEW_READ_ALL'),
					sectionCode: 'general',
					iconName: 'read'
				},
			];

			if (FeatureFlag.isDevelopmentEnvironment)
			{
				topMenuButtons.push([
					{
						id: 'developer-menu',
						title: 'Developer menu',
						sectionCode: 'general',
						iconName: 'start',
					},
				]);
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
					jn.import('im:messenger/lib/dev')
						.then(()=>{
							const { showDeveloperMenu } = require('im/messenger/lib/dev');
							showDeveloperMenu();
						})
						.catch((error) => {
							console.error(error)
						})
					;
				}
			};

			topMenuPopup.setData(topMenuButtons, [{ id: 'general' }], topMenuButtonHandler);

			this.setRightButtons([
				{
					type: 'search',
					callback: this.showSearchBar.bind(this),
				},
				{
					type: 'more',
					callback: () => topMenuPopup.show(),
				},
			]);
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
			const chatCreateButton = {
				type: 'plus',
				callback: this.sendCreateChatEvent.bind(this),
				icon: 'plus',
				animation: 'hide_on_scroll',
				color: '#60C7EF',
			};

			this.setFloatingButton(chatCreateButton);
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
		}

		addItems(items)
		{
			this.ui.addItems(items, true);
		}

		updateItems(items)
		{
			this.ui.updateItems(items);
		}

		updateItem(filter, fields)
		{
			this.ui.updateItem(filter, fields);
		}

		removeItem(itemFilter)
		{
			this.ui.removeItem(itemFilter);
		}

		findItem(filter, callback)
		{
			this.ui.findItem(filter, callback);
		}

		stopRefreshing()
		{
			this.ui.stopRefreshing();
		}

		showLoader()
		{
			const loader = {
				id: this.loadNextPageItemId,
				title: Loc.getMessage('IMMOBILE_DIALOG_LIST_ITEMS_LOADING'),
				type: 'loading',
				unselectable: true,
				params: {
					disableTap: true,
				},
				sectionCode: 'general'
			};

			this.addItems([loader]);
			this._isLoaderShown = true;
		}

		hideLoader()
		{
			this.removeItem({id: this.loadNextPageItemId});
			this._isLoaderShown = false;
		}

		showWelcomeScreen()
		{
			let options;

			if (MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false))
			{
				options = {
					'upperText': Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_1'),
					'lowerText': Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_INVITE'),
					'iconName': 'ws_employees',
					'listener': () => IntranetInvite.openRegisterSlider({
						originator: 'im.messenger',
						registerUrl: MessengerParams.get('INTRANET_INVITATION_REGISTER_URL', ''),
						rootStructureSectionId: MessengerParams.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
						adminConfirm: MessengerParams.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
						disableAdminConfirm: MessengerParams.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
						sharingMessage: MessengerParams.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', ''),
					}),
				};
			}
			else
			{
				options = {
					'upperText': Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_1'),
					'lowerText': Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_TEXT_CREATE'),
					'iconName': 'ws_employees',
					'listener': this.sendCreateChatEvent.bind(this),
				};
			}

			options['startChatButton'] = {
				'text': Loc.getMessage('IMMOBILE_RECENT_VIEW_EMPTY_EMPTY_BUTTON'),
				'iconName': 'ws_plus',
			};

			this.ui.welcomeScreen.show(options);
		}

		hideWelcomeScreen()
		{
			this.ui.welcomeScreen.hide();
		}

		sendCreateChatEvent()
		{
			this.emitCustomEvent(EventType.recent.createChat);
		}
	}

	module.exports = {
		RecentView,
	};
});
