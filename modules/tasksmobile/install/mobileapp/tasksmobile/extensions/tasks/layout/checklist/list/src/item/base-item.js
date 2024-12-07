/**
 * @module tasks/layout/checklist/list/src/base-item
 */
jn.define('tasks/layout/checklist/list/src/base-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Random } = require('utils/random');
	const { ItemTextField } = require('tasks/layout/checklist/list/src/text-field');
	const { ChecklistItemView } = require('tasks/layout/checklist/list/src/layout/item-view');
	const { confirmDestructiveAction } = require('alert');

	/**
	 * @class BaseChecklistItem
	 */
	class BaseChecklistItem extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {CheckListFlatTreeItem} [props.item]
		 * @param {boolean} [props.isFocused]
		 */
		constructor(props)
		{
			super(props);

			/** @type {ItemTextField} */
			this.textRef = null;
			/** @type {ItemAttachments} */
			this.attachmentsRef = null;

			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnChangeTitle = this.handleOnChangeTitle.bind(this);
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
		}

		/**
		 * @protected
		 * @abstract
		 */
		render()
		{
			return null;
		}

		renderContent(itemProps)
		{
			const { item } = this.props;

			return ChecklistItemView({
				testId: this.getTestId(`depth-${item.getDepth()}`),
				divider: !item.isFirstListDescendant(),
				...itemProps,
			});
		}

		/**
		 * @protected
		 * @returns {ItemTextField}
		 */
		renderTextField()
		{
			const { item, parentWidget, isFocused, showToastNoRights } = this.props;

			return new ItemTextField({
				ref: (ref) => {
					this.textRef = ref;
				},
				item,
				isFocused,
				parentWidget,
				showToastNoRights,
				enable: this.canUpdateItem(),
				placeholder: this.getPlaceholder(item),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				onSubmit: this.handleOnSubmit,
				onChangeText: this.handleOnChangeTitle,
				...this.getTextFieldStyle(),
			});
		}

		/**
		 * @protected
		 * @abstract
		 */
		getTextFieldStyle()
		{
			return {};
		}

		/**
		 * @protected
		 * @abstract
		 * @param {CheckListFlatTreeItem} item
		 */
		getPlaceholder(item)
		{
			return '';
		}

		handleOnChangeTitle(title)
		{
			const { item } = this.props;

			item.setTitle(title);
			item.setIsNew(false);
			this.handleOnChange();
		}

		handleOnChange()
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange();
			}
		}

		handleOnBlur(blurProps)
		{
			const { onBlur, item } = this.props;

			if (onBlur)
			{
				onBlur({ item });
			}
		}

		handleOnSubmit()
		{
			const { onSubmit, item } = this.props;

			if (onSubmit)
			{
				onSubmit(item);
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

		handleOnRemove = () => {
			const { item, onRemove, onBlur } = this.props;
			const removeAction = item.hasItemTitle() ? onRemove : onBlur;

			const remove = (forceDelete) => {
				if (removeAction)
				{
					removeAction({ item, forceDelete });
				}
			};

			if (!item.shouldRemove())
			{
				confirmDestructiveAction({
					title: '',
					description: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REMOVE_ITEM'),
					onDestruct: () => {
						remove(true);
					},
				});

				return;
			}

			remove(true);
		};

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

		addFile()
		{
			this.attachmentsRef.addFile();
		}

		getTestId(suffix)
		{
			const { item } = this.props;

			const prefix = `checklistItem_id-${item.getId()}`;

			return suffix ? `${prefix}_${suffix}` : prefix;
		}

		/**
		 * @param {number} additionalShift
		 * @return {number}
		 */
		getLeftShift(additionalShift = 0)
		{
			const { item } = this.props;

			return (item.getDepth() * 18) + additionalShift;
		}

		toggleCompleteText()
		{
			if (this.textRef)
			{
				this.textRef.toggleCompleted();
			}
		}

		reload()
		{
			this.setState({
				random: Random.getString(),
			});
		}

		canUpdateItem()
		{
			const { item } = this.props;

			return item.checkCanUpdate();
		}

		canRemoveItem()
		{
			const { item } = this.props;

			return item.checkCanRemove();
		}
	}

	module.exports = { BaseChecklistItem };
});
