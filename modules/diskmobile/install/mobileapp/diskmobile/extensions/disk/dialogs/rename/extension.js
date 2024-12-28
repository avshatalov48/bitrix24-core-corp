/**
 * @module disk/dialogs/rename
 */
jn.define('disk/dialogs/rename', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent } = require('tokens');

	const { StringInput, InputDesign, InputMode } = require('ui-system/form/inputs/string');
	const { getNameWithoutExtension, getExtension } = require('utils/file');

	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const { rename } = require('disk/statemanager/redux/slices/files/thunk');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const { BaseDialog } = require('disk/dialogs/base');

	class RenameDialog extends BaseDialog
	{
		constructor(props)
		{
			super(props);

			this.nameFieldRef = null;

			this.objectId = props.objectId ?? '';
			this.object = selectById(store.getState(), Number(this.objectId)) ?? {};
			this.startName = (this.object.isFolder ? this.object.name : getNameWithoutExtension(this.object.name)) ?? '';

			this.state = {
				name: this.startName,
			};
		}

		getTestId(suffix) {
			return `rename-dialog-${suffix}`;
		}

		isButtonDisabled()
		{
			return !this.#isValidName();
		}

		getButtonText()
		{
			return Loc.getMessage('M_DISK_RENAME_SAVE_BUTTON');
		}

		componentDidMount() {
			super.componentDidMount();

			this.#focusOnNameField();
			this.#selectNameInField();
		}

		#focusOnNameField = () => {
			void this.nameFieldRef?.focus();
		};

		#selectNameInField = () => {
			this.nameFieldRef?.selectAll();
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
			const { isFolder } = this.object;

			if (!this.#isValidName)
			{
				return;
			}

			let newName = this.state.name;

			if (!isFolder)
			{
				newName += `.${getExtension(this.object.name)}`;
			}

			if (this.state.name !== this.startName)
			{
				dispatch(rename({
					objectId: this.objectId,
					newName,
				}));
			}

			this.close();
		};

		/**
		 * @public
		 * @param {Object} data
		 * @param {number} [data.objectId]
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
				value: this.state.name,
				onChange: this.#onChangeName,
				placeholder: this.object.isFolder
					? Loc.getMessage('M_DISK_RENAME_FOLDER_PLACEHOLDER')
					: Loc.getMessage('M_DISK_RENAME_FILE_PLACEHOLDER'),
				design: InputDesign.GREY,
				mode: InputMode.STROKE,
				testId: this.getTestId('name-field'),
				forwardRef: this.#bindNameFieldRef,
			});
		}
	}

	module.exports = { RenameDialog };
});
