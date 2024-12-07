/**
 * @module intranet/user-mini-profile-form
 */
jn.define('intranet/user-mini-profile-form', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { FileConverter } = require('files/converter');
	const { getFile } = require('files/entry');
	const { Loc } = require('loc');
	const { isEmpty } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { StringInput, InputSize, InputMode, InputDesign } = require('ui-system/form/inputs/string');
	const { EmailInput, InputDomainIconPlace } = require('ui-system/form/inputs/email');
	const { PhoneInput } = require('ui-system/form/inputs/phone');
	const { isPhoneNumber } = require('utils/phone');
	const { isValidEmail } = require('utils/email');

	class UserMiniProfileForm extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.nameField = null;
			this.lastNameField = null;
			this.phoneField = null;
			this.emailField = null;
			this.profileData = {};
			this.scrollToInput = props.scrollToInput;

			this.state = {
				profileData: props.profileData,
			};
		}

		componentDidMount()
		{
			this.onContinue();
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();
			BX.postComponentEvent('userMiniProfileClosed', null);
		}

		getProfileData = () => {
			const { profileData } = this.state;

			return profileData;
		};

		getImageButton()
		{
			return View(
				{
					style: {
						marginRight: Indent.XL3.toNumber(),
						width: 84,
						height: 84,
						borderRadius: 42,
						backgroundColorGradient: {
							start: '#86ffc7',
							middle: '#279def',
							end: '#0175ff',
							angle: 45,
						},
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
					},
					onClick: () => this.showImagePicker(),
				},
				this.props.photo || this.state.newPhoto
					? Image({
						resizeMode: 'cover',
						uri: this.state.newPhoto ?? this.props.photo,
						style: {
							width: 77,
							height: 77,
							backgroundColor: Color.accentSoftBlue3.toHex(),
							borderColor: Color.bgSecondary.toHex(),
							borderWidth: 3,
							borderRadius: 39,
						},
					})
					: View(
						{
							style: {
								width: 77,
								height: 77,
								borderColor: Color.bgSecondary.toHex(),
								borderWidth: 3,
								borderRadius: 39,
								backgroundColor: Color.accentSoftBlue3.toHex(),
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
							},
						},
						IconView({
							icon: Icon.CAMERA,
							size: 47,
							color: Color.accentMainPrimary,
						}),
					),
			);
		}

		showImagePicker()
		{
			const items = [
				{
					id: 'mediateka',
					name: BX.message('MOBILE_LAYOUT_UI_FIELDS_IMAGE_SELECT_MEDIATEKA'),
				},
				{
					id: 'camera',
					name: BX.message('MOBILE_LAYOUT_UI_FIELDS_IMAGE_SELECT_CAMERA'),
				},
			];

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 1,
						},
						maxAttachedFilesCount: 1,
						previewMaxWidth: 640,
						previewMaxHeight: 640,
						attachButton: { items },
					},
				},
				(data) => this.onImageSelectFielded(data),
			);
		}

		onImageSelectFielded = (data) => {
			const image = data[0];

			if (image)
			{
				this.setState(
					{
						newPhoto: image.previewUrl,
					},
					() => {
						void this.convertImage(image);
					},
				);
			}
		};

		async convertImage(image)
		{
			try
			{
				const converter = new FileConverter();
				const path = await converter.resize('avatarResize', {
					url: image.previewUrl,
					width: 1000,
					height: 1000,
				});
				const file = await getFile(path);
				file.readMode = BX.FileConst.READ_MODE.DATA_URL;
				const fileData = await file.readNext();

				if (fileData.content)
				{
					const content = fileData.content;
					const personalPhoto = [
						'avatar.png', content.slice(
							content.indexOf('base64,') + 7,
							content.length,
						),
					];
					this.onChange('PERSONAL_PHOTO', personalPhoto);
				}
			}
			catch (error)
			{
				console.error(error);
			}
		}

		validate = (profileData) => {
			const { NAME, LAST_NAME, PERSONAL_MOBILE, EMAIL } = profileData;

			if (isEmpty(NAME))
			{
				this.nameField?.focus();

				return false;
			}

			if (isEmpty(LAST_NAME))
			{
				this.lastNameField?.focus();

				return false;
			}

			if (isEmpty(PERSONAL_MOBILE) || !isPhoneNumber(PERSONAL_MOBILE))
			{
				this.phoneField?.focus();

				return false;
			}

			if (isEmpty(EMAIL) || !isValidEmail(EMAIL))
			{
				this.emailField?.focus();

				return false;
			}

			return true;
		};

		onContinue = () => {
			if (this.validate(this.getProfileData()))
			{
				Keyboard.dismiss();
			}
		};

		submit()
		{
			const fields = this.getProfileData();

			if (!fields.ID)
			{
				return;
			}

			if (this.validate(fields))
			{
				this.updateUser(fields);
			}
		}

		updateUser(fields)
		{
			BX.rest.callMethod('user.update', fields)
				.then(() => {
					layout.close();
					BX.postComponentEvent('userMiniProfileClosed', null);
				})
				.catch((response) => {
					console.error(response);
					this.setState({
						emailFieldError: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_EMAIL_EXIST'),
					});
				});
		}

		onChange = (id, value) => {
			this.setState({
				profileData: {
					...this.getProfileData(),
					[id]: value,
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

		setFocusPrimaryField = () => {
			this.setState({
				primaryFieldSelected: true,
			});
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
				readOnly: false,
				onFocus: this.setFocusPrimaryField,
				onBlur: this.setBlurPrimaryField,
				onChange: this.onChangeName,
			});
		}

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
				readOnly: false,
				onFocus: this.setFocusPrimaryField,
				onBlur: this.setBlurPrimaryField,
				onChange: this.onChangeLastName,
			});
		}

		handleOnSetLastNameRef = (ref) => {
			this.lastNameField = ref;
		};

		getPhoneField()
		{
			const inputId = 'PERSONAL_MOBILE';

			return PhoneInput({
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
			});
		}

		handleOnSetPhoneRef = (ref) => {
			this.phoneField = ref;
		};

		focusEmailField = () => {
			this.setState({
				emailFieldError: null,
			});
			this.scrollToInput(this.emailField);
		};

		getEmailField()
		{
			const inputId = 'EMAIL';

			return EmailInput({
				validation: true,
				size: InputSize.L,
				design: InputDesign.LIGHT_GREY,
				ref: this.handleOnSetEmailRef,
				testId: `USER_MINI_PROFILE_${inputId}`,
				showTitle: false,
				id: inputId,
				placeholder: Loc.getMessage('INTRANETMOBILE_USER_MINI_PROFILE_PLACEHOLDER_EMAIL') ?? '',
				value: this.getProfileData().EMAIL ?? '',
				errorText: this.state.emailFieldError,
				error: !isEmpty(this.state.emailFieldError),
				onFocus: this.focusEmailField,
				domainIconPlace: InputDomainIconPlace.LEFT,
				leftContent: Icon.MAIL,
				onChange: this.onChangeEmail,
				onError: this.handleOnError,
			});
		}

		handleOnError = (error, id) => {};

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
					this.getImageButton(),
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
				View(
					{
						style: {
							marginBottom: Indent.M.toNumber(),
						},
					},
					this.getPhoneField(),
				),
				View(
					{
						style: {
							marginBottom: Indent.M.toNumber(),
						},
					},
					this.getEmailField(),
				),
			);
		}
	}

	module.exports = {
		UserMiniProfileForm,
	};
});
