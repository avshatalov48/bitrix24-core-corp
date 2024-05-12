/**
 * @module tasks/layout/checklist/list/src/base-item
 */
jn.define('tasks/layout/checklist/list/src/base-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { useCallback } = require('utils/function');
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
			this.handleOnRemove = this.handleOnRemove.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnChangeTitle = this.handleOnChangeTitle.bind(this);
		}

		/**
		 * @protected
		 * @abstract
		 */
		render()
		{
			return null;
		}

		renderContent(children)
		{
			const { item } = this.props;

			return ChecklistItemView({
				testId: this.getTestId(`depth-${item.getDepth()}`),
				divider: true,
				children,
			});
		}

		/**
		 * @protected
		 * @returns {ItemTextField}
		 */
		renderTextField()
		{
			const { item, parentWidget, isFocused } = this.props;

			return new ItemTextField({
				ref: useCallback((ref) => {
					this.textRef = ref;
				}),
				item,
				isFocused,
				parentWidget,
				placeholder: this.getPlaceholder(item),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				onSubmit: this.handleOnSubmit,
				onChangeText: useCallback(this.handleOnChangeTitle),
				style: this.getTextFieldStyle(),
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

		handleOnBlur()
		{
			const { onBlur, item } = this.props;

			if (onBlur)
			{
				onBlur(item);
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

		handleOnRemove()
		{
			const { item, onRemove } = this.props;
			const removeAction = item.hasItemTitle() ? onRemove : this.handleOnBlur;
			const remove = (force) => {
				if (onRemove)
				{
					removeAction({ item, force });
				}
			};

			if (!item.shouldRemove())
			{
				confirmDestructiveAction({
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REMOVE_ITEM'),
					onDestruct: () => {
						remove();
					},
				});

				return;
			}

			remove(true);
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

		getTestId(suffix)
		{
			const { item } = this.props;

			const prefix = `checklistItem_id-${item.getId()}`;

			return suffix ? `${prefix}_${suffix}` : prefix;
		}

		reload()
		{
			this.setState({});
		}
	}

	module.exports = { BaseChecklistItem };
});
