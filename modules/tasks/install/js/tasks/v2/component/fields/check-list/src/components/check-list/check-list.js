import { Dom, Event, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';

import { mapActions, mapGetters } from 'ui.vue3.vuex';
import type { BitrixVueComponentProps } from 'ui.vue3';
import { Item } from 'ui.entity-selector';
import type { MenuItemOptions, MenuOptions } from 'ui.system.menu';
import { Button as UiButton, AirButtonStyle, ButtonSize, ButtonIcon } from 'ui.vue3.components.button';
import { BIcon } from 'ui.icon-set.api.vue';
import { BMenu } from 'ui.vue3.components.menu';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.animated';
import 'ui.icon-set.outline';

import { Model } from 'tasks.v2.const';
import { participantMeta } from 'tasks.v2.component.elements.participant-list';
import { EntitySelectorDialog } from 'tasks.v2.lib.entity-selector-dialog';
import { checkListService } from 'tasks.v2.provider.service.check-list-service';
import { EntityTypes, FileService, fileService } from 'tasks.v2.provider.service.file-service';

import type { UserModel } from 'tasks.v2.model.users';
import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { CheckListStub } from './check-list-stub';
import { CheckListPopup } from '../check-list-popup/check-list-popup';
import { CheckListSheet } from '../check-list-sheet/check-list-sheet';
import { CheckListList } from '../check-list-list/check-list-list';
import { CheckListWidget } from '../check-list-widget/check-list-widget';
import { CheckListItemPanel } from '../check-list-item-panel/check-list-item-panel';

import { CheckListManager } from '../../lib/check-list-manager';
import { CheckListNotifier } from '../../lib/check-list-notifier';
import { Highlighter } from '../../lib/highlighter/highlighter';

import { PanelAction } from '../check-list-item-panel/check-list-item-panel-meta';

import './check-list.css';

const Context = Object.freeze({
	Sheet: 'sheet',
	Popup: 'popup',
	Preview: 'preview',
});

// @vue/component
export const CheckList = {
	name: 'TaskCheckList',
	components: {
		CheckListWidget,
		CheckListItemPanel,
		CheckListStub,
		UiButton,
		BIcon,
		BMenu,
	},
	provide(): Object
	{
		return {
			setItemsRef: this.setItemsRef,
			getItemsRef: this.getItemsRef,
		};
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isAutonomous: {
			type: Boolean,
			default: false,
		},
		isPreview: {
			type: Boolean,
			default: false,
		},
		isComponentShown: {
			type: Boolean,
			default: true,
		},
		checkListId: {
			type: [Number, String],
			default: 0,
		},
		isShown: {
			type: Boolean,
			default: false,
		},
		getBindElement: {
			type: Function,
			default: null,
		},
		getTargetContainer: {
			type: Function,
			default: null,
		},
	},
	emits: ['show', 'close', 'resize', 'open'],
	setup(): Object
	{
		return {
			resizeObserver: null,
			AirButtonStyle,
			ButtonSize,
			ButtonIcon,
			Outline,
		};
	},
	data(): Object
	{
		return {
			itemPanelIsShown: false,
			checkListWasUpdated: false,
			lastUpdatedCheckListId: 0,
			itemId: null,
			itemPanelStyles: {
				top: '0',
				display: 'flex',
			},
			isItemPanelFreeze: false,
			itemPanelTopOffset: this.isAutonomous ? 5 : 2,
			itemPanelTopLimit: this.isAutonomous ? 450 : 700,
			itemsRefs: {},
			isForwardMenuShown: false,
			forwardMenuSectionCode: 'createSection',
			forwardBindElement: null,
			shownPopups: new Set(),
			notifiers: new Map(),
		};
	},
	computed: {
		...mapGetters({
			deletingCheckListIds: `${Model.Interface}/deletingCheckListIds`,
			checkListCompletionCallback: `${Model.Interface}/checkListCompletionCallback`,
		}),
		componentName(): BitrixVueComponentProps
		{
			return {
				[Context.Sheet]: CheckListSheet,
				[Context.Popup]: CheckListPopup,
				[Context.Preview]: CheckListList,
			}[this.context];
		},
		context(): string
		{
			return {
				[true]: Context.Sheet,
				[this.isAutonomous]: Context.Popup,
				[this.isPreview]: Context.Preview,
			}.true;
		},
		contextClass(): string
		{
			return `--${this.context}`;
		},
		componentShown(): boolean
		{
			if (this.isPreview)
			{
				return this.isComponentShown;
			}

			return true;
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		canSaveCheckList(): boolean
		{
			return this.task.rights.checklistSave === true;
		},
		checkLists(): CheckListModel[]
		{
			return this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist);
		},
		parentCheckLists(): CheckListModel[]
		{
			return this.checkLists.filter((checkList: CheckListModel) => checkList.parentId === 0);
		},
		hasFewParentCheckLists(): boolean
		{
			return this.parentCheckLists.length > 1;
		},
		currentItem(): ?CheckListModel
		{
			return this.$store.getters[`${Model.CheckList}/getById`](this.itemId);
		},
		itemGroupModeSelected(): boolean
		{
			if (!this.currentItem)
			{
				return false;
			}

			return this.currentItem.groupMode?.selected === true;
		},
		forwardMenuOptions(): MenuOptions
		{
			return {
				id: `check-list-item-forward-menu-${this.currentItem.id}`,
				bindElement: this.forwardBindElement,
				maxWidth: 400,
				maxHeight: 300,
				offsetLeft: -110,
				sections: [
					{
						code: this.forwardMenuSectionCode,
					},
				],
				items: this.forwardMenuItems,
				targetContainer: document.body,
			};
		},
		forwardMenuItems(): MenuItemOptions[]
		{
			const checklistItems = this.parentCheckLists
				.filter((checkList: CheckListModel) => checkList.id !== this.currentItem.parentId)
				.map((checkList: CheckListModel) => ({
					title: checkList.title,
					dataset: {
						id: `ForwardMenuCheckList-${checkList.id}`,
					},
					onClick: () => {
						this.hideItemPanel();
						if (this.itemGroupModeSelected)
						{
							void this.forwardGroupItemsToChecklist(checkList.id);
						}
						else
						{
							this.forwardToChecklist(checkList.id);
						}
					},
				}));

			return [
				...checklistItems,
				{
					sectionCode: this.forwardMenuSectionCode,
					title: this.loc('TASKS_V2_CHECK_LIST_ITEM_FORWARD_MENU_CREATE'),
					dataset: {
						id: `ForwardMenuCreateNew-${this.currentItem.id}`,
					},
					onClick: this.forwardToNewChecklist.bind(this),
				},
			];
		},
		stub(): boolean
		{
			return this.checkLists.length === 0 || this.emptyList === true;
		},
		emptyList(): boolean
		{
			const siblings = this.parentCheckLists
				.filter((item: CheckListModel) => !this.deletingCheckListIds[item.id]);

			return siblings.length === 0;
		},
	},
	watch: {
		async titleFieldOffsetHeight(): Promise<void>
		{
			if (!this.$refs.popupComponent)
			{
				return;
			}

			await this.$nextTick();
			this.resize();
		},
		componentShown(value: boolean): void
		{
			if (!this.isPreview)
			{
				return;
			}

			this.executeCheckListCompletionCallbacks();

			void this.$nextTick(() => {
				if (value)
				{
					this.subscribeToEvents();

					if (
						this.checkListManager.getItem(this.checkListId)
						&& this.$refs.list
					)
					{
						const checkListNode = this.$refs.list.querySelector([`[data-id="${this.checkListId}"]`]);
						checkListNode?.scrollIntoView({
							block: 'center',
							behavior: 'instant',
						});
					}
				}
				else
				{
					this.unsubscribeFromEvents();
				}
			});
		},
		checkListWasUpdated(value: boolean): void
		{
			if (!this.currentItem)
			{
				return;
			}

			if (value === true)
			{
				const parentItem = this.checkListManager.getRootParentByChildId(this.currentItem.id);

				this.lastUpdatedCheckListId = parentItem ? parentItem.id : 0;
			}
		},
		checkListId(value: boolean): void
		{
			const isNewCheckList = Type.isString(value);
			if (isNewCheckList)
			{
				this.checkListWasUpdated = true;
			}
		},
	},
	created(): void
	{
		this.checkListManager = new CheckListManager({
			computed: {
				checkLists: () => this.checkLists,
			},
		});
	},
	mounted(): void
	{
		if (this.isAutonomous || this.isPreview)
		{
			this.subscribeToEvents();
		}
	},
	beforeUnmount(): void
	{
		if (this.isAutonomous || this.isPreview)
		{
			this.unsubscribeFromEvents();
		}

		if (this.isPreview)
		{
			this.executeCheckListCompletionCallbacks();
		}
	},
	methods: {
		...mapActions(Model.Interface, [
			'addCheckListItemToDeleting',
			'removeCheckListItemFromDeleting',
			'executeCheckListCompletionCallbacks',
			'clearCheckListCompletionCallbacks',
		]),
		subscribeToEvents(): void
		{
			Event.bind(this.$refs.list, 'scroll', this.handleScroll);

			EventEmitter.subscribe('BX.Main.Popup:onInit', this.handleInitPopup);
			EventEmitter.subscribe('BX.Main.Popup:onShow', this.handleShowPopup);
			EventEmitter.subscribe('BX.Main.Popup:onClose', this.handleClosePopup);
		},
		unsubscribeFromEvents(): void
		{
			Event.unbind(this.$refs.list, 'scroll', this.handleScroll);

			EventEmitter.unsubscribe('BX.Main.Popup:onInit', this.handleInitPopup);
			EventEmitter.unsubscribe('BX.Main.Popup:onShow', this.handleShowPopup);
			EventEmitter.unsubscribe('BX.Main.Popup:onClose', this.handleClosePopup);
		},
		handleUpdate(itemId: number | string): void
		{
			this.itemId = itemId;

			this.checkListWasUpdated = true;
		},
		handleRemove(itemId: number | string): void
		{
			this.itemId = itemId;

			this.freeze();

			this.addItemToDelete(itemId);

			this.checkListManager.hideItems(
				[itemId],
				(updates: CheckListModel[]) => this.upsertCheckLists(updates),
			);

			const messageKey = (
				this.currentItem.parentId === 0
					? 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_PARENT'
					: 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILD'
			);

			const notifier = new CheckListNotifier({
				content: this.loc(messageKey),
			});
			notifier.subscribeOnce('complete', (baseEvent: BaseEvent) => {
				const timerHasEnded = baseEvent.getData();

				if (timerHasEnded)
				{
					this.removeItem(itemId);
				}
				else
				{
					this.checkListManager.showItems(
						[itemId],
						(updates: CheckListModel[]) => this.upsertCheckLists(updates),
					);
				}

				this.removeItemFromDelete(itemId);

				this.unfreeze();

				this.notifiers.delete(itemId);
			});

			this.notifiers.set(itemId, notifier);

			notifier.showBalloonWithTimer();

			if (this.isCurrentItemEmpty())
			{
				notifier.stopTimer();
			}
		},
		handleScroll(): void
		{
			this.isForwardMenuShown = false;

			this.updatePanelPosition();
		},
		handleShow(data): void
		{
			this.$emit('show', data);
		},
		async handleClose(): void
		{
			this.cleanNotifiers();
			this.cancelGroupMode();
			this.cleanCollapsedState();
			this.executeCheckListCompletionCallbacks();

			if (
				this.checkListManager.hasEmptyItemWithFiles(this.hasItemFiles)
				|| this.checkListManager.hasEmptyParentItem()
			)
			{
				const firstEmptyItem = this.checkListManager.getFirstEmptyItem();

				this.focusToItem(firstEmptyItem.id, true);

				return;
			}

			const getItemIdWithUploadingFiles = (): string | number | undefined => {
				return [...this.fileServiceInstances.values()].find(
					(fileServiceInstance: FileService) => fileServiceInstance.isUploading(),
				)?.getEntityId();
			};

			const itemIdWithUploadingFiles = this.fileServiceInstances ? getItemIdWithUploadingFiles() : null;
			if (itemIdWithUploadingFiles)
			{
				this.focusToItem(itemIdWithUploadingFiles, true);

				return;
			}

			this.cleanEmptyCurrentItem();

			const checkListId = this.lastUpdatedCheckListId === 0 ? this.checkListId : this.lastUpdatedCheckListId;

			this.saveCheckList();

			this.$emit('close', this.deletingCheckListIds[checkListId] ? 0 : checkListId);
		},
		handleIsShown(isShown: boolean): void
		{
			if (isShown)
			{
				this.subscribeToEvents();
			}
			else
			{
				this.unsubscribeFromEvents();
			}
		},
		handleInitPopup(baseEvent: BaseEvent): void
		{
			const [id, bindElement, params] = baseEvent.getCompatData();
			if (id === 'b24-bottom-sheet' || id === 'tasks-relation-error')
			{
				return;
			}

			if (Type.isDomNode(bindElement))
			{
				const excludedIds = ['popup-submenu-'];
				const shouldExclude = excludedIds.some((excludedId: string) => id.includes(excludedId));

				if (
					this.$refs.list?.contains(bindElement)
					&& !shouldExclude
					&& !this.isPreview
				)
				{
					params.targetContainer = this.$refs.list;
				}
				else
				{
					params.targetContainer = document.body;
				}
			}
		},
		handleShowPopup(baseEvent: BaseEvent): void
		{
			const [popup] = baseEvent.getCompatData();

			this.shownPopups.add(popup.getId());

			this.freeze();
		},
		handleClosePopup(baseEvent: BaseEvent): void
		{
			const [popup] = baseEvent.getCompatData();

			this.shownPopups.delete(popup.getId());

			this.unfreeze();
		},
		async handleGroupRemove(itemId: number | string): Promise<void>
		{
			this.itemId = itemId;

			this.freeze();

			this.addItemToDelete(itemId);

			this.hideItemPanel(itemId);

			const allSelectedItems = this.checkListManager.getAllSelectedItems();

			const nearestItem = this.checkListManager.findNearestItem(this.currentItem, false);
			if (nearestItem)
			{
				await this.updateCheckList(nearestItem.id, {
					groupMode: {
						active: true,
						selected: true,
					},
				});

				setTimeout(() => {
					this.showItemPanel(nearestItem.id);
				}, 0);
			}

			const allSelectedItemIds = allSelectedItems.map((item: CheckListModel) => item.id);

			this.checkListManager.hideItems(
				allSelectedItemIds,
				(updates: CheckListModel[]) => this.upsertCheckLists(updates),
			);

			const messageKey = (
				allSelectedItems.length > 1
					? 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILDREN'
					: 'TASKS_V2_CHECK_LIST_ITEM_REMOVE_BALLOON_CHILD'
			);

			const notifier = new CheckListNotifier({
				content: this.loc(messageKey),
			});
			notifier.subscribeOnce('complete', (baseEvent: BaseEvent) => {
				const timerHasEnded = baseEvent.getData();
				const idsToShow = [];
				allSelectedItems.forEach((item: CheckListModel) => {
					if (timerHasEnded)
					{
						this.removeItem(item.id);
					}
					else
					{
						idsToShow.push(item.id);
					}

					this.removeItemFromDelete(item.id);
				});

				this.checkListManager.showItems(
					idsToShow,
					(updates: CheckListModel[]) => this.upsertCheckLists(updates),
				);

				if (timerHasEnded)
				{
					if (nearestItem && !this.deletingCheckListIds[nearestItem.id])
					{
						this.showItemPanel(nearestItem.id);
					}
					else
					{
						this.cancelGroupMode();
					}
				}
				else
				{
					this.showItemPanel(this.currentItem.id);
				}

				this.unfreeze();

				this.notifiers.delete(itemId);
			});

			this.notifiers.set(itemId, notifier);

			notifier.showBalloonWithTimer();
		},
		handleFocus(itemId: number | string): void
		{
			this.isItemPanelFreeze = false;

			this.showItemPanel(itemId);
		},
		handleBlur(itemId: number | string): void
		{
			this.itemId = itemId;

			if (
				this.isCurrentItemEmpty()
				&& this.hasItemFiles(this.currentItem)
			)
			{
				return;
			}

			if (this.isItemPanelFreeze === false)
			{
				this.hideItemPanel(itemId);
			}
		},
		handleEmptyBlur(itemId: number | string): void
		{
			this.itemId = itemId;

			if (this.currentItem.parentId === 0)
			{
				this.setDefaultCheckListTitle(itemId);

				return;
			}

			if (this.hasItemFiles(this.currentItem))
			{
				return;
			}

			if (this.isItemPanelFreeze === false)
			{
				this.removeItem(itemId);
			}
		},
		handleGroupMode(itemId: number | string): void
		{
			this.itemId = itemId;

			this.cancelGroupMode();

			const firstChild = this.checkListManager.getFirstChild(itemId);
			if (!firstChild)
			{
				return;
			}

			this.activateGroupMode(itemId);
			this.showItemPanel(firstChild.id);
		},
		handleGroupModeSelect(itemId: number | string): void
		{
			this.itemId = itemId;

			if (this.itemGroupModeSelected)
			{
				this.showItemPanel(itemId);
			}
			else
			{
				this.showItemPanelOnNearestSelectedItem(itemId);
			}
		},
		handlePanelAction({ action, node }: {action: string, node: HTMLElement}): void
		{
			const actionHandlers = {
				[PanelAction.SetImportant]: (n) => this.setImportant(n),
				[PanelAction.AttachFile]: (n) => this.attachFile(n),
				[PanelAction.MoveRight]: (n) => this.moveGroupToRight(n),
				[PanelAction.MoveLeft]: (n) => this.moveGroupToLeft(n),
				[PanelAction.AssignAccomplice]: (n) => {
					if (!this.isItemPanelFreeze)
					{
						this.showParticipantDialog(n, 'accomplices');
					}
				},
				[PanelAction.AssignAuditor]: (n) => {
					if (!this.isItemPanelFreeze)
					{
						this.showParticipantDialog(n, 'auditors');
					}
				},
				[PanelAction.Forward]: (n) => this.forward(n),
				[PanelAction.Delete]: (n) => this.delete(n),
				[PanelAction.Cancel]: (n) => this.cancelGroupMode(n),
			};

			actionHandlers[action]?.(node);
		},
		handleOpenCheckList(checkListId: number | string): void
		{
			this.cleanNotifiers();
			this.cleanCollapsedState();

			this.$emit('open', checkListId);
		},
		updateCheckList(id: number | string, fields: Partial<CheckListModel>): Promise<void>
		{
			return this.$store.dispatch(`${Model.CheckList}/update`, { id, fields });
		},
		updateTask(fields: Partial<TaskModel>): Promise<void>
		{
			return this.$store.dispatch(`${Model.Tasks}/update`, {
				id: this.taskId,
				fields,
			});
		},
		upsertCheckLists(items: CheckListModel[]): Promise<void>
		{
			return this.$store.dispatch(`${Model.CheckList}/upsertMany`, items);
		},

		insertCheckList(item: CheckListModel): Promise<void>
		{
			return this.$store.dispatch(`${Model.CheckList}/insert`, item);
		},

		insertManyCheckLists(items: CheckListModel[]): Promise<void>
		{
			return this.$store.dispatch(`${Model.CheckList}/insertMany`, items);
		},

		deleteCheckList(id: number | string): Promise<void>
		{
			return this.$store.dispatch(`${Model.CheckList}/delete`, id);
		},
		saveCheckList(): void
		{
			if (!this.isDemoCheckListModified())
			{
				this.removeChecklists();

				this.checkListWasUpdated = false;
			}

			if (
				this.checkListWasUpdated
				&& this.isEdit
				&& this.canSaveCheckList
			)
			{
				const deletingIds = new Set(Object.values(this.deletingCheckListIds));
				const fullListDeletingIds = this.checkListManager.expandIdsWithChildren(deletingIds);

				const checkListsToSave = this.checkLists.filter((checkList: CheckListModel) => {
					return !fullListDeletingIds.has(checkList.id);
				});

				void checkListService.save(this.taskId, checkListsToSave);
			}

			this.checkListWasUpdated = false;
		},
		isDemoCheckListModified(): boolean
		{
			if (this.getCheckListsNumber() > 1)
			{
				return true;
			}

			const [checkList] = this.checkLists;
			if (!checkList)
			{
				return false;
			}

			const demoTitle = this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', { '#number#': 1 });

			return (
				checkList.title !== demoTitle
				|| this.checkListManager.getChildren(checkList.id).length > 0
				|| this.hasItemUsers(checkList)
				|| this.hasItemFiles(checkList)
			);
		},
		removeChecklists(): void
		{
			this.checkLists
				.filter((checklist: CheckListModel) => checklist.parentId === 0)
				.forEach((item: CheckListModel) => {
					this.removeItem(item.id);
				});
		},
		async addCheckList(empty: boolean = false): Promise<string>
		{
			const parentId = Text.getRandom();
			const childId = Text.getRandom();

			const items = [this.getDataForNewCheckList(parentId)];
			if (!empty)
			{
				items.push({
					id: childId,
					nodeId: childId,
					parentId,
					sortIndex: 0,
				});
			}

			await this.insertManyCheckLists(items);
			void this.updateTask({ checklist: [...this.task.checklist, parentId, childId] });

			this.checkListWasUpdated = true;

			return parentId;
		},
		async addFastCheckList(): void
		{
			const checkListId = await this.addCheckList();

			this.handleOpenCheckList(checkListId);
		},
		showForwardMenu(node: HTMLElement): void
		{
			this.forwardBindElement = node;

			this.isForwardMenuShown = true;
		},
		getCheckListsNumber(): number
		{
			return this.checkLists.filter((checklist: CheckListModel) => {
				return checklist.parentId === 0 && !this.deletingCheckListIds[checklist.id];
			}).length;
		},
		getDataForNewCheckList(parentId: string): CheckListModel
		{
			return {
				id: parentId,
				nodeId: parentId,
				title: this.loc('TASKS_V2_CHECK_LIST_TITLE_NUMBER', { '#number#': this.getCountForNewCheckList() }),
				sortIndex: this.getSortForNewCheckList(),
			};
		},
		getSortForNewCheckList(): number
		{
			return this.getCheckListsNumber();
		},
		getCountForNewCheckList(): number
		{
			return this.getCheckListsNumber() + 1;
		},
		setItemsRef(id, ref): void
		{
			this.itemsRefs[id] = ref;
		},
		getItemsRef(id): Object
		{
			return this.itemsRefs[id];
		},
		focusToItem(itemId: number | string, highlight: boolean = false): void
		{
			void this.$nextTick(() => {
				const itemRef = this.getItemsRef(itemId);
				itemRef?.$refs.growingTextArea?.focusTextarea();
				if (highlight)
				{
					void (new Highlighter()).highlight(itemRef?.$refs.item);
				}
			});
		},
		addItem({ id, sort }: {id: number | string, sort: number}): void
		{
			if (this.hasActiveGroupMode())
			{
				return;
			}

			this.itemId = id;

			const childId = Text.getRandom();
			const parentId = this.currentItem.parentId;

			this.resortItemsAfterIndex(parentId, sort);

			this.insertItem(parentId, childId, sort);
		},
		addItemFromBtn(checkListId: number | string): void
		{
			if (this.isPreview)
			{
				this.handleOpenCheckList(checkListId);
			}

			if (this.hasActiveGroupMode())
			{
				return;
			}

			const childId = Text.getRandom();
			const sortIndex = this.checkListManager.getChildren(checkListId).length;

			this.insertItem(checkListId, childId, sortIndex);
		},
		insertItem(parentId: number, childId: number, sortIndex: number): void
		{
			void this.insertCheckList({
				id: childId,
				nodeId: childId,
				parentId,
				sortIndex,
			});
			void this.updateTask({ checklist: [...this.task.checklist, childId] });

			this.syncParentCompletionState(childId);
		},
		removeItem(id: number | string, isRootCall: boolean = true): void
		{
			if (!this.task)
			{
				return;
			}

			const item = this.checkListManager.getItem(id);
			if (!item)
			{
				return;
			}

			const children = this.checkListManager.getChildren(item.id);

			if (
				item?.title
				|| item.parentId === 0
			)
			{
				this.checkListWasUpdated = true;
			}

			if (children.length > 0)
			{
				children.forEach((child: CheckListModel) => {
					this.removeItem(child.id, false);
				});
			}

			const checkListIds = this.task.checklist.filter((itemId) => itemId !== item.id);
			void this.updateTask({
				containsChecklist: checkListIds.length > 0,
				checklist: checkListIds,
			});
			void this.deleteCheckList(item.id);

			if (isRootCall)
			{
				this.resortItemsOnLevel(item.parentId);
			}

			this.syncParentCompletionState(item.id, item.parentId);

			fileService.delete(item.id, EntityTypes.CheckListItem);
		},
		addItemToDelete(itemId: number | string): void
		{
			this.addCheckListItemToDeleting(itemId);
		},
		removeItemFromDelete(itemId: number | string): void
		{
			this.removeCheckListItemFromDeleting(itemId);

			if (this.checkListWasUpdated === true && this.isEdit && Type.isNumber(itemId))
			{
				void checkListService.delete(this.taskId, itemId);
			}
		},
		resortItemsAfterIndex(parentId: number | string, sortIndex: number): void
		{
			this.checkListManager.resortItemsAfterIndex(
				parentId,
				sortIndex,
				(updates: CheckListModel[]) => {
					void this.upsertCheckLists(updates);
				},
			);
		},
		resortItemsOnLevel(parentId: number | string): void
		{
			this.checkListManager.resortItemsOnLevel(
				parentId,
				(updates: CheckListModel[]) => this.upsertCheckLists(updates),
			);
		},
		syncParentCompletionState(itemId: number | string, parentId: number | string): void
		{
			this.checkListManager.syncParentCompletionState(
				itemId,
				(id: string | number, fields: Partial<CheckListModel>) => this.updateCheckList(id, fields),
				parentId,
			);
		},
		showItemPanel(itemId: number | string): void
		{
			if (this.isPreview)
			{
				return;
			}

			this.itemId = itemId;

			this.itemPanelIsShown = true;

			void this.updateCheckList(itemId, { panelIsShown: true });

			void this.$nextTick(() => this.updatePanelPosition());
		},
		hideItemPanel(itemId: number | string): void
		{
			if (this.isPreview)
			{
				return;
			}

			this.itemPanelIsShown = false;

			if (this.hasActiveGroupMode() && this.checkListManager.getAllSelectedItems().length === 0)
			{
				this.deactivateGroupMode();
			}

			const item = this.checkListManager.getItem(itemId);
			if (item)
			{
				void this.updateCheckList(itemId, { panelIsShown: false });
			}

			this.isItemPanelFreeze = false;
		},
		showItemPanelOnNearestSelectedItem(itemId: number | string): void
		{
			// eslint-disable-next-line no-lonely-if
			const nearestSelectedItem = this.checkListManager.findNearestItem(this.currentItem, true);
			if (nearestSelectedItem)
			{
				this.showItemPanel(nearestSelectedItem.id);
			}
			else
			{
				this.hideItemPanel(itemId);
			}
		},
		updatePanelPosition()
		{
			if (this.itemPanelIsShown === false)
			{
				return;
			}

			const itemRef = this.$refs.list.querySelector([`[data-id="${this.currentItem.id}"]`]);

			const panelRect = Dom.getPosition(this.$refs.panel.$el);
			const listRect = Dom.getPosition(this.$refs.list);
			const itemRect = Dom.getRelativePosition(itemRef, this.$refs.list);
			const isParentItem = (this.currentItem.parentId === 0);

			const paddingOffset = 18;
			const panelWidth = panelRect.width === 0 ? 304 : panelRect.width;

			const top = itemRect.top - 28;

			if (isParentItem)
			{
				const left = listRect.width - panelWidth - (paddingOffset * 2) - 80;
				const display = top > -30 && top < this.itemPanelTopLimit ? 'flex' : 'none';

				this.itemPanelStyles = {
					top: `${top}px`,
					left: `${left}px`,
					display,
				};
			}
			else
			{
				const left = listRect.width - panelWidth - paddingOffset;
				const display = top > 40 && top < this.itemPanelTopLimit ? 'flex' : 'none';

				this.itemPanelStyles = {
					top: `${top}px`,
					left: `${left}px`,
					display,
				};
			}
		},
		setImportant(): void
		{
			if (this.itemGroupModeSelected)
			{
				const updates = this.checkListManager.getAllSelectedItems()
					.map((item: CheckListModel) => ({
						...item,
						isImportant: !item.isImportant,
					}));

				void this.upsertCheckLists(updates);
			}
			else
			{
				void this.updateCheckList(this.currentItem.id, { isImportant: !this.currentItem.isImportant });
			}

			this.checkListWasUpdated = true;
		},
		attachFile(node: HTMLElement): void
		{
			this.fileServiceInstances ??= new Map();

			const fileServiceInstance = this.getCurrentFileService();

			this.fileServiceInstances.set(this.currentItem.id, fileServiceInstance);

			fileServiceInstance.browse({
				bindElement: node,
				onShowCallback: () => {
					this.isItemPanelFreeze = true;
				},
				onHideCallback: () => {
					this.isItemPanelFreeze = false;
				},
			});
			fileServiceInstance.subscribe('onFileComplete', () => {
				this.isItemPanelFreeze = fileServiceInstance.isUploading();
				this.focusToItem(this.currentItem.id);
				this.checkListWasUpdated = true;
			});
		},
		getCurrentFileService(): ?FileService
		{
			if (!this.currentItem)
			{
				return null;
			}

			return fileService.get(
				this.currentItem.id,
				EntityTypes.CheckListItem,
				{ parentEntityId: this.taskId },
			);
		},
		hasItemFiles(item: CheckListModel): boolean
		{
			if (!item)
			{
				return false;
			}

			const fileServiceInstance = this.getCurrentFileService();

			const files = item.attachments;

			return (
				files.length > 0
				|| fileServiceInstance?.isUploading()
				|| fileServiceInstance?.hasUploadingError()
			);
		},
		hasItemUsers(item: CheckListModel): boolean
		{
			return (item.accomplices.length > 0 || item.auditors.length > 0);
		},
		isCurrentItemEmpty(): boolean
		{
			if (!this.currentItem)
			{
				return true;
			}

			return this.currentItem.title === '';
		},
		moveGroupToRight(): void
		{
			if (this.itemGroupModeSelected)
			{
				this.checkListManager.getAllSelectedItems()
					.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex)
					.forEach((item: CheckListModel) => {
						this.moveRight(item);
					});
			}
			else
			{
				this.moveRight(this.currentItem);
			}
		},
		moveRight(item: CheckListModel): void
		{
			this.checkListManager.moveRight(
				item,
				(updates: CheckListModel[]) => {
					void this.upsertCheckLists(updates);
					this.checkListWasUpdated = true;

					if (!item.groupMode?.active)
					{
						this.focusToItem(item.id);
					}
				},
			);
		},
		moveGroupToLeft(): void
		{
			if (this.itemGroupModeSelected)
			{
				this.checkListManager.getAllSelectedItems()
					.sort((a: CheckListModel, b: CheckListModel) => b.sortIndex - a.sortIndex)
					.forEach((item: CheckListModel) => {
						this.moveLeft(item);
					});
			}
			else
			{
				this.moveLeft(this.currentItem);
			}
		},
		moveLeft(item: CheckListModel): void
		{
			this.checkListManager.moveLeft(
				item,
				(updates: CheckListModel[]) => {
					void this.upsertCheckLists(updates);
					this.checkListWasUpdated = true;

					if (!item.groupMode?.active)
					{
						this.focusToItem(item.id);
					}
				},
			);
		},
		async forward(node: HTMLElement): Promise<void>
		{
			if (this.hasFewParentCheckLists)
			{
				this.showForwardMenu(node);
			}
			else
			{
				this.hideItemPanel();

				void this.forwardToNewChecklist();
			}

			this.checkListWasUpdated = true;
		},
		async forwardToNewChecklist(): Promise<void>
		{
			const newParentId = await this.addCheckList(true);

			if (this.itemGroupModeSelected)
			{
				void this.forwardGroupItemsToChecklist(newParentId);
			}
			else
			{
				this.forwardToChecklist(newParentId);
			}
		},
		forwardToChecklist(checkListId: number | string): void
		{
			const finalSortIndex = this.checkListManager.getChildren(checkListId).length;

			void this.updateCheckList(this.currentItem.id, {
				parentId: checkListId,
				sortIndex: finalSortIndex,
			});

			this.resortItemsOnLevel(checkListId);
			this.resortItemsOnLevel(this.currentItem.parentId);
		},
		async forwardGroupItemsToChecklist(checkListId: number | string): Promise<void>
		{
			const finalSortIndex = this.checkListManager.getChildren(checkListId).length;

			const checkListIdsFromWhichWereForwarded = new Set();

			const allSelectedItems = this.checkListManager.getAllSelectedItems();
			const nearestItem = this.checkListManager.findNearestItem(this.currentItem, false, allSelectedItems);
			if (nearestItem)
			{
				this.showItemPanel(nearestItem.id);
			}
			else
			{
				this.cancelGroupMode();
			}

			const allSelectedWithChildren = this.checkListManager.getAllSelectedItemsWithChildren();

			const selectedItemsIds = new Set(allSelectedItems.map((item: CheckListModel) => item.id));

			const updates = [];

			allSelectedItems.forEach((item: CheckListModel) => {
				const shouldUpdateParentId = !selectedItemsIds.has(item.parentId);

				checkListIdsFromWhichWereForwarded.add(item.parentId);

				updates.push({
					...item,
					parentId: shouldUpdateParentId ? checkListId : item.parentId,
					groupMode: {
						active: false,
						selected: false,
					},
					sortIndex: shouldUpdateParentId ? finalSortIndex : item.sortIndex,
				});
			});

			allSelectedWithChildren.forEach((item: CheckListModel) => {
				if (!selectedItemsIds.has(item.id))
				{
					updates.push({
						...item,
						groupMode: {
							active: false,
							selected: false,
						},
					});
				}
			});

			await this.upsertCheckLists(updates);

			if (nearestItem)
			{
				void this.updateCheckList(nearestItem.id, {
					groupMode: {
						active: true,
						selected: true,
					},
				});
			}

			this.resortItemsOnLevel(checkListId);
			checkListIdsFromWhichWereForwarded.forEach((id: number | string) => {
				this.resortItemsOnLevel(id);
			});
		},
		delete(): void
		{
			if (this.itemGroupModeSelected)
			{
				void this.handleGroupRemove(this.currentItem.id);
			}
			else
			{
				this.hideItemPanel();
				this.handleRemove(this.currentItem.id);
			}
		},
		cancelGroupMode(): void
		{
			this.deactivateGroupMode();
			this.hideItemPanel();
		},
		cleanCollapsedState(): void
		{
			const updates = this.parentCheckLists
				.map((item: CheckListModel) => ({
					...item,
					localCollapsedState: null,
				}));

			void this.upsertCheckLists(updates);
		},
		cleanEmptyCurrentItem(): void
		{
			if (this.currentItem && this.isCurrentItemEmpty())
			{
				this.removeItem(this.currentItem.id);
			}
		},
		showParticipantDialog(targetNode: HTMLElement, type: 'accomplices' | 'auditors'): void
		{
			this.isItemPanelFreeze = true;

			const preselected = this.currentItem[type].map((user: UserModel) => ['user', user.id]);

			this.selector ??= new EntitySelectorDialog({
				...participantMeta.dialogOptions(this.taskId, 'check-list'),
				preselectedItems: preselected,
				popupOptions: {
					events: {
						onShow: (baseEvent: BaseEvent): void => {
							const popup = baseEvent.getTarget();
							const popupWidth = popup.getPopupContainer().offsetWidth;
							const targetNodeWidth = 10;

							const offsetLeft = targetNodeWidth - (popupWidth / 2);
							const angleShift = Popup.getOption('angleLeftOffset') - Popup.getOption('angleMinTop');

							popup.setAngle({ offset: popupWidth / 2 - angleShift });
							popup.setOffset({ offsetLeft: offsetLeft + Popup.getOption('angleLeftOffset') });
						},
						onClose: (): void => {
							const users = this.selector.getSelectedItems().map((item: Item) => ({
								id: item.getId(),
								name: item.getTitle(),
								image: item.getAvatar(),
								type: item.getEntityType(),
							}));

							this.saveParticipants(this.selector.params.itemId, this.selector.params.type, users);
						},
					},
				},
				events: {
					onHide: (): void => {
						this.isItemPanelFreeze = false;

						if (!this.itemGroupModeSelected && this.currentItem.id === this.selector.params.itemId)
						{
							this.focusToItem(this.selector.params.itemId);
						}

						this.updatePanelPosition();
					},
				},
			});

			this.selector.selectItemsByIds(preselected);
			this.selector.params = {
				itemId: this.currentItem.id,
				type,
			};

			this.selector.showTo(targetNode);
		},
		saveParticipants(id: number | string, type: 'accomplices' | 'auditors', users: UserModel[]): void
		{
			if (this.itemGroupModeSelected)
			{
				const updates = this.checkListManager.getAllSelectedItems()
					.map((item: CheckListModel) => ({
						...item,
						[type]: users,
					}));

				void this.upsertCheckLists(updates);
			}
			else
			{
				void this.updateCheckList(id, {
					[type]: users,
				});
			}

			const ids = users.map((user: UserModel) => user.id);
			const fields = {
				[`${type}Ids`]: ids,
			};

			void this.updateTask(fields);

			this.checkListWasUpdated = true;
		},
		activateGroupMode(parentItemId: number | string): void
		{
			this.itemId = parentItemId;

			const updates = this.checkListManager.getAllChildren(parentItemId)
				.map((item: CheckListModel, index: number) => ({
					...item,
					groupMode: {
						active: true,
						selected: index === 0,
					},
				}));

			updates.push({
				...this.currentItem,
				groupMode: {
					active: true,
					selected: false,
				},
			});

			void this.upsertCheckLists(updates);
		},
		deactivateGroupMode(): void
		{
			const updates = this.checkListManager.getAllGroupModeItems()
				.map((item: CheckListModel) => ({
					...item,
					groupMode: {
						active: false,
						selected: false,
					},
				}));

			void this.upsertCheckLists(updates);
		},
		hasActiveGroupMode(): boolean
		{
			return this.checkListManager.getAllGroupModeItems().length > 0;
		},
		freeze()
		{
			this.$refs.childComponent?.$refs?.childComponent?.freeze();
		},
		unfreeze()
		{
			if (
				this.shownPopups.size === 0
				&& Object.keys(this.deletingCheckListIds).length === 0
			)
			{
				this.$refs.childComponent?.$refs?.childComponent?.unfreeze();
			}
		},
		setDefaultCheckListTitle(itemId: number | string): void
		{
			void this.updateCheckList(itemId, {
				title: this.loc(
					'TASKS_V2_CHECK_LIST_TITLE_NUMBER',
					{ '#number#': this.getCheckListsNumber() },
				),
			});
		},
		cleanNotifiers(): void
		{
			this.notifiers.forEach((notifier: CheckListNotifier) => notifier.stopTimer());
			this.notifiers.clear();
		},
	},
	template: `
		<component
			v-if="componentShown"
			ref="childComponent"
			:is="componentName"
			:taskId="taskId"
			:isShown="isShown"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			:isEmpty="emptyList"
			@show="handleShow"
			@close="handleClose"
			@isShown="handleIsShown"
			@addFastCheckList="addFastCheckList"
			@resize="$emit('resize')"
		>
			<template v-slot:default="{ handleShow, handleClose }">
				<div ref="wrapper" class="tasks-check-list-wrapper" :class="contextClass">
					<div v-if="!isPreview" class="tasks-check-list-close-icon" :class="contextClass">
						<BIcon :name="Outline.CROSS_L" @click="handleClose"/>
					</div>
					<div ref="list" data-list class="tasks-check-list-content" :class="contextClass">
						<CheckListWidget
							v-show="!stub"
							:taskId="taskId"
							:checkListId="checkListId"
							:isPreview="isPreview"
							@update="handleUpdate"
							@show="handleShow"
							@addItem="addItem"
							@addItemFromBtn="addItemFromBtn"
							@removeItem="handleRemove"
							@focus="handleFocus"
							@blur="handleBlur"
							@emptyBlur="handleEmptyBlur"
							@startGroupMode="handleGroupMode"
							@toggleGroupModeSelected="handleGroupModeSelect"
							@openCheckList="handleOpenCheckList"
						/>
						<CheckListStub v-if="stub && !isPreview" @click="addCheckList" />
					</div>
					<div v-show="!stub && !isPreview" class="tasks-check-list-footer" :class="contextClass">
						<UiButton
							:text="loc('TASKS_V2_CHECK_LIST_NEW_BTN')"
							:size="ButtonSize.MEDIUM"
							:leftIcon="ButtonIcon.ADD"
							:style="AirButtonStyle.PLAIN_NO_ACCENT"
							@click="addCheckList"
						/>
						<UiButton
							:text="loc('TASKS_V2_CHECK_LIST_READY_BTN')"
							:size="ButtonSize.MEDIUM"
							@click="handleClose"
						/>
					</div>
					<CheckListItemPanel
						v-if="itemPanelIsShown && !isPreview"
						ref="panel"
						:taskId="taskId"
						:currentItem="currentItem"
						:style="itemPanelStyles"
						@action="handlePanelAction"
					/>
					<BMenu
						v-if="isForwardMenuShown"
						:options="forwardMenuOptions"
						@close="isForwardMenuShown = false"
					/>
				</div>
			</template>
		</component>
	`,
};
