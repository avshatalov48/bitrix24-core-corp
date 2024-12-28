/**
 * @module disk/dialogs/create-folder
 */
jn.define('disk/dialogs/create-folder', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { Indent } = require('tokens');

	const { StringInput, InputDesign, InputMode } = require('ui-system/form/inputs/string');

	const { BaseDialog } = require('disk/dialogs/base');

	class CreateFolderDialog extends BaseDialog
	{
		constructor(props)
		{
			super(props);

			this.nameFieldRef = null;

			this.state = {
				name: '',
				pending: false,
			};
		}

		get isLoading()
		{
			return this.state.pending;
		}

		getTestId(suffix)
		{
			return `create-folder-dialog-${suffix}`;
		}

		isButtonDisabled()
		{
			return !this.#isValidName();
		}

		getButtonText()
		{
			return Loc.getMessage('M_DISK_CREATE_FOLDER_DIALOG_CREATE_BUTTON');
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.#focusOnNameField();
		}

		#focusOnNameField = () => {
			void this.nameFieldRef?.focus();
		};

		#bindNameFieldRef = (ref) => {
			this.nameFieldRef = ref;
		};

		#onChangeName = (name) => {
			this.setState({ name });
		};

		#isValidName = () => {
			return this.state.name.length > 0;
		};

		save = () => {
			if (this.state.pending)
			{
				return;
			}

			this.setState({
				pending: true,
			});

			const data = {
				id: this.props.parentFolderId,
				name: this.state.name,
			};

			BX.ajax.runAction('disk.api.folder.addSubFolder', { data })
				.then((response) => {
					this.close();
					this.props.onCreate?.(response?.data?.folder);
				})
				.catch((err) => {
					console.error(err);
					Alert.alert(
						Loc.getMessage('M_DISK_CREATE_FOLDER_DIALOG_ERROR_TITLE'),
						Loc.getMessage('M_DISK_CREATE_FOLDER_DIALOG_ERROR_TEXT'),
						() => this.setState({ pending: false }),
						Loc.getMessage('M_DISK_CREATE_FOLDER_DIALOG_ERROR_OK'),
					);
				});
		};

		/**
		 * @param {Object} data
		 * @param {string} data.parentFolderId
		 * @param {Function} [data.onCreate]
		 * @param {LayoutWidget} parentWidget
		 */
		static async open(data, parentWidget)
		{
			super.open(data, parentWidget);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						justifyContent: 'center',
						paddingHorizontal: Indent.M.toNumber(),
					},
				},
				this.renderNameInput(),
			);
		}

		renderNameInput()
		{
			return StringInput({
				forwardRef: this.#bindNameFieldRef,
				testId: this.getTestId('name-field'),
				value: this.state.name,
				placeholder: Loc.getMessage('M_DISK_CREATE_FOLDER_DIALOG_NAME_PLACEHOLDER'),
				onChange: this.#onChangeName,
				design: InputDesign.GREY,
				mode: InputMode.STROKE,
				focused: true,
			});
		}
	}

	module.exports = { CreateFolderDialog };
});
