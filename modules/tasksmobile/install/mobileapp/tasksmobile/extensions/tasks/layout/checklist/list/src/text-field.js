/**
 * @module tasks/layout/checklist/list/src/text-field
 */
jn.define('tasks/layout/checklist/list/src/text-field', (require, exports, module) => {
	const { Color } = require('tokens');
	const { inAppUrl } = require('in-app-url');
	const { TextInput } = require('ui-system/typography/text-input');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');

	/**
	 * @class ItemTextField
	 */
	class ItemTextField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.textInputRef = null;
			this.cursorPosition = 0;

			this.#initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initState(props);
		}

		#initState(props)
		{
			this.state = {
				completed: props.item.getIsComplete(),
			};
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						marginTop: 4,
						justifyContent: 'center',
					},
					onClick: this.handleOnClickView,
				},
				this.#renderTextField(),
			);
		}

		handleOnClickView = () => {
			const { enable, showToastNoRights } = this.props;
			if (!enable)
			{
				showToastNoRights();
			}
		};

		handleOnChange = (title) => {
			const { onChangeText } = this.props;

			if (onChangeText)
			{
				onChangeText(title);
			}
		};

		handleOnSubmit = () => {
			const { onSubmit } = this.props;

			if (onSubmit)
			{
				onSubmit();
			}
		};

		handleOnLinkClick = ({ url }) => {
			inAppUrl.open(url);
		};

		handleOnFocus = () => {
			const { onFocus, item, enable } = this.props;
			const title = item.getTitle();

			this.cursorPosition = title.length;

			if (onFocus && enable)
			{
				onFocus();
			}
		};

		handleOnBlur = () => {
			const { onBlur } = this.props;
			const value = this.getTextValue();

			if (value)
			{
				this.setState({}, onBlur);
			}
			else
			{
				onBlur();
			}
		};

		handleOnRef = (ref) => {
			if (!ref)
			{
				return;
			}

			this.textInputRef = ref;

			const { isFocused, item } = this.props;

			if (isFocused && !item.hasItemTitle())
			{
				if (item.getIndex() === 1)
				{
					setTimeout(() => {
						this.focus();
					}, 500);
				}
				else
				{
					this.focus();
				}
			}
		};

		focus()
		{
			const { enable } = this.props;

			if (this.textInputRef && enable)
			{
				this.textInputRef.focus();
			}
		}

		blur()
		{
			if (this.textInputRef)
			{
				this.textInputRef.blur({
					hideKeyboard: true,
				});
			}
		}

		/**
		 * @private
		 */
		isFocused()
		{
			if (this.textInputRef)
			{
				return this.textInputRef.isFocused();
			}

			return false;
		}

		/**
		 * @public
		 */
		clear()
		{
			if (this.textInputRef)
			{
				this.textInputRef.clear();
			}
		}

		toggleCompleted()
		{
			const { item } = this.props;

			this.setState({
				completed: item.getIsComplete(),
			});
		}

		#renderTextField()
		{
			const { placeholder, header, textSize, enable = true } = this.props;

			return TextInput({
				size: textSize,
				header,
				enable,
				multiline: true,
				placeholder,
				ref: this.handleOnRef,
				placeholderTextColor: Color.base4.toHex(),
				color: Color.base1,
				style: this.getStyle(),
				forcedValue: this.getValue(),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				returnKeyType: this.getReturnKeyType(),
				onSubmitEditing: this.handleOnSubmit,
				onChangeText: this.handleOnChange,
				onSelectionChange: ({ selection }) => {
					this.cursorPosition = selection.start;
				},
				onLinkClick: this.handleOnLinkClick,
			});
		}

		getReturnKeyType()
		{
			const { item } = this.props;

			return item.isRoot() ? 'done' : null;
		}

		getValue()
		{
			const { item } = this.props;

			return this.parseTextValue(item.getTitle());
		}

		parseTextValue(value)
		{
			const plainTextFormatter = new PlainTextFormatter({
				diskRenderType: 'link',
				mentionRenderType: 'text',
				tableRenderType: 'placeholder',
				codeRenderType: 'text',
				listRenderType: 'text',
				allowedTags: ['url'],
			});

			const plainAst = plainTextFormatter.format({
				source: value,
			});

			return plainAst.toString({ encode: false });
		}

		getStyle()
		{
			const { item, style = {} } = this.props;
			const completed = item.getIsComplete();

			return {
				textAlignVertical: 'center',
				opacity: completed ? 0.6 : 1,
				...style,
			};
		}

		getCursorPosition()
		{
			return this.cursorPosition;
		}

		getTextValue()
		{
			return this.textInputRef.getTextValue();
		}

		setSelection()
		{
			const titleLength = this.getValue().length;

			if (!titleLength || !this.textInputRef)
			{
				return;
			}

			this.textInputRef.setSelection(titleLength, titleLength);
		}
	}

	module.exports = { ItemTextField };
});
