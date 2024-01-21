/**
 * @module tasks/layout/checklist/list/src/text-field
 */
jn.define('tasks/layout/checklist/list/src/text-field', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { animate } = require('animation');
	const { ProfileView } = require('user/profile');
	const { throttle } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class ItemTextField
	 */
	class ItemTextField extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.ref = null;
			this.textFieldRef = null;
			this.handleOnFocus = throttle(this.handleOnFocus, 500, this);
		}

		heightAnimate(height)
		{
			if (!this.getTitle())
			{
				return;
			}

			animate(this.ref, {
				height: Math.max(height, 28),
			});
		}

		render()
		{
			const { isFocused } = this.props;

			return View(
				{
					ref: (ref) => {
						this.ref = ref;
					},
					style: {
						flex: 1,
						height: 'auto',
						marginLeft: 6,
						justifyContent: 'center',
					},
				},
				isFocused ? this.renderTextField() : this.renderBBCodeText(),
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

		blur()
		{
			if (this.textFieldRef)
			{
				this.textFieldRef.blur();
			}
		}

		clear()
		{
			if (this.textFieldRef)
			{
				this.textFieldRef.clear();
			}
		}

		focus()
		{
			if (!this.textFieldRef || this.textFieldRef.isFocused())
			{
				return;
			}

			this.textFieldRef.focus();
		}

		handleOnFocus()
		{
			const { focus, onFocus } = this.props;

			if (focus)
			{
				return;
			}

			if (onFocus)
			{
				onFocus();
			}
		}

		renderTextField()
		{
			const { isFocused, onSubmit, styles = {} } = this.props;

			return TextInput({
				ref: (textFieldRef) => {
					this.textFieldRef = textFieldRef;
				},
				placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_ITEM_INPUT_PLACEHOLDER'),
				placeholderTextColor: AppTheme.colors.base4,
				focus: isFocused,
				style: {
					fontSize: 16,
					fontWeight: '400',
					color: AppTheme.colors.base1,
					textAlignVertical: 'center',
					...styles,
				},
				onLayout: () => {
					this.focus();
				},
				// showBBCode: true,
				multiline: true,
				forcedValue: this.getTitle(),
				onSubmitEditing: onSubmit,
				onChangeText: this.handleOnChange.bind(this),
			});
		}

		renderBBCodeText()
		{
			const { styles = {} } = this.props;

			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
					},
					onClick: () => {
						this.handleOnFocus();
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
						...styles,
					},
					focus: false,
					linksUnderline: false,
					value: this.getTitleWithUrls(),
					onLinkClick: this.onLinkClick.bind(this),
				}),
			);
		}

		getTitleWithUrls()
		{
			const { members, completed } = this.props;
			const title = this.getTitle();
			let newTitle = title;

			Object.keys(members).forEach((id) => {
				const { name } = members[id];
				newTitle = newTitle.replace(name, `[COLOR=#2067b0][URL=${id}]${name}[/URL][/COLOR]`);
			});

			if (completed)
			{
				return `[S]${title}[/S]`;
			}

			return newTitle;
		}

		getTitle()
		{
			const { title } = this.props;

			return title();
		}

		onLinkClick({ url })
		{
			const { parentWidget } = this.props;

			const userId = url;
			const widgetParams = { groupStyle: true };
			const isBackdrop = true;

			widgetParams.backdrop = {
				bounceEnable: false,
				swipeAllowed: true,
				showOnTop: true,
				hideNavigationBar: false,
				horizontalSwipeAllowed: false,
			};
			parentWidget.openWidget('list', widgetParams)
				.then((list) => ProfileView.open({ userId, isBackdrop }, list))
				.catch(console.error);
		}
	}

	module.exports = { ItemTextField };
});

