/**
 * @module tasks/layout/checklist/list/src/item
 */
jn.define('tasks/layout/checklist/list/src/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const AppTheme = require('apptheme');
	const { useCallback } = require('utils/function');
	const { ItemMembers } = require('tasks/layout/checklist/list/src/actions/members');
	const { ItemAttachments } = require('tasks/layout/checklist/list/src/actions/attachments');
	const { ItemTextField } = require('tasks/layout/checklist/list/src/text-field');
	const { CheckBoxCounter } = require('tasks/layout/checklist/list/src/checkbox/checkbox-counter');
	const { ChecklistItemView } = require('tasks/layout/checklist/list/src/layout/item-view');
	const { ButtonRemove } = require('tasks/layout/checklist/list/src/buttons/button-remove');

	/**
	 * @class ChecklistItem
	 */
	class ChecklistItem extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {CheckListFlatTreeItem} [props.item]
		 * @param {boolean} [props.isFocused]
		 */
		constructor(props)
		{
			super(props);

			/** @type {ItemAttachments} */
			this.attachmentsRef = null;
			/** @type {CheckBoxCounter} */
			this.counterRef = null;
			/** @type {ItemTextField} */
			this.textRef = null;
			/** @type {ButtonRemove} */
			this.buttonRemoveRef = null;

			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnRemove = this.handleOnRemove.bind(this);
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnChangeAttachments = this.handleOnChangeAttachments.bind(this);
			this.handleOnChangeTitle = this.handleOnChangeTitle.bind(this);
			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);
		}

		render()
		{
			const { item } = this.props;

			return ChecklistItemView({
				testId: `checkListItem-${item.getNodeId()}`,
				divider: true,
				children: [
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						View(
							{
								style: {
									flexDirection: 'row',
									alignItems: 'flex-start',
								},
								onClick: () => {
									this.textInputFocus();
								},
							},
							this.renderCheckbox(),
							this.renderTextField(),
							!item.isRoot() && new ButtonRemove({
								ref: (buttonRemoveRef) => {
									this.buttonRemoveRef = buttonRemoveRef;
								},
								onClick: useCallback(this.handleOnRemove),
							}),
						),
						this.renderActions(),
					),
				],
			});
		}

		/**
		 * @private
		 */
		isShowActionRow()
		{
			const { item } = this.props;

			return item.getMembersCount() > 0 || item.getAttachmentsCount() > 0;
		}

		/**
		 * @private
		 * @return {[ItemAttachments]}
		 */
		renderActions()
		{
			const { item } = this.props;
			const marginLeft = 36 + item.getDepth() * 12;

			return View(
				{
					style: {
						display: this.isShowActionRow() ? 'flex' : 'none',
						marginTop: this.isShowActionRow() ? 8 : 0,
						marginLeft,
						flexDirection: 'row',
					},
				},
				this.renderAttachments(),
				this.renderMembers(),
			);
		}

		/**
		 * @private
		 * @return View
		 */
		renderMembers()
		{
			const { item, openUserSelectionManager } = this.props;

			return new ItemMembers({
				item,
				onClick: () => {
					openUserSelectionManager(item.getId());
				},
			});
		}

		setMembersToText(members)
		{
			const { item } = this.props;
			const itemTitle = item.getTitle().trim();
			const slicePosition = this.textRef?.getCursorPosition() ?? itemTitle.length;
			const startPosition = itemTitle.slice(0, slicePosition).trim();
			const endPosition = itemTitle.slice(slicePosition, itemTitle.length).trim();
			const title = `${startPosition} ${members.join(' ')} ${endPosition}`.trim();

			this.handleOnChangeTitle(title);
			this.textRef.reload();
		}

		/**
		 * @private
		 * @returns {ItemAttachments}
		 */
		renderAttachments()
		{
			const { item, parentWidget, diskConfig } = this.props;

			return new ItemAttachments({
				ref: useCallback((ref) => {
					this.attachmentsRef = ref;
				}),
				item,
				diskConfig,
				parentWidget,
				onChange: this.handleOnChangeAttachments,
			});
		}

		/**
		 * @private
		 * @returns {ItemTextField}
		 */
		renderTextField()
		{
			const { item, parentWidget, isFocused } = this.props;

			return new ItemTextField({
				ref: useCallback((ref) => {
					this.textRef = ref;
				}),
				isFocused,
				parentWidget,
				placeholder: Loc.getMessage(`TASKSMOBILE_LAYOUT_${item.isRoot() ? 'LIST' : 'ITEM'}_INPUT_PLACEHOLDER`),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				completed: item.getIsComplete(),
				getTitle: useCallback(() => item.getTitle()),
				members: item.getMembers(),
				onSubmit: useCallback(this.handleOnSubmit),
				onChangeText: useCallback(this.handleOnChangeTitle),
				styles: {
					fontWeight: item.isRoot() ? '500' : '400',
				},
			});
		}

		/**
		 * @private
		 * @returns {CheckBoxCounter|null}
		 */
		renderCheckbox()
		{
			const { item } = this.props;

			if (item.isRoot())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						marginRight: 6,
						alignItems: 'center',
					},
				},
				...this.renderDepth(),
				new CheckBoxCounter({
					ref: useCallback((ref) => {
						this.counterRef = ref;
					}),
					onClick: useCallback(this.handleOnToggleComplete),
					checked: item.getIsComplete(),
					important: item.getIsImportant(),
					disabled: !item.checkCanToggle(),
					totalCount: item.getDescendantsCount(),
					completedCount: item.getCompleteCount(),
					progressMode: item.getDescendantsCount() > 0,
				}),
			);
		}

		renderDepth()
		{
			const { item } = this.props;

			return Array.from({ length: item.getDepth() }).map(() => View({
				style: {
					marginRight: 8,
					width: 6,
					height: 1,
					alignItems: 'center',
					backgroundColor: AppTheme.colors.base6,
				},
			}));
		}

		handleOnChangeAttachments()
		{
			const { updateRows, item } = this.props;

			this.handleOnChange();

			if (updateRows)
			{
				updateRows([item.getId()]);
			}
		}

		handleOnChangeTitle(title)
		{
			const { item } = this.props;

			item.setTitle(title);
			item.setIsNew(false);
			this.handleOnChange();
		}

		handleOnToggleComplete()
		{
			const { item, onToggleComplete } = this.props;

			Haptics.impactLight();
			item.toggleComplete();

			onToggleComplete(item);
		}

		handleOnChange()
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange();
			}
		}

		handleOnSubmit()
		{
			const { item, onAdd } = this.props;
			if (item.getTitle() && onAdd)
			{
				onAdd(item);
			}
		}

		handleOnBlur()
		{
			if (this.buttonRemoveRef)
			{
				this.buttonRemoveRef.hide();
			}

			const { item } = this.props;
			const { onBlur } = this.props;

			if (onBlur)
			{
				onBlur(item);
			}
		}

		handleOnFocus()
		{
			const { item, onFocus } = this.props;

			if (this.buttonRemoveRef)
			{
				this.buttonRemoveRef.show();
			}

			if (onFocus)
			{
				onFocus(item);
			}
		}

		handleOnRemove()
		{
			const { item, onRemove } = this.props;

			if (onRemove && item.checkCanRemove())
			{
				onRemove(item);
			}
		}

		textInputFocus()
		{
			if (this.textRef)
			{
				this.textRef.focus();
			}
		}

		textInputBlur()
		{
			if (this.textRef)
			{
				this.textRef.blur();
			}
		}

		clearText(item)
		{
			if (item.getTitle())
			{
				this.handleOnChangeTitle('');
				this.textRef.clear();
			}
		}

		addFile()
		{
			this.attachmentsRef.addFile();
		}

		toggleImportant()
		{
			const { item } = this.props;
			item.toggleImportant(!item.getIsImportant());

			return new Promise((resolve) => {
				this.counterRef.toggleAnimateImportant(item.getIsImportant())
					.then(() => {
						this.handleOnChange();
						resolve();
					})
					.catch(console.error);
			});
		}

		toggleCompleteText()
		{
			if (this.textRef)
			{
				this.textRef.completeItem();
			}
		}

		updateProgress(progressParams)
		{
			if (!this.counterRef)
			{
				return Promise.resolve();
			}

			return this.counterRef.updateProgress(progressParams);
		}
	}

	module.exports = { ChecklistItem };
});
