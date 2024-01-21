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
			this.tempChecklistsIds = new Set();
			this.checklists = new Map();
			this.widgetMap = new Map();

			this.handleOnSave = this.handleOnSave.bind(this);
			this.handleOnClose = this.handleOnClose.bind(this);
			this.handleOnRemove = this.handleOnRemove.bind(this);
			this.handleOnMoveToChecklist = this.handleOnMoveToChecklist.bind(this);
			this.handleOnCreateChecklist = this.handleOnCreateChecklist.bind(this);

			this.createChecklistsMap(props);
		}

		getChecklists()
		{
			return this.checklists;
		}

		getChecklistsIds()
		{
			return this.checklists.keys();
		}

		closeChecklistWidget(checklistId)
		{
			const layoutWidget = this.widgetMap.get(checklistId);
			if (!layoutWidget)
			{
				return;
			}

			layoutWidget.back();
		}

		createChecklistsMap(props)
		{
			const { checkListTree } = props;
			const checklists = checkListTree?.descendants || [];

			if (checklists.length === 0)
			{
				return;
			}

			checklists.forEach((checkList) => {
				this.addChecklist(new CheckListFlatTree(checkList));
			});
		}

		addChecklist(checkList)
		{
			const rootItem = checkList.getRootItem();
			this.checklists.set(rootItem.getId(), checkList);
			this.tempChecklistsIds.add(rootItem.getId());
		}

		deleteChecklist(checklistId)
		{
			this.checklists.delete(checklistId);
		}

		openChecklist(checklist)
		{
			const rootItem = checklist.getRootItem();
			const checklistId = rootItem.getId();

			return new Promise((resolve) => {
				ChecklistWidget.open({
					...this.props,
					checklist,
					checklists: this.checklists,
					onSave: this.handleOnSave,
					onClose: () => {
						this.handleOnClose(checklistId);
					},
					moreMenuActions: {
						onCreateChecklist: () => {
							this.handleOnCreateChecklist();
						},
						onRemove: () => {
							this.handleOnRemove(checklistId);
						},
						onMoveToCheckList: this.handleOnMoveToChecklist,
					},
				}).then(({ layoutWidget }) => {
					resolve(layoutWidget);
					this.widgetMap.set(checklistId, layoutWidget);
				}).catch(console.error);
			});
		}

		createNewChecklist(params)
		{
			const newChecklist = CheckListFlatTree.buildDefaultList(params);
			this.addChecklist(newChecklist);

			return newChecklist;
		}

		handleOnMoveToChecklist(moveParams)
		{
			const { moveIds, toCheckListId, sourceCheckListId } = moveParams;
			if (moveIds.length === 0)
			{
				return;
			}

			const sourceCheckList = this.checklists.get(sourceCheckListId);
			const receivingCheckList = this.checklists.get(toCheckListId);

			moveIds.forEach((id) => {
				receivingCheckList.addItem(sourceCheckList.getItemById(id));
				sourceCheckList.removeById(id);
			});
		}

		handleOnCreateChecklist()
		{
			return this.openChecklist(
				this.createNewChecklist({
					number: this.checklists.size,
					addBlankItem: true,
				}),
			);
		}

		handleOnRemove(checklistId)
		{
			this.closeChecklistWidget(checklistId);
			this.deleteChecklist(checklistId);
			this.handleOnSave();
		}

		onChange()
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange(this.getChecklistsIds());
			}
		}

		/**
		 * After the refactor, the saving of each checklist must be transferred to the tree
		 */
		handleOnSave()
		{
			const items = [];
			this.checklists.forEach((checkList) => {
				items.push(...checkList.getRequestData());
			});

			if (items.length === 0)
			{
				return;
			}

			this.tempChecklistsIds.clear();

			const { taskId } = this.props;

			BX.ajax.runAction(
				'tasks.task.checklist.save',
				{
					data: { items, taskId },
				},
			).then((response) => {
				if (items.length === Object.keys(response?.data?.checkListItem?.traversedItems).length)
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

		handleOnClose(checklistId)
		{
			if (this.tempChecklistsIds.has(checklistId))
			{
				this.deleteChecklist(checklistId);
				this.tempChecklistsIds.delete(checklistId);
				this.widgetMap.delete(checklistId);
			}
		}
	}

	module.exports = { ChecklistController };
});
