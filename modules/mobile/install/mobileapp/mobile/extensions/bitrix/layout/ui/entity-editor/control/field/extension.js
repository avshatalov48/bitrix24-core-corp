/**
 * @module layout/ui/entity-editor/control/field
 */
jn.define('layout/ui/entity-editor/control/field', (require, exports, module) => {

	const {
		FieldFactory,
		BooleanType,
		StringType,
		TextAreaType,
		NumberType,
		BarcodeType,
		UrlType,
		AddressType,
		MoneyType,
	} = require('layout/ui/fields');

	const { Loc } = require('loc');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { isEqual, get } = require('utils/object');
	const { stringify } = require('utils/string');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { DuplicateTooltip } = require('layout/ui/entity-editor/tooltip/duplicate');
	const { useCallback } = require('utils/function');
	const { EntityEditorBaseControl } = require('layout/ui/entity-editor/control/base');
	const { EntityEditorControlOptions } = require('layout/ui/entity-editor/editor-enum/control-options');
	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');
	const { EntityEditorModeOptions } = require('layout/ui/entity-editor/editor-enum/mode-options');

	const EDIT_MODE_FIELD_BACKGROUND_COLOR = '#f8fafb';

	const INLINE_FIELDS = new Set([
		StringType,
		TextAreaType,
		NumberType,
		BarcodeType,
		UrlType,
		AddressType,
		MoneyType,
	]);

	/**
	 * @class EntityEditorField
	 */
	class EntityEditorField extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);
			this.state.value = this.getValueFromModel();

			/** @type {BaseField} */
			this.fieldRef = null;
			this.fieldViewRef = null;
			this.bindRef = this.bindRef.bind(this);
			this.onChangeState = this.onChangeState.bind(this);
			this.onFocusIn = this.onFocusIn.bind(this);
			this.onFocusOut = this.onFocusOut.bind(this);
			this.onFieldClick = this.handleFieldClick.bind(this);
			this.renderAdditionalContent = this.renderAdditionalContent.bind(this);
		}

		get showBorder()
		{
			return BX.prop.getBoolean(this.props.settings, 'showBorder', true);
		}

		initializeStateFromModel()
		{
			if (!this.isChanged)
			{
				this.state.value = this.getValueFromModel();
			}
		}

		isInline()
		{
			if (!this.isEditable())
			{
				return false;
			}

			return INLINE_FIELDS.has(FieldFactory.checkForAlias(this.type));
		}

		isEditable()
		{
			if (this.isEditRestricted())
			{
				return false;
			}

			return super.isEditable();
		}

		prepareFieldProps()
		{
			const id = this.getId();

			const restrictedEdit = this.isEditRestricted();
			const showEditIcon = restrictedEdit || this.isSingleEditEnabled();
			const tooltip = this.getTooltip();

			return {
				ref: this.bindRef,
				id,
				uid: this.getUid(),
				type: this.type,
				context: this.getContext(),
				testId: `${this.model.id}_${id}`,
				title: this.getTitle(),
				multiple: this.isMultiple(),
				placeholder: this.isNewEntity() && this.getCreationPlaceholder(),
				readOnly: this.isReadOnly(),
				editable: this.isEditable(),
				onChange: this.onChangeState,
				onFocusIn: this.onFocusIn,
				onFocusOut: this.onFocusOut,
				config: this.prepareConfig(),
				required: this.isRequired(),
				showRequired: this.isShowRequired(),
				showEditIcon,
				restrictedEdit,
				showBorder: this.showBorder,
				hasSolidBorderContainer: this.hasSolidBorderContainer(),
				hasHiddenEmptyView: true,
				tooltip: isFunction(tooltip) ? useCallback(tooltip) : null,
				renderAdditionalContent: this.renderAdditionalContent,
			};
		}

		getTooltip()
		{
			if (this.hasDuplicate())
			{
				return DuplicateTooltip.create({
					uid: this.getUid(),
					fieldType: this.getId(),
					settings: this.settings,
					entityId: this.editor.getEntityId(),
					entityTypeName: this.editor.getEntityTypeName(),
				});
			}

			return null;
		}

		hasDuplicate()
		{
			return DuplicateTooltip.isEnabledDuplicateControl(this.schemeElement.getData(), this.editor.getEntityTypeName());
		}

		bindRef(ref)
		{
			this.fieldRef = ref;
		}

		isReadOnly()
		{
			const readonly = !this.isEditable();
			const showReadOnlyOnInitialize = this.isInitialReadOnly() && this.getMode() === EntityEditorMode.view;
			return readonly || showReadOnlyOnInitialize;
		}

		isVisible()
		{
			if (this.checkOptionFlag(EntityEditorControlOptions.showAlways))
			{
				return true;
			}

			if (!super.isVisible())
			{
				return false;
			}

			return this.isNeedToDisplay();
		}

		get marginBottom()
		{
			return 6;
		}

		renderField()
		{
			return View(
				{
					style: {
						marginBottom: this.isVisible() ? this.marginBottom : 0,
					},
				},
				this.getFieldInstance(this.getValue()),
			);
		}

		renderAdditionalContent()
		{
			return null;
		}

		render()
		{
			return View(
				{
					style: {
						display: this.isVisible() ? 'flex' : 'none',
					},
					ref: (ref) => this.fieldViewRef = ref,
					onClick: this.onFieldClick,
				},
				this.renderField(),
			);
		}

		prepareConfig()
		{
			return {
				...this.schemeElement.getData(),
				entityId: this.editor.getEntityId(),
				isNewEntity: this.isNewEntity(),
				options: this.getOptions(),
				type: this.type,
				enableKeyboardHide: true,
				parentWidget: this.layout,
				styles: {
					externalWrapperBackgroundColor: this.getFieldBackgroundColor(),
					externalWrapperBorderColor: this.getFieldBorderColor(),
				},
				deepMergeStyles: {
					externalWrapper: this.showBorder ? {} : {
						marginHorizontal: 16,
					},
				},
				ellipsize: true,
			};
		}

		getFieldBackgroundColor()
		{
			if (this.hasSolidBorderContainer())
			{
				return this.parent.isInEditMode() ? EDIT_MODE_FIELD_BACKGROUND_COLOR : '#fcfcfd';
			}

			return null;
		}

		getFieldBorderColor()
		{
			if (this.hasSolidBorderContainer())
			{
				return this.getSolidBorderContainerColor();
			}

			return this.parent.isInEditMode() ? '#e4e6e7' : '#edeef0';
		}

		getSolidBorderContainerColor()
		{
			return '#dfe0e3';
		}

		onChangeState(value)
		{
			return this.setValue(value);
		}

		prepareBeforeSaving(value)
		{
			return value;
		}

		onFocusIn()
		{
			this.editor
				.blurInlineFields(this)
				.then(() => {
					this.customEventEmitter.emit('UI.EntityEditor.Field::onFocusIn', [this.getName()]);

					if (this.fieldRef && this.fieldRef.hasKeyboard())
					{
						setTimeout(() => this.editor.scrollToFocusedField(this.fieldViewRef), 300);
					}
				})
			;
		}

		onFocusOut()
		{
			this.customEventEmitter.emit('UI.EntityEditor.Field::onFocusOut', [this.getName()]);
		}

		handleFieldClick()
		{
			const promise = (
				FocusManager
					.blurFocusedFieldIfHas(this.fieldRef)
					.then(() => this.editor.blurInlineFields(this))
			);

			if (this.isEditRestricted() && this.getFeatureSlider())
			{
				promise.then(() => this.showFeatureSlider());
			}
			else
			{
				promise.then(() => this.switchToSingleEditMode());
			}
		}

		isMultiple()
		{
			return this.schemeElement && this.schemeElement.isMultiple();
		}

		getTitle()
		{
			if (!this.schemeElement)
			{
				return '';
			}

			let title = this.schemeElement.getTitle();
			if (title === '')
			{
				title = this.schemeElement.getName();
			}

			return title;
		}

		getValueFromModel(defaultValue = '')
		{
			if (this.model)
			{
				return this.model.getField(this.getName(), defaultValue);
			}

			return defaultValue;
		}

		getValue()
		{
			return this.state.value;
		}

		getValuesToSave()
		{
			if (!this.fieldRef || !this.isEditable())
			{
				return {};
			}

			const promise = (
				this.fieldRef
					.getValueWhileReady()
					.then((value) => ({ [this.getName()]: this.prepareBeforeSaving(value) }))
			);

			return {
				[this.getName()]: promise,
			};
		}

		validate()
		{
			if (!this.fieldRef || !this.isEditable())
			{
				return true;
			}

			const isValid = this.fieldRef.validate();
			if (!isValid)
			{
				this.editor.scrollToInvalidField(this.fieldViewRef);
			}

			return isValid;
		}

		isRequired()
		{
			return this.schemeElement && this.schemeElement.isRequired();
		}

		isShowRequired()
		{
			return this.schemeElement && this.schemeElement.isShowRequired();
		}

		setValue(value)
		{
			if (!isEqual(this.state.value, value))
			{
				return new Promise((resolve) => {
					this.setState({ value }, () => {
						this.markAsChanged();
						this.customEventEmitter.emit('UI.EntityEditor.Field::onChangeState', [{
							fieldName: this.getName(),
							fieldValue: value,
						}]);

						resolve();
					});
				});
			}

			return Promise.resolve();
		}

		isNeedToDisplay()
		{
			if (
				this.isInEditMode()
				|| this.checkOptionFlag(EntityEditorControlOptions.showAlways)
				|| this.schemeElement.isShownAlways
			)
			{
				return true;
			}

			return this.hasContentToDisplay();
		}

		getOptions()
		{
			return this.schemeElement.options;
		}

		checkOptionFlag(flag)
		{
			return EntityEditorControlOptions.check(this.getOptionFlags(), flag);
		}

		getOptionFlags()
		{
			return (
				this.schemeElement
					? this.schemeElement.getOptionsFlags()
					: EntityEditorControlOptions.none
			);
		}

		getContext()
		{
			return get(this.editor, ['payload', 'context'], {});
		}

		hasContentToDisplay()
		{
			return this.hasValue() || this.hasValueFromModel();
		}

		hasValue()
		{
			const value = this.getValue();

			return this.hasFilledValue(value);
		}

		hasValueFromModel()
		{
			const value = this.getValueFromModel();

			return this.hasFilledValue(value);
		}

		hasFilledValue(value)
		{
			if (this.type === BooleanType)
			{
				return true;
			}

			if (this.type === MoneyType && !this.isMultiple())
			{
				const { amount } = value;

				return stringify(amount) !== '';
			}

			const fieldInstance = this.getFieldInstance(value);

			return !fieldInstance.isEmpty();
		}

		getFieldInstance(value)
		{
			return FieldFactory.create(this.type, {
				...this.prepareFieldProps(),
				value,
			});
		}

		showReadOnlyFieldHint()
		{
			Notify.showUniqueMessage(
				Loc.getMessage('M_ENTITY_EDITOR_FIELD_CANT_EDIT_HINT_TEXT', { '#NAME#': this.getTitle() }),
				Loc.getMessage('M_ENTITY_EDITOR_FIELD_CANT_EDIT_HINT_TITLE'),
				{ time: 3 },
			);
		}

		switchToSingleEditMode()
		{
			if (!this.isSingleEditEnabled())
			{
				if (!this.editor.readOnly)
				{
					this.showReadOnlyFieldHint();
				}

				return Promise.reject();
			}

			return this.editor.switchControlMode(
				this,
				EntityEditorMode.edit,
				EntityEditorModeOptions.individual,
			);
		}

		blurInlineFields(fieldToSkip = null)
		{
			if (fieldToSkip && fieldToSkip === this)
			{
				return Promise.resolve();
			}

			return this.switchToViewMode();
		}

		doSetMode(mode, options, notify)
		{
			if (this.getMode() === mode)
			{
				return Promise.resolve();
			}

			return new Promise(resolve => {
				this.setState({ mode }, () => {
					let promise = Promise.resolve();

					if (
						mode === EntityEditorMode.edit
						&& options === EntityEditorModeOptions.individual
					)
					{
						promise = promise.then(() => this.focusField());
					}

					promise.then(() => {
						this.processControlModeChange(notify);
						resolve();
					});
				});
			});
		}

		focusField()
		{
			if (this.fieldRef)
			{
				return this.fieldRef.focus();
			}

			return Promise.reject();
		}

		isSingleEditEnabled()
		{
			return (
				(this.isModeToggleEnabled() || this.parent.isInEditMode())
				&& this.isEditable()
				&& this.getDataBooleanParam('enableEditInView', true)
				&& this.getDataBooleanParam('enableSingleEdit', true)
			);
		}

		hasSolidBorderContainer()
		{
			return this.getDataBooleanParam('hasSolidBorder', false);
		}

		isInitialReadOnly()
		{
			return this.getDataBooleanParam('initialReadOnly', false);
		}

		isEditRestricted()
		{
			return BX.prop.getBoolean(this.getRestrictionParams(), 'isRestricted', false);
		}

		getFeatureSlider()
		{
			return BX.prop.getString(this.getRestrictionParams(), 'mobileHelperId', null);
		}

		getRestrictionParams()
		{
			return this.schemeElement ? this.schemeElement.getDataParam('restriction', {}) : {};
		}

		showFeatureSlider()
		{
			void PlanRestriction.open(
				{
					title: this.getTitle(),
				},
				this.layout,
			);
		}
	}

	const styles = {
		defaultFieldWrapper: (visible, parentMode, showBorder) => ({
			externalWrapper: {
				paddingHorizontal: 16,
				display: visible ? 'flex' : 'none',
			},
			wrapper: {
				borderBottomWidth: showBorder ? 0.5 : 0,
				borderBottomColor: parentMode === EntityEditorMode.edit ? '#e4e6e7' : '#edeef0',
			},
		}),
	};

	EntityEditorField.Styles = styles;

	module.exports = { EntityEditorField };
});