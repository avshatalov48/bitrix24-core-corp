/**
 * @module tasks/layout/checklist/list/src/text-field
 */
jn.define('tasks/layout/checklist/list/src/text-field', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Random } = require('utils/random');
	const { throttle } = require('utils/function');

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

			this.handleOnSubmit = throttle(this.handleOnSubmit, 500, this);
			this.handleOnChange = throttle(this.handleOnChange, 200, this);
			this.handleOnLayout = this.handleOnLayout.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnBlur = this.handleOnBlur.bind(this);
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
				},
				this.renderTextField(),
			);
		}

		handleOnChange(title)
		{
			const { onChangeText } = this.props;

			if (onChangeText)
			{
				onChangeText(title);
			}
		}

		handleOnSubmit()
		{
			const { onSubmit } = this.props;

			if (onSubmit)
			{
				onSubmit();
			}
		}

		handleOnLayout()
		{
			const { isFocused } = this.props;

			if (isFocused && this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		handleOnFocus()
		{
			const { onFocus, item } = this.props;

			const title = item.getTitle();
			this.cursorPosition = title.length;

			if (onFocus)
			{
				onFocus();
			}
		}

		handleOnBlur()
		{
			const { onBlur } = this.props;

			if (onBlur)
			{
				onBlur();
			}
		}

		focus()
		{
			if (this.textInputRef)
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

		reload()
		{
			this.setState({ reload: Random.getString() });
		}

		renderTextField()
		{
			const { item, style = {}, placeholder } = this.props;

			if (!item.isRoot())
			{
				style.color = this.getTitleColor();
			}

			return TextInput({
				ref: (ref) => {
					this.textInputRef = ref;
				},
				onLayout: this.handleOnLayout,
				placeholder,
				placeholderTextColor: Color.base4,
				style: {
					fontSize: 16,
					textAlignVertical: 'center',
					...style,
				},
				multiline: true,
				forcedValue: this.getTitle(),
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				returnKeyType: this.getReturnKeyType(),
				onSubmitEditing: this.handleOnSubmit,
				onChangeText: this.handleOnChange,
				onSelectionChange: ({ selection }) => {
					this.cursorPosition = selection.start;
				},
			});
		}

		renderBBCodeText()
		{
			const { style = {} } = this.props;

			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: Color.base3,
						textAlignVertical: 'center',
						...style,
					},
					focus: false,
					linksUnderline: false,
					value: this.getTitleWithUrls(),
				}),
			);
		}

		getReturnKeyType()
		{
			const { item } = this.props;

			return item.isRoot() ? 'done' : null;
		}

		getTitleWithUrls(title, members)
		{
			let newTitle = title;
			Object.keys(members).forEach((id) => {
				const { name } = members[id];
				newTitle = newTitle.replace(
					name,
					`[COLOR=${Color.accentMainLinks}][URL=${id}]${name}[/URL][/COLOR]`,
				);
			});
		}

		getTitle()
		{
			const { item } = this.props;

			return item.getTitle();
		}

		getTitleColor()
		{
			const { item } = this.props;
			const completed = item.getIsComplete();

			return completed ? Color.base5 : Color.base1;
		}

		getCursorPosition()
		{
			return this.cursorPosition;
		}

		getTextValue()
		{
			return this.textInputRef.getTextValue();
		}
	}

	module.exports = { ItemTextField };
});
