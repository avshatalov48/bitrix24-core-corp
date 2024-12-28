/**
 * @module intranet/user-mini-profile-form
 */
jn.define('intranet/user-mini-profile-form', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Loc } = require('loc');
	const { isEmpty } = require('utils/object');
	const { trim } = require('utils/string');
	const { PureComponent } = require('layout/pure-component');
	const { StringInput, InputSize, InputMode, InputDesign } = require('ui-system/form/inputs/string');
	const { EmailInput, InputDomainIconPlace } = require('ui-system/form/inputs/email');
	const { PhoneInput } = require('ui-system/form/inputs/phone');
	const { Avatar, AvatarEntityType, AvatarShape } = require('ui-system/blocks/avatar');
	const { AvatarPicker } = require('avatar-picker');
	const { isPhoneNumber } = require('utils/phone');
	const { isValidEmail } = require('utils/email');
	const { AvaMenu } = require('ava-menu');
	const { phoneUtils } = require('native/phonenumber');

	class UserMiniProfileForm extends PureComponent
	{
		constructor(props)
		{
			super(props);

			const { ID, NAME = '', LAST_NAME = '', PERSONAL_MOBILE = '', EMAIL = '', PERSONAL_PHOTO = '' } = props.profileData;

			this.state.profileData = { ID, NAME, LAST_NAME, PERSONAL_MOBILE, EMAIL };
			this.state.photo = PERSONAL_PHOTO;

			this.nameField = null;
			this.lastNameField = null;
			this.phoneField = null;
			this.emailField = null;
			this.scrollToInput = props.scrollToInput;
			this.avatarPicker = new AvatarPicker();
			this.focusedInputIndex = 0;
		}

		componentDidMount()
		{
			this.inputs = [
				this.nameField,
				this.lastNameField,
				this.phoneField,
				this.emailField,
			];
			this.nameField.focus();
		}

		getProfileData = () => {
			const { profileData } = this.state;

			return profileData;
		};

		getAvatarButton()
		{
			return View(
				{
					style: {
						marginRight: Indent.XL3.toNumber(),
					},
				},
				Avatar({
					testId: 'MINI_PROFILE_AVATAR',
					type: AvatarShape.CIRCLE,
					accent: true,
					size: 84,
					entityType: env.isCollaber ? AvatarEntityType.COLLAB : AvatarEntityType.USER,
					uri: this.state.newPhoto ?? this.state.photo,
					onClick: this.showImagePicker,
					withRedux: true,
					icon: IconView({
						testId: 'MINI_PROFILE_AVATAR_ICON',
						size: 47,
						color: env.isCollaber ? Color.collabAccentPrimary : Color.accentMainPrimary,
						icon: Icon.CAMERA,
					}),
					backgroundColor: env.isCollaber ? Color.collabBgContent1 : Color.accentSoftBlue3,
				}),
			);
		}

		showImagePicker = () => {
			this.avatarPicker.open()
				.then((image) => {
					if (image)
					{
						this.setState({
							newPhoto: image.previewUrl,
						});
						const personalPhoto = [
							'avatar.png',
							image.base64,
						];
						this.onChange('PERSONAL_PHOTO', personalPhoto);
					}
				})
				.catch((err) => {
					console.error(err);
				});
		};

		getInvalidField = (profileData) => {
			const { PERSONAL_MOBILE, EMAIL } = profileData;

			if (!(isEmpty(PERSONAL_MOBILE) || this.isEmptyPhoneBody(PERSONAL_MOBILE)) && !isPhoneNumber(PERSONAL_MOBILE))
			{
				return this.phoneField;
			}

			if (!isEmpty(EMAIL) && !isValidEmail(EMAIL))
			{
				return this.emailField;
			}

			return null;
		};

		isEmptyPhoneBody = (phone) => {
			return isEmpty(phone?.replace(`+${phoneUtils.getPhoneCode(phone)}`, ''));
		};

		getEmptyRequiredField = (profileData) => {
			const { PERSONAL_MOBILE, EMAIL } = profileData;
			const prefilledMobile = this.props.profileData.PERSONAL_MOBILE;
			const prefilledEmail = this.props.profileData.EMAIL;

			if (
				!isEmpty(prefilledMobile)
				&& (
					isEmpty(PERSONAL_MOBILE) || this.isEmptyPhoneBody(PERSONAL_MOBILE)
				)
			)
			{
				return this.phoneField;
			}

			if (!isEmpty(prefilledEmail) && isEmpty(EMAIL))
			{
				return this.emailField;
			}

			return null;
		};

		getFieldToFocus = () => {
			for (let index = this.focusedInputIndex + 1; index < this.inputs.length; index++)
			{
				const input = this.inputs[index];

				if (
					input?.isEmpty()
					|| (
						input === this.phoneField
						&& this.isEmptyPhoneBody(input.getValue())
					)
				)
				{
					return this.inputs[index];
				}
			}

			return null;
		};

		onContinue = () => {
			const fieldToFocus = this.getFieldToFocus();

			if (fieldToFocus)
			{
				fieldToFocus?.focus();
			}
			else
			{
				this.focusedInputIndex = -1;
				Keyboard.dismiss();
			}
		};

		submitForm()
		{
			const fields = this.getProfileData();

			if (!fields.ID)
			{
				return Promise.reject(new Error('id is null'));
			}

			const invalidField = this.getInvalidField(fields) ?? this.getEmptyRequiredField(fields);

			if (invalidField)
			{
				invalidField?.focus();

				return Promise.reject(new Error('invalid data'));
			}

			if (this.isEmptyPhoneBody(fields.PERSONAL_MOBILE))
			{
				fields.PERSONAL_MOBILE = '';
			}

			const submitPromise = BX.ajax.runAction('intranetmobile.userprofile.saveProfile', {
				data: {
					data: fields,
				},
			});

			submitPromise
				.then(this.#syncWithAvaMenu())
				.catch((response) => {
					this.onHandleSubmitErrors(response.errors ?? []);
					throw response;
				});

			return submitPromise;
		}

		onHandleSubmitErrors(errors) {
			const errorMap = {
				WRONG_EMAIL: { emailFieldError: true },
				WRONG_PHONE: { phoneFieldError: true },
				EMAIL_EXIST: {
					emailFieldError: true,
					emailFieldErrorMessage: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_EMAIL_EXIST'),
				},
			};

			const newState = errors.reduce((acc, error) => {
				const errorState = errorMap[error.code ?? null];
				if (errorState)
				{
					return { ...acc, ...errorState };
				}

				return acc;
			}, {});

			this.setState(newState);
		}

		async #syncWithAvaMenu()
		{
			return BX.ajax.runAction('mobile.AvaMenu.getUserInfo', { data: { reloadFromDb: true } })
				.then(({ data }) => {
					AvaMenu.setUserInfo(data);
				})
				.catch(console.error);
		}

		onChange = (id, value) => {
			this.setState({
				profileData: {
					...this.getProfileData(),
					[id]: trim(value),
				},
			});
		};

		onChangeName = (value) => {
			this.onChange('NAME', value);
		};

		onChangeLastName = (value) => {
			this.onChange('LAST_NAME', value);
		};

		onChangeMobile = (value) => {
			this.onChange('PERSONAL_MOBILE', value);
		};

		onChangeEmail = (value) => {
			this.onChange('EMAIL', value);
		};

		setBlurPrimaryField = () => {
			this.setState({
				primaryFieldSelected: false,
			});
		};

		getNameField()
		{
			const inputId = 'NAME';

			return StringInput({
				size: InputSize.L,
				mode: InputMode.NAKED,
				ref: this.handleOnSetNameRef,
				testId: `USER_MINI_PROFILE_${inputId}`,
				showTitle: false,
				id: inputId,
				placeholder: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_PLACEHOLDER_NAME') ?? '',
				value: this.getProfileData().NAME ?? '',
				onFocus: this.focusNameField,
				onBlur: this.setBlurPrimaryField,
				onChange: this.onChangeName,
			});
		}

		focusNameField = () => {
			this.setState(
				{
					primaryFieldSelected: true,
				},
				() => {
					this.focusedInputIndex = this.inputs.indexOf(this.nameField);
				},
			);
		};

		handleOnSetNameRef = (ref) => {
			this.nameField = ref;
		};

		getLastNameField()
		{
			const inputId = 'LAST_NAME';

			return StringInput({
				size: InputSize.L,
				mode: InputMode.NAKED,
				ref: this.handleOnSetLastNameRef,
				testId: `USER_MINI_PROFILE_${inputId}`,
				showTitle: false,
				id: inputId,
				placeholder: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_PLACEHOLDER_LAST_NAME') ?? '',
				value: this.getProfileData().LAST_NAME ?? '',
				onFocus: this.focusLastNameField,
				onBlur: this.setBlurPrimaryField,
				onChange: this.onChangeLastName,
			});
		}

		focusLastNameField = () => {
			this.setState(
				{
					primaryFieldSelected: true,
				},
				() => {
					this.focusedInputIndex = this.inputs.indexOf(this.lastNameField);
				},
			);
		};

		handleOnSetLastNameRef = (ref) => {
			this.lastNameField = ref;
		};

		getPhoneField()
		{
			const inputId = 'PERSONAL_MOBILE';

			return PhoneInput({
				style: {
					marginBottom: Indent.M.toNumber(),
				},
				validation: true,
				id: inputId,
				size: InputSize.L,
				design: InputDesign.LIGHT_GREY,
				ref: this.handleOnSetPhoneRef,
				testId: `USER_MINI_PROFILE_${inputId}`,
				showTitle: false,
				placeholder: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_PLACEHOLDER_PHONE') ?? '',
				value: this.getProfileData().PERSONAL_MOBILE ?? '',
				onChange: this.onChangeMobile,
				onFocus: this.focusPhoneField,
				onValid: this.handleOnValidationPhone,
				errorText: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_WRONG_PHONE') ?? '',
				error: this.state.phoneFieldError ?? false,
			});
		}

		focusPhoneField = () => {
			this.setState(
				{
					phoneFieldError: false,
				},
				() => {
					this.focusedInputIndex = this.inputs.indexOf(this.phoneField);
				},
			);
		};

		handleOnValidationPhone = (value) => {
			return isEmpty(value) || isPhoneNumber(value);
		};

		handleOnSetPhoneRef = (ref) => {
			this.phoneField = ref;
		};

		focusEmailField = () => {
			this.setState(
				{
					emailFieldErrorMessage: null,
					emailFieldError: false,
				},
				() => {
					this.scrollToInput(this.emailField);
					this.focusedInputIndex = this.inputs.indexOf(this.emailField);
				},
			);
		};

		getEmailField()
		{
			const inputId = 'EMAIL';

			return EmailInput({
				style: {
					marginBottom: Indent.M.toNumber(),
				},
				validation: true,
				size: InputSize.L,
				design: InputDesign.LIGHT_GREY,
				ref: this.handleOnSetEmailRef,
				testId: `USER_MINI_PROFILE_${inputId}`,
				showTitle: false,
				id: inputId,
				placeholder: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_PLACEHOLDER_EMAIL') ?? '',
				value: this.getProfileData().EMAIL ?? '',
				errorText: this.state.emailFieldErrorMessage,
				error: this.state.emailFieldError,
				onFocus: this.focusEmailField,
				onValid: this.handleOnValidationEmail,
				domainIconPlace: InputDomainIconPlace.LEFT,
				leftContent: Icon.MAIL,
				onChange: this.onChangeEmail,
			});
		}

		handleOnValidationEmail = (value) => {
			return isEmpty(value) || isValidEmail(value);
		};

		handleOnSetEmailRef = (ref) => {
			this.emailField = ref;
		};

		getDivider()
		{
			return View(
				{
					style: {
						height: 1,
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
					},
				},
			);
		}

		render()
		{
			return View(
				{
					style: {
						paddingHorizontal: Indent.XL2.toNumber(),
					},
				},
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
						},
					},
					this.getAvatarButton(),
					View(
						{
							style: {
								flexGrow: 1,
								borderWidth: 1,
								borderColor: this.state.primaryFieldSelected
									? Color.accentMainPrimary.toHex()
									: Color.bgSeparatorPrimary.toHex(),
								borderRadius: Corner.L.toNumber(),
								marginBottom: Indent.XL4.toNumber(),
								paddingLeft: InputSize.L.getInput()?.paddingHorizontal?.toNumber() ?? Indent.XL.toNumber(),
							},
						},
						this.getNameField(),
						this.getDivider(),
						this.getLastNameField(),
					),
				),
				this.getPhoneField(),
				this.getEmailField(),
			);
		}
	}

	module.exports = {
		UserMiniProfileForm,
	};
});
