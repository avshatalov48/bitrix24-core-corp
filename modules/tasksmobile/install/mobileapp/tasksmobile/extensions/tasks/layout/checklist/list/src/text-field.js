/**
 * @module tasks/layout/checklist/list/src/text-field
 */
jn.define('tasks/layout/checklist/list/src/text-field', (require, exports, module) => {
	const { Color } = require('tokens');
	const { TextInput } = require('ui-system/typography/text-input');

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

		handleOnFocus = () => {
			const { onFocus, item, enable } = this.props;
			const title = item.getTitle();

			// this.setSelection();
			this.cursorPosition = title.length;

			if (onFocus && enable)
			{
				onFocus();
			}
		};

		handleOnBlur = () => {
			const { onBlur } = this.props;

			if (onBlur)
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
			const { item, style = {}, placeholder, header, textSize, enable = true } = this.props;

			if (!item.isRoot())
			{
				style.color = this.getTitleColor().toHex();
			}

			return TextInput({
				size: textSize,
				header,
				enable,
				ref: this.handleOnRef,
				placeholder,
				placeholderTextColor: Color.base4.toHex(),
				style: {
					textAlignVertical: 'center',
					color: this.getTitleColor().toHex(),
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

		/**
		 * @return {Color}
		 */
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

		setSelection()
		{
			const titleLength = this.getTitle().length;

			if (!titleLength || !this.textInputRef)
			{
				return;
			}

			this.textInputRef.setSelection(titleLength, titleLength);
		}
	}

	module.exports = { ItemTextField };
});
