/**
 * @module tasks/checklist/controller
 */
jn.define('tasks/checklist/controller', (require, exports, module) => {
	const { ChecklistWidget } = require('tasks/checklist/widget');
	const { CheckListFlatTree } = require('tasks/checklist/flat-tree');

	/**
	 * @class ChecklistController
	 */
	class ChecklistController
	{
		constructor(props)
		{
			this.props = props;
			this.checklistsMap = new Map();
			this.widgetMap = new Map();

			this.handleOnSave = this.handleOnSave.bind(this);
			this.handleOnClose = this.handleOnClose.bind(this);
			this.handleOnRemove = this.handleOnRemove.bind(this);
			this.handleOnShowOnlyMine = this.handleOnShowOnlyMine.bind(this);
			this.handleOnHideCompleted = this.handleOnHideCompleted.bind(this);
			this.handleOnMoveToChecklist = this.handleOnMoveToChecklist.bind(this);
			this.handleOnCreateChecklist = this.handleOnCreateChecklist.bind(this);

			this.createChecklistsMap(props);
		}

		getChecklists()
		{
			return this.checklistsMap;
		}

		getChecklistsIds()
		{
			return [...this.checklistsMap.keys()];
		}

		/**
		 * @param {string | number} checklistId
		 * @return {Checklist}
		 */
		getViewChecklistComponent(checklistId)
		{
			const checklistWidget = this.widgetMap.get(checklistId);

			if (!checklistWidget)
			{
				return null;
			}

			return checklistWidget.getComponent();
		}

		closeChecklistWidget(checklistId)
		{
			const checklistWidget = this.widgetMap.get(checklistId);
			if (!checklistWidget)
			{
				return;
			}

			checklistWidget.close();
		}

		createChecklistsMap(props)
		{
			const { checkListTree } = props;
			const checklists = checkListTree?.descendants || [];

			if (checklists.length === 0)
			{
				return;
			}

			checklists.forEach((checklist) => {
				this.addChecklist(new CheckListFlatTree({ checklist }));
			});
		}

		addChecklist(checklist)
		{
			const checklistId = checklist.getId();
			this.checklistsMap.set(checklistId, checklist);
		}

		/**
		 * @private
		 * @param {string | number} checklistId
		 */
		deleteChecklist(checklistId)
		{
			this.checklistsMap.delete(checklistId);
			this.removeFromWidgetMap(checklistId);
		}

		/**
		 * @private
		 * @param {string | number} checklistId
		 */
		removeFromWidgetMap(checklistId)
		{
			this.widgetMap.delete(checklistId);
		}

		/**
		 * @param {object} params
		 * @param {CheckListFlatTree} [params.checklist]
		 * @param {string | number} [params.focusedItemId]
		 * @return {Promise}
		 */
		openChecklist(params)
		{
			const { userId } = this.props;
			const { checklist } = params;
			const checklistId = checklist.getId();

			return new Promise((resolve) => {
				ChecklistWidget.open({
					userId,
					inLayout: false,
					checklists: this.checklistsMap,
					onSave: this.handleOnSave,
					onClose: this.handleOnClose,
					moreMenuActions: {
						onRemove: this.handleOnRemove(checklistId),
						onCreateChecklist: this.handleOnCreateChecklist(checklistId),
						onMoveToCheckList: this.handleOnMoveToChecklist,
						onShowOnlyMine: this.handleOnShowOnlyMine(checklistId),
						onHideCompleted: this.handleOnHideCompleted(checklistId),
					},
					...params,
				}).then((checklistWidget) => {
					this.widgetMap.set(checklistId, checklistWidget);
					resolve(checklistWidget);
				}).catch(console.error);
			});
		}

		/**
		 * @param params
		 * @return {CheckListFlatTree}
		 */
		createNewChecklist(params)
		{
			const newChecklist = CheckListFlatTree.buildDefaultList(params);
			this.addChecklist(newChecklist);

			return newChecklist;
		}

		/**
		 * @param {object} moveParams
		 * @param {number[]} [moveParams.moveIds]
		 * @param {number} [moveParams.toCheckListId]
		 * @param {number} [moveParams.sourceCheckListId]
		 * @param {boolean} [moveParams.open]
		 * @return {void}
		 */
		async handleOnMoveToChecklist(moveParams)
		{
			const { moveIds, sourceCheckListId } = moveParams;

			if (moveIds.length === 0)
			{
				console.error('moveIds is empty');

				return null;
			}

			let toCheckListId = moveParams.toCheckListId;
			let checklist = toCheckListId ? this.checklistsMap.get(toCheckListId) : null;

			if (!toCheckListId)
			{
				checklist = this.createChecklist({ addBlankItem: false });
				toCheckListId = checklist.getId();
			}

			this.moveToChecklist({ moveIds, toCheckListId, sourceCheckListId });

			return async () => {
				const { checklistWidget } = await this.openChecklist({
					checklist,
					focusedItemId: moveIds[0],
					parentWidget: this.getParentWidgetByChecklistId(sourceCheckListId),
				}).catch(console.error);

				checklistWidget.handleOnChange();
			};
		}

		/**
		 * @param {number[]} moveIds
		 * @param {number} toCheckListId
		 * @param {number} sourceCheckListId
		 */
		moveToChecklist({ moveIds, toCheckListId, sourceCheckListId })
		{
			const viewChecklist = this.getViewChecklistComponent(sourceCheckListId);
			const sourceChecklist = this.checklistsMap.get(sourceCheckListId);
			const receivingChecklist = this.checklistsMap.get(toCheckListId);

			const removeItems = [];

			moveIds.forEach((id) => {
				const item = sourceChecklist.getItemById(id);
				if (item)
				{
					receivingChecklist.addItem(item);
					removeItems.push(item);
				}
			});

			removeItems.forEach((item) => {
				viewChecklist.handleOnRemoveItem(item);
			});
		}

		getParentWidgetByChecklistId(checklistId)
		{
			const { parentWidget } = this.props;
			const checklistWidget = this.widgetMap.get(checklistId);

			if (!checklistWidget)
			{
				return parentWidget;
			}

			return checklistWidget.getLayoutWidget();
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		handleOnCreateChecklist(checklistId)
		{
			return () => {
				return this.openChecklist({
					checklist: this.createChecklist(),
					parentWidget: this.getParentWidgetByChecklistId(checklistId),
				});
			};
		}

		/**
		 * @private
		 * @param params
		 * @return {CheckListFlatTree}
		 */
		createChecklist(params = {})
		{
			return this.createNewChecklist({
				number: this.checklistsMap.size,
				addBlankItem: true,
				...params,
			});
		}

		getChecklistRequestData()
		{
			const requestData = [];

			this.checklistsMap.forEach((checklist) => {
				requestData.push(checklist.getRequestData());
			});

			return requestData.flat();
		}

		onChange()
		{
			const { onChange } = this.props;

			onChange(this.getChecklistsIds());
		}

		filterEmptyChecklists()
		{
			this.checklistsMap.forEach((checklist, id) => {
				const length = checklist.getTreeItems().filter((item) => item.hasItemTitle()).length;

				if (length <= 1)
				{
					this.checklistsMap.delete(id);
				}
			});
		}

		/**
		 * @public
		 * @param {number} taskId
		 */
		save({ taskId })
		{
			this.filterEmptyChecklists();
			const items = this.getChecklistRequestData();

			BX.ajax.runAction(
				'tasks.task.checklist.save',
				{
					data: { items, taskId },
				},
			).then((response) => {
				if (response?.data?.checkListItem
					&& items.length === Object.keys(response.data.checkListItem?.traversedItems).length
				)
				{
					console.log(`save done, count:${items.length}`);
				}
				else
				{
					console.log('save error', response);
				}

				this.onChange();
			}).catch(console.error);
		}

		handleOnSave()
		{
			const { taskId } = this.props;

			this.save({ taskId });
		}

		handleOnClose(checklistId)
		{
			const checklist = this.checklistsMap.get(checklistId);

			if (!checklist || !this.widgetMap.has(checklistId))
			{
				return;
			}

			this.removeFromWidgetMap();
			this.handleOnSave();
		}

		handleOnRemove(checklistId)
		{
			return () => {
				this.closeChecklistWidget(checklistId);
				this.deleteChecklist(checklistId);
				this.handleOnSave();
			};
		}

		handleOnHideCompleted(checklistId)
		{
			return (selected) => {
				const checklist = this.getViewChecklistComponent(checklistId);
				checklist.reload({ hideCompleted: selected, onlyMy: false });
			};
		}

		handleOnShowOnlyMine(checklistId)
		{
			return (selected) => {
				const checklist = this.getViewChecklistComponent(checklistId);
				checklist.reload({ onlyMy: selected, hideCompleted: false });
			};
		}
	}

	module.exports = { ChecklistController };
});
