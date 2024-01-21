/**
 * @module tasks/layout/checklist/list/src/item
 */
jn.define('tasks/layout/checklist/list/src/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { erase } = require('assets/common');
	const { useCallback } = require('utils/function');
	const { CheckListMenu } = require('tasks/layout/checklist/list/src/menu');
	const { UserSelectionManager } = require('layout/ui/user-selection-manager');
	const { ItemAttachments } = require('tasks/layout/checklist/list/src/attachments');
	const { ItemTextField } = require('tasks/layout/checklist/list/src/text-field');
	const { CheckBoxCounter } = require('tasks/layout/checklist/list/src/checkbox-counter');

	const borderColor = AppTheme.colors.bgSeparatorSecondary;
	const ERASE_SIZE = 28;

	/**
	 * @class Item
	 */
	class Item extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ItemAttachments} */
			this.attachmentsRef = null;
			/** @type {CheckBoxCounter} */
			this.counterRef = null;
			/** @type {ItemTextField} */
			this.textRef = null;

			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);
			this.handleOnChangeTitle = this.handleOnChangeTitle.bind(this);
			this.handleOnRemove = this.handleOnRemove.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnToggleImportant = this.handleOnToggleImportant.bind(this);
			this.handleOnMoveToCheckList = this.handleOnMoveToCheckList.bind(this);
			this.openUserSelectionManager = this.openUserSelectionManager.bind(this);
		}

		render()
		{
			const { item, isFocused } = this.props;

			return View(
				{
					testId: `checkListItem-${item.getNodeId()}`,
					style: {
						flexDirection: 'column',
						paddingTop: 16,
						paddingHorizontal: 18,
					},
				},
				View(
					{
						style: {
							marginLeft: item.getDepth(),
							paddingBottom: 16,
							borderBottomWidth: 1,
							borderBottomColor: borderColor,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'flex-start',

							},
						},
						this.renderCheckbox(),
						this.renderTextField(),
						isFocused && this.renderDeleteButton(),
					),
					this.renderAttachments(),
					isFocused && this.renderMenu(),
				),
			);
		}

		renderDeleteButton()
		{
			return Image({
				style: {
					width: ERASE_SIZE,
					height: ERASE_SIZE,
				},
				svg: {
					content: erase(),
				},
				resizeMode: 'center',
				onClick: () => {
					this.eraseItem();
				},
			});
		}

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
				onChange: this.handleOnChange,
			});
		}

		renderTextField()
		{
			const { item, parentWidget, isFocused } = this.props;

			return new ItemTextField({
				ref: useCallback((ref) => {
					this.textRef = ref;
				}),
				parentWidget,
				isFocused,
				completed: item.getIsComplete(),
				title: useCallback(() => item.getTitle()),
				members: item.getMembers(),
				onFocus: this.handleOnFocus,
				onSubmit: this.handleOnSubmit,
				onChangeText: this.handleOnChangeTitle,
				styles: {
					fontWeight: item.isRoot() ? '500' : '400',
				},
			});
		}

		renderMenu()
		{
			const { item } = this.props;

			if (item.isRoot())
			{
				return null;
			}

			const { parentWidget, onShowSelector, onListToggle, onTabMove } = this.props;

			return new CheckListMenu({
				item,
				onTabMove,
				parentWidget,
				onListToggle,
				onShowSelector,
				extendedMenu: true,
				onMoveToCheckList: this.handleOnMoveToCheckList,
				onToggleImportant: this.handleOnToggleImportant,
				openUserSelectionManager: this.openUserSelectionManager,
				onAddFile: useCallback(() => this.attachmentsRef.addFile()),
			});
		}

		openUserSelectionManager()
		{
			const { item, parentWidget } = this.props;

			const sectionsData = {
				auditor: {
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_AUDITOR'),
					addButtonText: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_AUDITOR'),
				},
				accomplice: {
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_ACCOMPLICE'),
					addButtonText: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ACCOMPLICE'),
				},
			};
			const members = item.getMembers();
			const users = Object.keys(item.getMembers()).map((id) => {
				const { type, name = '', avatar = '' } = members[id];

				return {
					id: Number(id),
					section: item.getMemberType(type),
					title: name,
					image: avatar,
				};
			});

			UserSelectionManager.open({ users, sectionsData, parentWidget });
		}

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
				new CheckBoxCounter({
					ref: useCallback((ref) => {
						this.counterRef = ref;
					}),
					checked: item.getIsComplete(),
					progressMode: item.getDescendantsCount(),
					onClick: this.handleOnToggleComplete,
					isDisabled: !item.checkCanToggle(),
					totalCount: item.getDescendantsCount(),
					completedCount: item.getCompleteCount(),
					isImportant: item.getIsImportant(),
				}),
			);
		}

		handleOnMoveToCheckList()
		{
			const { item, onMoveToCheckList } = this.props;
			if (onMoveToCheckList)
			{
				onMoveToCheckList(item.getMoveIds());
			}
		}

		handleOnChangeTitle(title)
		{
			const { item } = this.props;

			item.setTitle(title);
			this.handleOnChange();
		}

		handleOnToggleComplete()
		{
			const { item, onToggleComplete } = this.props;

			Haptics.impactLight();
			item.toggleComplete();

			onToggleComplete(item);
		}

		handleOnToggleImportant()
		{
			const { item } = this.props;
			item.toggleImportant(!item.getIsImportant());

			this.counterRef.toggleAnimateImportant(item.getIsImportant())
				.then(() => {
					this.handleOnChange();
				})
				.catch(console.error);
		}

		eraseItem()
		{
			const { item } = this.props;
			if (item.getTitle())
			{
				this.handleOnChangeTitle('');
				this.textRef.clear();
			}
			else
			{
				this.handleOnRemove();
			}
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

		handleOnFocus()
		{
			const { item, onFocus } = this.props;

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

		updateProgress(progressParams)
		{
			if (!this.counterRef)
			{
				return Promise.resolve();
			}

			return this.counterRef.updateProgress(progressParams);
		}
	}

	module.exports = { Item };
});

