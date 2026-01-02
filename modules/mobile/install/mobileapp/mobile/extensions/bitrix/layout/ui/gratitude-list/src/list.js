/**
 * @module layout/ui/gratitude-list/src/list
 */
jn.define('layout/ui/gratitude-list/src/list', (require, exports, module) => {
	const { StatefulList } = require('layout/ui/stateful-list');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { ListItemsFactory, ListItemType } = require('layout/ui/gratitude-list/src/factory');
	const { fetchUsersIfNotLoaded } = require('statemanager/redux/slices/users/thunk');
	const {
		gratitudesUpserted,
		gratitudeUpserted,
		gratitudeRemoved,
		selectGratitudeByPostId,
	} = require('statemanager/redux/slices/gratitude');
	const { dispatch } = require('statemanager/redux/store');
	const { inAppUrl } = require('in-app-url');
	const { isModuleInstalled } = require('module');
	const { GratitudeIcon } = require('assets/icons');
	const { createTestIdGenerator } = require('utils/test');
	const { PropTypes } = require('utils/validation');
	const store = require('statemanager/redux/store');

	const ITEMS_LOAD_LIMIT = 30;
	const POST_FORM_DATA = {
		attachmentArrowRightIcon: '/bitrix/js/mobile/images/postform/icon_arrow_right.svg',
		attachmentCloseIcon: '/bitrix/js/mobile/images/postform/icon_close.png',
		attachmentFileIconFolder: '/bitrix/js/mobile/images/postform/file/',
		backgroundIcon: '/bitrix/js/mobile/images/postform/icon_background.png',
		keyboardEllipsisIcon: '/bitrix/js/mobile/images/postform/icon_keyboard_ellipsis.svg',
		menuDeleteIcon: '/bitrix/js/mobile/images/postform/icon_menu_delete.png',
		menuDownIcon: '/bitrix/js/mobile/images/postform/icon_menu_down.png',
		menuDownIconDisabled: '/bitrix/js/mobile/images/postform/icon_menu_down_disabled.png',
		menuMedalIcon: '/bitrix/js/mobile/images/postform/icon_menu_medal.png',
		menuMultiCheckIcon: '/bitrix/js/mobile/images/postform/icon_menu_multicheck.png',
		menuPlusIcon: '/bitrix/js/mobile/images/postform/icon_menu_plus.png',
		menuUpIcon: '/bitrix/js/mobile/images/postform/icon_menu_up.png',
		menuUpIconDisabled: '/bitrix/js/mobile/images/postform/icon_menu_up_disabled.png',
		titleIcon: '/bitrix/js/mobile/images/postform/icon_title.png',
		userAvatar: '/bitrix/js/mobile/images/postform/avatar/user.png',
	};

	/**
	 * @typedef {Object} GratitudeListProps
	 * @property {Object} layout
	 * @property {number} ownerId
	 */
	class GratitudeList extends LayoutComponent
	{
		/**
		 * @param {GratitudeListProps} props
		 */
		constructor(props)
		{
			super(props);

			this.handleItemAddEventBound = this.handleItemAddEvent.bind(this);
			this.handleItemUpdateEventBound = this.handleItemUpdateEvent.bind(this);
			this.handleItemDeleteEventBound = this.handleItemDeleteEvent.bind(this);

			this.statefulListRef = null;
			this.getTestId = createTestIdGenerator({
				prefix: 'gratitude-list',
			});
		}

		componentDidMount()
		{
			BX.addCustomEvent('GratitudeList::onItemAdd', this.handleItemAddEventBound);
			BX.addCustomEvent('GratitudeList::onItemUpdate', this.handleItemUpdateEventBound);
			BX.addCustomEvent('GratitudeList::onItemDeleted', this.handleItemDeleteEventBound);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('GratitudeList::onItemAdd', this.handleItemAddEventBound);
			BX.removeCustomEvent('GratitudeList::onItemUpdate', this.handleItemUpdateEventBound);
			BX.removeCustomEvent('GratitudeList::onItemDeleted', this.handleItemDeleteEventBound);
		}

		/**
		 * @returns {Object}
		 */
		get #layout()
		{
			return this.props.layout;
		}

		/**
		 * @returns {number}
		 */
		get #ownerId()
		{
			return this.props.ownerId;
		}

		async handleItemAddEvent(item)
		{
			const ownerIds = item?.GRATITUDE_EMPLOYEES;
			if (item && ownerIds?.includes(this.#ownerId))
			{
				const gratitude = this.#prepareGratitudeForRedux(item);

				this.statefulListRef?.updateItemsData([gratitude]);

				await dispatch(gratitudeUpserted(gratitude));
			}
		}

		async handleItemUpdateEvent(item)
		{
			const ownerIds = item?.GRATITUDE_EMPLOYEES;
			if (item && ownerIds?.includes(this.#ownerId) && item.post_id)
			{
				const oldGratitude = selectGratitudeByPostId(store.getState(), item.post_id);

				if (!oldGratitude)
				{
					return;
				}

				await this.statefulListRef?.deleteItem([oldGratitude.id]);
				await dispatch(gratitudeRemoved(oldGratitude.id));

				const newGratitude = this.#prepareGratitudeForRedux(item, oldGratitude);

				this.statefulListRef?.updateItemsData([newGratitude]);
				await dispatch(gratitudeUpserted(newGratitude));
			}
		}

		async handleItemDeleteEvent(item)
		{
			if (item && item.post_id)
			{
				const gratitudeToRemove = selectGratitudeByPostId(store.getState(), item.post_id);

				await this.statefulListRef?.deleteItem([gratitudeToRemove.id]);

				await dispatch(gratitudeRemoved(gratitudeToRemove.id));
			}
		}

		#prepareGratitudeForRedux(item, gratitude = {})
		{
			const medalName = GratitudeIcon.getNameByFeedId(item.GRATITUDE_MEDAL);

			return {
				id: item.postFields?.UF_GRATITUDE,
				authorId: Number(env.userId),
				createdAt: gratitude.createdAt ?? Math.floor(Date.now() / 1000),
				name: medalName,
				ownerId: this.#ownerId,
				relatedPostId: Number(item.post_id),
				title: item.POST_MESSAGE,
			};
		}

		bindRef = (ref) => {
			if (ref)
			{
				this.statefulListRef = ref;
			}
		};

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderList(),
			);
		}

		renderList()
		{
			return new StatefulList({
				testId: this.getTestId(),
				layout: this.#layout,
				showAirStyle: true,
				jsonEnabled: true,
				itemsLoadLimit: ITEMS_LOAD_LIMIT,
				ref: this.bindRef,
				actions: this.getListActions(),
				actionParams: this.getListActionParams(),
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				itemType: ListItemType.GRATITUDE,
				itemFactory: ListItemsFactory,
				onBeforeItemsRender: this.onBeforeItemsRender,
				isShowFloatingButton: true,
				isFloatingButtonAccent: true,
				onFloatingButtonClick: this.onFloatingButtonClick,
				onFloatingButtonLongClick: this.onFloatingButtonClick,
				itemDetailOpenHandler: this.itemDetailOpenHandler,
				getEmptyListComponent: this.getEmptyListComponent,
			});
		}

		getListActions()
		{
			return {
				loadItems: 'mobile.Profile.getGratitudeList',
			};
		}

		getListActionParams()
		{
			return {
				loadItems: {
					ownerId: this.#ownerId,
				},
			};
		}

		onItemsLoaded = async (responseData, context) => {
			const { authorIds, items } = responseData || {};

			if (items && items.length > 0)
			{
				await dispatch(gratitudesUpserted(items));
			}

			if (authorIds && authorIds.length > 0)
			{
				await dispatch(fetchUsersIfNotLoaded({ userIds: authorIds }));
			}
		};

		onBeforeItemsRender = (items) => {
			return items.map((item, index) => ({
				...item,
				testId: this.getTestId(`item-${index}`),
				showBorder: index > 0,
			}));
		};

		onFloatingButtonClick = () => {
			void PageManager.openComponent('JSStackComponent', {
				name: 'JSStackComponent',
				componentCode: 'livefeed.postform',
				canOpenInDefault: true,
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['livefeed.postform'].publicUrl,
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'postFormLayoutWidget',
						useLargeTitleMode: true,
						titleParams: {
							text: '',
							type: 'section',
						},
						modal: true,
					},
				},
				params: {
					SERVER_NAME: currentDomain,
					MEDALS_LIST: GratitudeIcon.getFeedList(),
					DESTINATION_LIST: {},
					DESTINATION_TO_ALL_DENY: false,
					DESTINATION_TO_ALL_DEFAULT: true,
					MODULE_DISK_INSTALLED: isModuleInstalled('disk') ? 'Y' : 'N',
					MODULE_WEBDAV_INSTALLED: isModuleInstalled('webdav') ? 'Y' : 'N',
					MODULE_VOTE_INSTALLED: 'N',
					USE_IMPORTANT: 'N',
					BACKGROUND_IMAGES_DATA: {},
					BACKGROUND_COMMON: {
						url: '/bitrix/templates/mobile_app/images/lenta/medal/13_cup/background_mobile_mono.svg',
					},
					POST_FORM_DATA,
					DEFAULT_OPEN_MENU_ITEM: 'gratitude',
				},
			});
		};

		itemDetailOpenHandler = (postId) => {
			if (!this.#ownerId || !postId)
			{
				console.warn(
					'GratitudeList: itemDetailOpenHandler: postId or ownerId is not defined',
					this.#ownerId,
					postId,
				);

				return;
			}

			inAppUrl.open(`${currentDomain}/company/personal/user/${this.#ownerId}/blog/${postId}/`);
		};

		getEmptyListComponent()
		{
			const statusBlockProps = {
				testId: 'status-block',
				image: Image({
					testId: 'image',
					style: {
						width: 128,
						height: 118,
					},
					uri: makeLibraryImagePath('graphics.png', 'gratitude'),
				}),
				title: Loc.getMessage('M_UI_GRATITUDE_LIST_EMPTY_TITLE'),
				titleColor: Color.base3,
				emptyScreen: true,
				preventRefresh: true,
			};

			return StatusBlock(statusBlockProps);
		}
	}

	GratitudeList.propTypes = {
		layout: PropTypes.object.isRequired,
		ownerId: PropTypes.number.isRequired,
	};

	module.exports = {
		GratitudeList,
	};
});
