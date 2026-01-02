/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/content', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { isEmpty } = require('utils/object');
	const { DialogType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-service');
	const { ParticipantType } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/const');
	const {
		SidebarBaseTabListContent,
		SidebarTabListItemModel,
	} = require('im/messenger/controller/sidebar-v2/tabs/base');

	const { menuFactory } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/factories/menu-factory');
	const { itemFactory } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/factories/item-factory');
	const { modelFactory } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/factories/model-factory');

	/**
	 * @class SidebarParticipantsTabContent
	 */
	class SidebarParticipantsTabContent extends SidebarBaseTabListContent
	{
		/**
		 * @param {ParticipantsMenuProps} params
		 * @returns {ParticipantsBaseMenu}
		 */
		createMenu(params)
		{
			if (!params.userId)
			{
				return null;
			}

			return menuFactory(params);
		}

		bindMethods()
		{
			this.onStoreSetSidebarParticipants = this.onStoreSetSidebarParticipants.bind(this);
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.storeManager.on('dialoguesModel/update', this.onStoreSetSidebarParticipants);
			this.storeManager.on('dialoguesModel/copilotModel/update', this.onStoreSetSidebarParticipants);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.storeManager.off('dialoguesModel/update', this.onStoreSetSidebarParticipants);
			this.storeManager.off('dialoguesModel/copilotModel/update', this.onStoreSetSidebarParticipants);
		}

		onStoreManagersUpdate(managerList)
		{
			if (!Array.isArray(managerList) || isEmpty(managerList))
			{
				return;
			}

			const currentItems = this.getItems();
			const currentManagerIds = new Set(
				currentItems
					.filter((item) => item.getData().isManager)
					.map((item) => item.getId()),
			);

			const managerListSet = new Set(managerList);

			const newManagerItems = managerList
				.filter((userId) => !currentManagerIds.has(userId))
				.map((userId) => this.createListItemModel(userId));

			const removedManagerItems = currentItems
				.filter((item) => item.getData().isManager && !managerListSet.has(item.getId()))
				.map((item) => this.createListItemModel(item.getId()));

			const updateItems = [...newManagerItems, ...removedManagerItems];

			void this.updateRows(updateItems);
			this.updateMenu(updateItems);
		}

		updateMenu(items)
		{
			if (!Array.isArray(items) || isEmpty(items))
			{
				return;
			}

			items.forEach((item) => {
				const menu = this.createMenu({
					dialogId: this.getDialogId(),
					...item.getData(),
				});
				const menuRef = this.getMenuRefByItemId(item.getId());
				menuRef?.setProvider(menu.getActions);
			});
		}

		onStoreSetSidebarParticipants({ payload } = {})
		{
			if (String(payload.data?.dialogId) !== String(this.getDialogId()))
			{
				return;
			}

			if (payload.actionName === 'updateManagerList')
			{
				this.onStoreManagersUpdate(payload.data?.fields?.managerList);

				return;
			}

			this.onStoreItemsUpdated();
		}

		getEmptyScreenProps()
		{
			return {
				testId: 'participants',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_EMPTY_SCREEN_TITLE'),
			};
		}

		renderItem(item)
		{
			const itemContentParams = {
				...item.data,
				dialogId: this.getDialogId(),
			};

			const menu = this.createMenu(itemContentParams);

			if (menu?.shouldShowMenu())
			{
				itemContentParams.onShowMenu = this.handleOnShowMenu({ menu, item });
			}

			return itemFactory(itemContentParams);
		}

		getListViewData()
		{
			const items = [];
			const addParticipantsEnabled = this.dialogLocator.get('configurator')?.config?.sidebar?.params?.addParticipantsEnabled ?? true;
			if (this.permissionManager.canAddParticipants() && addParticipantsEnabled)
			{
				items.push(new SidebarTabListItemModel(this.getButton()));
			}

			items.push(...this.getItems());

			return [{ items: items.map((item) => item.toListView()) }];
		}

		getButton()
		{
			return {
				id: ParticipantType.button,
				type: ParticipantType.button,
			};
		}

		getItemsFromStore()
		{
			const {
				type,
				dialogId,
				participants,
				hasNextPage = true,
			} = this.getDialogModel();

			const result = {
				items: null,
				hasNextPage,
			};

			if (!Type.isArray(participants) || isEmpty(participants))
			{
				return result;
			}

			if (this.isGroupDialog())
			{
				result.items = this.convertToSortedList(participants);
			}
			else if (type === DialogType.user)
			{
				result.items = this.convertToSortedList([
					Number(dialogId),
					this.getCurrentUserId(),
				]);
			}

			return result;
		}

		getDialogId()
		{
			const { dialogId } = this.getDialog();

			return dialogId;
		}

		getDialog()
		{
			const { dialog } = this.props;

			return dialog;
		}

		getCurrentUserId()
		{
			return MessengerParams.getUserId();
		}

		isGroupDialog()
		{
			return DialogHelper.isDialogId(this.getDialogId());
		}

		convertToSortedList(participants)
		{
			return participants.map((userId) => this.createListItemModel(userId));
		}

		createListItemModel(userId)
		{
			const model = modelFactory({
				userId,
				dialogId: this.getDialogId(),
			});

			return new SidebarTabListItemModel(model.getData());
		}

		handleOnShowMenu = ({ menu, item }) => ({ ref }) => {
			this.showItemContextMenu(ref, item.id, menu?.getActions);
		};

		getDialogModel()
		{
			return this.store.getters['dialoguesModel/getById'](this.getDialogId());
		}
	}

	module.exports = { SidebarParticipantsTabContent };
});
