/**
 * @module layout/ui/src/name-checker-item
 */
jn.define('layout/ui/src/name-checker-item', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent, Corner } = require('tokens');
	const { Input, InputMode, InputSize, InputDesign } = require('ui-system/form/inputs/input');
	const { Text5 } = require('ui-system/typography/text');
	const { Type } = require('type');
	const { NameCheckerItemAvatar } = require('layout/ui/src/name-checker-item-avatar');
	const { Area } = require('ui-system/layout/area');

	class NameCheckerItem extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.avatarRef = null;
			this.firstName = this.user.firstName ?? '';
			this.secondName = this.user.secondName ?? '';
		}

		componentDidMount()
		{
			super.componentDidMount();
			if (this.props.onDidMount)
			{
				this.props.onDidMount(this, this.index);
			}
		}

		get testId()
		{
			return 'name-checker-item';
		}

		get user()
		{
			return this.props?.user ?? {};
		}

		get getItemFormattedSubDescription()
		{
			return this.props.getItemFormattedSubDescription ?? '';
		}

		get index()
		{
			return this.props.index ?? 0;
		}

		get avatarEntityType()
		{
			return this.props.avatarEntityType;
		}

		#getTestId = (suffix) => {
			const prefix = this.testId;

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		getInputsRefs()
		{
			return [
				this.firstNameInputRef,
				this.secondNameInputRef,
			];
		}

		updateAvatar()
		{
			this.avatarRef?.update({
				firstName: this.firstName,
				secondName: this.secondName,
			});
		}

		#bindAvatarRef = (ref) => {
			this.avatarRef = ref;
		};

		getData()
		{
			return {
				...this.user,
				firstName: this.firstName,
				secondName: this.secondName,
			};
		}

		render()
		{
			return Area(
				{
					testId: this.#getTestId('info-area'),
					excludePaddingSide: {
						bottom: true,
					},
					style: {
						flexDirection: 'row',
						marginBottom: Indent.XL.toNumber(),
					},
				},
				this.#renderAvatar(),
				this.#renderContent(),
			);
		}

		#renderAvatar()
		{
			return new NameCheckerItemAvatar({
				ref: this.#bindAvatarRef,
				index: this.index,
				size: 36,
				firstName: this.firstName,
				secondName: this.secondName,
				avatarEntityType: this.avatarEntityType,
			});
		}

		#renderContent()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.#renderFields(),
				this.#renderFormattedSubDescription(),
			);
		}

		#renderFields()
		{
			return View(
				{
					style: {
						borderColor: Color.bgSeparatorPrimary.toHex(),
						borderWidth: 1,
						borderRadius: Corner.L.toNumber(),
						marginBottom: Indent.XS.toNumber(),
						paddingLeft: Indent.XL.toNumber(),
						paddingVertical: Indent.XS.toNumber(),
						width: '100%',
					},
				},
				Input({
					testId: this.#getTestId('first-name-input'),
					size: InputSize.M,
					design: InputDesign.PRIMARY,
					mode: InputMode.NAKED,
					multiline: false,
					align: 'left',
					returnKeyType: 'done',
					placeholder: Loc.getMessage('NAME_CHECKER_ITEM_FIRST_NAME_PLACEHOLDER'),
					value: this.firstName ?? '',
					ref: this.#bindFirstNameInputRef,
					onChange: this.#firstNameInputOnChange,
					onFocus: this.#firstNameInputOnFocus,
					onSubmit: this.#firstNameInputOnSubmit,
					isScrollEnabled: false,
				}),
				this.#renderFieldsSeparator(),
				Input({
					testId: this.#getTestId('second-name-input'),
					size: InputSize.M,
					design: InputDesign.PRIMARY,
					mode: InputMode.NAKED,
					multiline: false,
					align: 'left',
					returnKeyType: 'done',
					placeholder: Loc.getMessage('NAME_CHECKER_ITEM_SECOND_NAME_PLACEHOLDER'),
					value: this.secondName ?? '',
					ref: this.#bindSecondNameInputRef,
					onChange: this.#secondNameInputOnChange,
					onFocus: this.#secondNameInputOnFocus,
					onSubmit: this.#secondNameInputOnSubmit,
					isScrollEnabled: false,
				}),
			);
		}

		#bindFirstNameInputRef = (ref) => {
			this.firstNameInputRef = ref;
		};

		#bindSecondNameInputRef = (ref) => {
			this.secondNameInputRef = ref;
		};

		#firstNameInputOnSubmit = () => {
			if (this.props.onInputSubmit)
			{
				this.props.onInputSubmit(this.firstNameInputRef);
			}
		};

		#secondNameInputOnSubmit = () => {
			if (this.props.onInputSubmit)
			{
				this.props.onInputSubmit(this.secondNameInputRef);
			}
		};

		#firstNameInputOnFocus = () => {
			if (this.props.onInputFocus)
			{
				this.props.onInputFocus(this.firstNameInputRef);
			}
		};

		#secondNameInputOnFocus = () => {
			if (this.props.onInputFocus)
			{
				this.props.onInputFocus(this.secondNameInputRef);
			}
		};

		#firstNameInputOnChange = (value) => {
			this.firstName = value;
			this.updateAvatar();
			if (Type.isFunction(this.props.onChange))
			{
				this.props.onChange(this.getData());
			}
		};

		#secondNameInputOnChange = (value) => {
			this.secondName = value;
			this.updateAvatar();
			if (Type.isFunction(this.props.onChange))
			{
				this.props.onChange(this.getData());
			}
		};

		#renderFieldsSeparator()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: Color.bgSeparatorPrimary.toHex(),
				},
			});
		}

		#renderFormattedSubDescription()
		{
			const text = this.getItemFormattedSubDescription?.(this.user) ?? '';

			return Text5({
				testId: this.#getTestId('formatted-phone-text'),
				text,
				color: Color.base3,
				numberOfLines: 0,
				ellipsize: 'end',
				style: {
					marginTop: Indent.XS2.toNumber(),
				},
			});
		}
	}

	module.exports = { NameCheckerItem };
});
