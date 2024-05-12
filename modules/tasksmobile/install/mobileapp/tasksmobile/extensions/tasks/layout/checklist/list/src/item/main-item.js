/**
 * @module tasks/layout/checklist/list/src/main-item
 */
jn.define('tasks/layout/checklist/list/src/main-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { Indent, Color } = require('tokens');
	const { useCallback } = require('utils/function');
	const { ItemMembers } = require('tasks/layout/checklist/list/src/actions/members');
	const { ItemAttachments } = require('tasks/layout/checklist/list/src/actions/attachments');
	const { CheckBoxCounter } = require('tasks/layout/checklist/list/src/checkbox/checkbox-counter');
	const { ButtonRemove } = require('tasks/layout/checklist/list/src/buttons/button-remove');
	const { BaseChecklistItem } = require('tasks/layout/checklist/list/src/base-item');

	/**
	 * @class MainChecklistItem
	 */
	class MainChecklistItem extends BaseChecklistItem
	{
		constructor(props)
		{
			super(props);

			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);

			/** @type {CheckBoxCounter} */
			this.counterRef = null;
			/** @type {ButtonRemove} */
			this.buttonRemoveRef = null;
		}

		render()
		{
			return this.renderContent([
				View(
					{
						testId: 'checklist_entity_title',
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
						new ButtonRemove({
							ref: (buttonRemoveRef) => {
								this.buttonRemoveRef = buttonRemoveRef;
							},
							onClick: useCallback(this.handleOnRemove),
						}),
					),
					this.renderActions(),
				),
			]);
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
			const marginLeft = 34 + this.getLeftShift();

			return View(
				{
					style: {
						display: this.isShowActionRow() ? 'flex' : 'none',
						marginTop: this.isShowActionRow() ? Indent.M : 0,
						flexDirection: 'row',
						marginLeft,
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
				testId: this.getTestId('users'),
				onClick: openUserSelectionManager,
			});
		}

		/**
		 * @private
		 * @returns {ItemAttachments}
		 */
		renderAttachments()
		{
			const { item, parentWidget, diskConfig, onChangeAttachments } = this.props;

			return new ItemAttachments({
				ref: useCallback((ref) => {
					this.attachmentsRef = ref;
				}),
				testId: this.getTestId('file'),
				item,
				diskConfig,
				parentWidget,
				onChange: onChangeAttachments,
			});
		}

		/**
		 * @protected
		 * @param {CheckListFlatTreeItem} item
		 */
		getPlaceholder(item)
		{
			return Loc.getMessage('TASKSMOBILE_LAYOUT_ITEM_INPUT_PLACEHOLDER');
		}

		/**
		 * @private
		 * @returns {CheckBoxCounter|null}
		 */
		renderCheckbox()
		{
			const { item } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						marginRight: 6,
						marginLeft: this.getLeftShift(),
						alignItems: 'center',
					},
				},
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

		getTextFieldStyle()
		{
			const { item } = this.props;

			return {
				fontWeight: '400',
				color: item.getIsComplete() ? Color.base5 : Color.base1,
			};
		}

		getLeftShift()
		{
			const { item } = this.props;

			return item.getDepth() * 18;
		}

		handleOnToggleComplete()
		{
			const { item, onToggleComplete } = this.props;

			Haptics.impactLight();

			item.toggleComplete();
			this.toggleCompleteText();

			if (item.getIsComplete())
			{
				this.textInputBlur();
			}

			onToggleComplete(item);
		}

		handleOnChangeTitle(title)
		{
			const { item } = this.props;

			item.setTitle(title);
			item.setIsNew(false);
			this.handleOnChange();
		}

		handleOnBlur()
		{
			if (this.buttonRemoveRef)
			{
				this.buttonRemoveRef.hide();
			}

			super.handleOnBlur();
		}

		handleOnFocus()
		{
			const { item, showMenu } = this.props;

			if (this.buttonRemoveRef)
			{
				this.buttonRemoveRef.show();
			}

			if (showMenu)
			{
				showMenu(item);
			}

			super.handleOnFocus(item);
		}

		handleOnSubmit()
		{
			if (this.textRef.getTextValue())
			{
				super.handleOnSubmit();
			}
			else
			{
				this.textInputBlur();
			}
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
				this.textRef.reload();
			}
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

		updateProgress(progressParams)
		{
			if (!this.counterRef)
			{
				return Promise.resolve();
			}

			return this.counterRef.updateProgress(progressParams);
		}
	}

	module.exports = { MainChecklistItem };
});
