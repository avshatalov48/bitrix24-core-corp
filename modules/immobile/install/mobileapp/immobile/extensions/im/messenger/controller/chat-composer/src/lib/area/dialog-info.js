/**
 * @module im/messenger/controller/chat-composer/lib/area/dialog-info
 */
jn.define('im/messenger/controller/chat-composer/lib/area/dialog-info', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Indent } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { StringInput, InputSize, InputDesign, InputMode } = require('ui-system/form/inputs/string');
	const { TextAreaInput } = require('ui-system/form/inputs/textarea');
	const { AvatarButton } = require('im/messenger/controller/chat-composer/lib/element/avatar-button');

	/**
	 * @class DialogInfo
	 * @typedef {LayoutComponent<EntityInfoProps, EntityInfoState>} DialogInfo
	 */
	class DialogInfo extends LayoutComponent
	{
		/**
		 * @param {EntityInfoProps} props
		 */
		constructor(props)
		{
			super(props);
			const description = props?.description?.value ?? null;
			this.state = { title: props.title.value, description };
			this.bindMethods();
		}

		bindMethods()
		{
			this.onChangeTitle = this.onChangeTitle.bind(this);
			this.onChangeDesc = this.onChangeDesc.bind(this);
			this.onFocusTitle = this.onFocusTitle.bind(this);
			this.onFocusDesc = this.onFocusDesc.bind(this);
		}

		componentWillReceiveProps(props)
		{
			/*
			 * this check is needed to handle two scenarios:
			 * 1 - receive props when the state does not need to be updated (mutation in another nested component,
			 * and we will be sure of the current state with all the symbols)
			 * 2 - receive props when it is necessary to update the state (click the "done" button)
			 */
			if (props.shouldForceUpdateState)
			{
				const description = props?.description?.value ?? null;
				this.setState({ ...this.state, title: props.title.value, description });
			}
		}

		render()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginBottom: Indent.XL.toNumber(),
						},
					},
					this.renderAvatarButton(),
					this.renderTitleInput(),
				),
				this.renderDescriptionInput(),
			);
		}

		renderAvatarButton()
		{
			return View(
				{
					style: {
						marginRight: Indent.XL3.toNumber(),
					},
				},
				new AvatarButton(this.props.avatar),
			);
		}

		renderTitleInput()
		{
			return View(
				{
					style: {
						flexGrow: 2,
					},
				},
				StringInput({
					forwardRef: this.handleOnRefTitle,
					testId: 'title-input',
					placeholder: this.props.title.placeholder,
					size: InputSize.L,
					mode: InputMode.STROKE,
					design: InputDesign.LIGHT_GREY,
					onChange: this.onChangeTitle,
					onFocus: this.onFocusTitle,
					value: this.state.title,
				}),
			);
		}

		handleOnRefTitle = (ref) => {
			this.inputTitleRef = ref;
		};

		handleOnRefDesc = (ref) => {
			this.inputDescRef = ref;
		};

		isDescriptionRendering()
		{
			return Type.isPlainObject(this.props.description);
		}

		renderDescriptionInput()
		{
			if (this.state.description === null)
			{
				return null;
			}

			if (!this.isDescriptionRendering())
			{
				return null;
			}

			return View(
				{},
				TextAreaInput({
					testId: 'description-input',
					height: 90,
					showCharacterCount: false,
					forwardRef: this.handleOnRefDesc,
					placeholder: this.props.description.placeholder,
					size: InputSize.L,
					mode: InputMode.STROKE,
					design: InputDesign.LIGHT_GREY,
					onChange: this.onChangeDesc,
					onFocus: this.onFocusDesc,
					value: this.state.description,
					label: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_INFO_DESCRIPTION_INPUT_LABEL'),
					multiline: true,
				}),
			);
		}

		/**
		 * @param {string} text
		 */
		onChangeTitle(text)
		{
			this.setState({ ...this.state, title: text });

			this.props.title.onChange(
				{
					title: this.state.title,
					description: this.state.description,
					isInputChanged: this.getIsInputChanged(),
					inputRef: this.inputTitleRef,
				},
			);
		}

		onFocusTitle()
		{
			this.props.title.onFocusTitle({ inputRef: this.inputTitleRef });
		}

		onFocusDesc()
		{
			this.props.description.onFocusDesc({ inputRef: this.inputDescRef });
		}

		/**
		 * @param {string} text
		 */
		onChangeDesc(text)
		{
			this.setState({ ...this.state, description: text });

			this.props.description.onChange(
				{
					title: this.state.title,
					description: this.state.description,
					isInputChanged: this.getIsInputChanged(),
					inputRef: this.inputDescRef,
				},
			);
		}

		getIsInputChanged()
		{
			if (this.state.description === null) // this case is only possible when you only have permission to manage UI
			{
				if (this.state.title !== this.props.title.value)
				{
					return true;
				}

				if (this.state.title === this.props.title.value)
				{
					return false;
				}
			}
			else
			{
				if (this.state.description !== this.props.description.value
					|| this.state.title !== this.props.title.value)
				{
					return true;
				}

				if (this.state.description === this.props.description.value
					&& this.state.title === this.props.title.value)
				{
					return false;
				}
			}

			return false;
		}
	}

	module.exports = { DialogInfo };
});
