/**
 * @module crm/multi-field-drawer
 */
jn.define('crm/multi-field-drawer', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { CombinedV2Field } = require('layout/ui/fields/combined-v2');
	const { EmailField } = require('layout/ui/fields/email');
	const { PhoneField } = require('layout/ui/fields/phone');
	const { SelectField } = require('layout/ui/fields/select');
	const { WarningBlock, BlockType } = require('layout/ui/warning-block');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { Loc } = require('loc');
	const { phoneUtils } = require('native/phonenumber');
	const { NotifyManager } = require('notify-manager');
	const { Type } = require('type');
	const { useCallback } = require('utils/function');
	const { get } = require('utils/object');
	const { getMainDefaultCountryCode } = require('utils/phone');
	const { handleErrors } = require('crm/error');

	const extensionData = jnExtensionData.get('crm:multi-field-drawer');
	const multiFields = BX.prop.getObject(extensionData, 'multiFields', {});

	const MultiFieldType = {
		PHONE: 'PHONE',
		EMAIL: 'EMAIL',
	};

	const MultiFieldPrimaryField = {
		PHONE: PhoneField,
		EMAIL: EmailField,
	};

	class MultiFieldDrawer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = this.getInitialState();

			this.fieldChanged = false;
			this.refMap = new Map();

			this.onChange = this.onChange.bind(this);
			this.onRef = this.onRef.bind(this);
		}

		getInitialState()
		{
			const state = {};

			this.getMultiFieldsToShow().forEach((field) => {
				let fieldState;

				if (field === MultiFieldType.PHONE)
				{
					const countryCode = getMainDefaultCountryCode();
					const phone = `+${phoneUtils.getPhoneCode(countryCode)}`;

					fieldState = {
						value: { phone, countryCode },
						type: null,
					};
				}
				else
				{
					fieldState = {
						value: '',
						type: null,
					};
				}

				state[field] = fieldState;
			});

			return state;
		}

		componentDidMount()
		{
			this.saveButton = new WidgetHeaderButton({
				widget: this.layoutWidget,
				text: Loc.getMessage('MCRM_MULTI_FIELD_DRAWER_SAVE'),
				loadingText: Loc.getMessage('MCRM_MULTI_FIELD_DRAWER_SAVING'),
				onClick: this.saveMultiFields.bind(this),
				disabled: () => {
					if (this.fieldChanged)
					{
						return !this.isSaveAllowed();
					}

					return true;
				},
			});

			this.focusFirstField();
		}

		focusFirstField()
		{
			const firstFieldType = this.getMultiFieldsToShow()[0];
			if (firstFieldType && this.refMap.has(firstFieldType))
			{
				const firstField = this.refMap.get(firstFieldType);

				firstField.focus();
			}
		}

		get entityTypeId()
		{
			return this.props.entityTypeId;
		}

		get entityId()
		{
			return this.props.entityId;
		}

		get fields()
		{
			return BX.prop.getArray(this.props, 'fields', []);
		}

		getMultiFieldsToShow()
		{
			return this.fields.filter((field) => {
				return MultiFieldType.hasOwnProperty(field) && multiFields.hasOwnProperty(field);
			});
		}

		show(parentWidget = PageManager)
		{
			const title = this.getTitle();
			const bottomSheet = new BottomSheet({ title, component: this });

			bottomSheet.setParentWidget(parentWidget);

			const mediumPercent = Math.min(50 + this.getMultiFieldsToShow().length * 10, 80);
			bottomSheet.setMediumPositionPercent(mediumPercent);

			return bottomSheet.open().then((widget) => {
				this.layoutWidget = widget;
			});
		}

		getTitle()
		{
			const { title } = this.props;

			if (Type.isStringFilled(title))
			{
				return title;
			}

			const fieldsToShow = this.getMultiFieldsToShow();

			if (fieldsToShow.length === 1)
			{
				const phraseCode = `MCRM_MULTI_FIELD_DRAWER_${fieldsToShow[0]}_TITLE`;

				if (Loc.hasMessage(phraseCode))
				{
					return Loc.getMessage(phraseCode);
				}
			}

			return Loc.getMessage('MCRM_MULTI_FIELD_DRAWER_DEFAULT_TITLE');
		}

		refreshSaveButton()
		{
			if (this.saveButton)
			{
				this.saveButton.refresh();
			}
		}

		isSaveAllowed()
		{
			return this.getMultiFieldsToShow().every((field) => {
				const fieldRef = this.refMap.get(field);
				if (fieldRef)
				{
					return fieldRef.validate();
				}

				return false;
			});
		}

		saveMultiFields()
		{
			if (!this.isSaveAllowed())
			{
				return Promise.reject();
			}

			NotifyManager.showLoadingIndicator();

			const action = 'crmmobile.MultiField.save';

			const entity = {
				entityTypeId: this.entityTypeId,
				entityId: this.entityId,
			};
			const json = {
				...entity,
				values: this.state,
			};

			return BX.ajax.runAction(action, { json })
				.then((response) => {
					NotifyManager.hideLoadingIndicator(true);

					const data = {
						...entity,
						values: response.data,
					};

					BX.postComponentEvent('MultiFieldDrawer::onSave', [data]);

					this.layoutWidget.close(() => {
						if (this.props.onSuccess)
						{
							this.props.onSuccess(data, multiFields);
						}
					});
				})
				.catch((errors) => {
					NotifyManager.hideLoadingIndicator(false);

					return handleErrors(errors);
				});
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
					},
				},
				this.hasWarningBlock() && this.renderWarningBlock(),
				...this.renderFields(),
			);
		}

		renderWarningBlock()
		{
			const params = {
				description: this.getWarningBlockDescription(),
				type: BlockType.info,
			};

			if (this.isShowWarningBlockTitle())
			{
				params.title = Loc.getMessage('MCRM_PHONE_DRAWER_WARNING_TITLE');
			}

			return new WarningBlock(params);
		}

		hasWarningBlock()
		{
			return Boolean(this.props.warningBlock);
		}

		getWarningBlockDescription()
		{
			return get(this.props, 'warningBlock.description', Loc.getMessage('MCRM_MULTI_FIELD_DRAWER_WARNING_DESCRIPTION'));
		}

		isShowWarningBlockTitle()
		{
			return get(this.props, 'warningBlock.showTitle', true);
		}

		renderFields()
		{
			return this.getMultiFieldsToShow().map((fieldType) => {
				const fieldView = this.renderFieldByType(fieldType);

				if (fieldView)
				{
					return View(
						{
							style: {
								marginTop: 11,
								paddingLeft: 17,
								paddingRight: 40,
								paddingVertical: 23,
								backgroundColor: '#f8fafb',
								borderRadius: 12,
							},
						},
						fieldView,
					);
				}

				return null;
			});
		}

		renderFieldByType(multiFieldType)
		{
			if (!MultiFieldType[multiFieldType] || !MultiFieldPrimaryField[multiFieldType])
			{
				return null;
			}

			const { [multiFieldType]: value } = this.state;
			const primaryField = MultiFieldPrimaryField[multiFieldType];

			return CombinedV2Field({
				value,
				onChange: useCallback(
					(changedValue) => this.onChange(multiFieldType, changedValue),
					[multiFieldType],
				),
				ref: useCallback((ref) => this.onRef(multiFieldType, ref), [multiFieldType]),
				config: {
					primaryField: {
						id: 'value',
						renderField: primaryField,
						required: true,
						showTitle: false,
						showBorder: true,
					},
					secondaryField: {
						id: 'type',
						renderField: SelectField,
						showTitle: false,
						required: true,
						showRequired: false,
						config: {
							items: this.getTypeItems(multiFieldType),
						},
					},
				},
			});
		}

		getTypeItems(multiFieldType)
		{
			return Object.entries(multiFields[multiFieldType]).map(([value, name]) => ({ value, name }));
		}

		onChange(multiFieldType, value)
		{
			this.fieldChanged = true;
			this.setState({ [multiFieldType]: value }, () => this.refreshSaveButton());
		}

		onRef(multiFieldType, ref)
		{
			this.refMap.set(multiFieldType, ref);
		}
	}

	module.exports = {
		MultiFieldDrawer,
		MultiFieldType,
	};
});
