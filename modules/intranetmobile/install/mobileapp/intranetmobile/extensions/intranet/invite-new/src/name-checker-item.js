/**
 * @module intranet/invite-new/src/name-checker-item
 */
jn.define('intranet/invite-new/src/name-checker-item', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent, Corner, Component } = require('tokens');
	const { Input, InputMode, InputSize, InputDesign } = require('ui-system/form/inputs/input');
	const { getFormattedNumber } = require('utils/phone');
	const { Text5 } = require('ui-system/typography/text');
	const { Type } = require('type');
	const { NameCheckerItemAvatar } = require('intranet/invite-new/src/name-checker-item-avatar');
	const { debounce } = require('utils/function');

	class NameCheckerItem extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.avatarRef = null;
			this.firstName = this.user.firstName ?? '';
			this.secondName = this.user.secondName ?? '';

			this.firstNameInputOnChangeDebounce = debounce(this.#firstNameInputOnChange, 500, this);
			this.secondNameInputOnChangeDebounce = debounce(this.#secondNameInputOnChange, 500, this);
			this.bindFirstNameInputRef = this.bindFirstNameInputRef.bind(this);
			this.bindSecondNameInputRef = this.bindSecondNameInputRef.bind(this);
		}

		componentDidMount()
		{
			super.componentDidMount();
			if (this.props.onDidMount)
			{
				this.props.onDidMount(this);
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

		getInputsRefs()
		{
			return [
				this.firstNameInputRef,
				this.secondNameInputRef,
			];
		}

		updateAvatar()
		{
			if (this.avatarRef)
			{
				this.avatarRef.update({
					firstName: this.firstName,
					secondName: this.secondName,
				});
			}
		}

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
			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						paddingBottom: Component.cardListGap.toNumber(),
					},
				},
				this.#renderAvatar(),
				this.#renderContent(),
			);
		}

		#renderAvatar()
		{
			return new NameCheckerItemAvatar({
				ref: (ref) => {
					this.avatarRef = ref;
				},
				index: this.props.index,
				size: 36,
				firstName: this.firstName,
				secondName: this.secondName,
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
				this.#renderFormattedPhone(),
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
						width: '100%',
					},
				},
				Input({
					testId: `${this.testId}-first-name-input`,
					size: InputSize.M,
					design: InputDesign.PRIMARY,
					mode: InputMode.NAKED,
					multiline: false,
					align: 'left',
					returnKeyType: 'done',
					placeholder: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_ITEM_FIRST_NAME_PLACEHOLDER'),
					value: this.firstName ?? '',
					ref: this.bindFirstNameInputRef,
					onChange: this.firstNameInputOnChangeDebounce,
					onFocus: this.#firstNameInputOnFocus,
					onSubmit: this.#firstNameInputOnSubmit,
					isScrollEnabled: false,
				}),
				this.#renderFieldsSeparator(),
				Input({
					testId: `${this.testId}-second-name-input`,
					size: InputSize.M,
					design: InputDesign.PRIMARY,
					mode: InputMode.NAKED,
					multiline: false,
					align: 'left',
					returnKeyType: 'done',
					placeholder: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_ITEM_SECOND_NAME_PLACEHOLDER'),
					value: this.secondName ?? '',
					ref: this.bindSecondNameInputRef,
					onChange: this.secondNameInputOnChangeDebounce,
					onFocus: this.#secondNameInputOnFocus,
					onSubmit: this.#secondNameInputOnSubmit,
					isScrollEnabled: false,
				}),
			);
		}

		bindFirstNameInputRef = (ref) => {
			this.firstNameInputRef = ref;
		};

		bindSecondNameInputRef = (ref) => {
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

		#firstNameInputOnChange(value)
		{
			this.firstName = value;
			this.updateAvatar();
			if (Type.isFunction(this.props.onChange))
			{
				this.props.onChange(this.getData());
			}
		}

		#secondNameInputOnChange(value)
		{
			this.secondName = value;
			this.updateAvatar();
			if (Type.isFunction(this.props.onChange))
			{
				this.props.onChange(this.getData());
			}
		}

		#renderFieldsSeparator()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: Color.bgSeparatorPrimary.toHex(),
				},
			});
			/* return View(
				{
					style: {
						height: 1,
						width: '100%',
						paddingLeft: Indent.XL.toNumber(),
					},
				},
				View({
					style: {
						height: 1,
						width: '100%',
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
					},
				}),
			); */
		}

		#renderFormattedPhone()
		{
			return Text5({
				testId: `${this.testId}-formatted-phone-text`,
				text: Loc.getMessage('INTRANET_INVITE_FORMATTED_PHONE_TEXT', {
					'#phone#': getFormattedNumber(this.user.formattedPhone),
				}),
				color: Color.base3,
				numberOfLines: 0,
				ellipsize: 'end',
			});
		}
	}

	module.exports = { NameCheckerItem };
});
