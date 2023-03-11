(() => {
	const { EventEmitter } = jn.require('event-emitter');
	const { isEqual } = jn.require('utils/object');
	const { PureComponent } = jn.require('layout/pure-component');

	/**
	 * @class EntityEditorBaseControl
	 */
	class EntityEditorBaseControl extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;

			this.initialize(props.id, props.uid, props.type, props.settings);
			this.isChanged = false;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			nextState = Array.isArray(nextState) ? nextState[0] : nextState;

			return !isEqual(this.props, nextProps) || !isEqual(this.state, nextState);
		}

		componentWillReceiveProps(props)
		{
			this.initialize(props.id, props.uid, props.type, props.settings);

			if (this.editor && this.editor.settings.loadFromModel)
			{
				this.initializeStateFromModel();
			}
		}

		initialize(id, uid, type, settings)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : Random.getString();

			this.uid = CommonUtils.isNotEmptyString(uid) ? uid : Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.settings = settings ? settings : {};

			/** @type {EntityEditor} */
			this.editor = BX.prop.get(this.settings, 'editor', null);
			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, 'model', null);
			/** @type {EntitySchemeElement} */
			this.schemeElement = BX.prop.get(this.settings, 'schemeElement', null);

			this.readOnly = BX.prop.getBoolean(this.settings, 'readOnly', true);
			this.type = type || '';

			let mode = BX.prop.getInteger(this.settings, 'mode', BX.UI.EntityEditorMode.view);
			if (mode === BX.UI.EntityEditorMode.edit && !this.isEditable())
			{
				mode = BX.UI.EntityEditorMode.view;
			}

			this.state.mode = mode;

			/** @type {EntityEditorBaseControl} */
			this.parent = BX.prop.get(this.settings, 'parent', null);
			this.isChanged = BX.prop.getBoolean(this.settings, 'isChanged', false);
		}

		initializeStateFromModel()
		{

		}

		getUid()
		{
			return this.uid;
		}

		isInEditMode()
		{
			if (this.parent && this.parent.isInEditMode())
			{
				return true;
			}

			return this.getMode() === BX.UI.EntityEditorMode.edit;
		}

		renderFromModel(ref)
		{
			const elements = this.schemeElement ? this.schemeElement.getElements() : [];

			return (
				elements
					.map((element, index) => {
						return this.renderElementFromModel(element, (controlRef) => ref(controlRef, index));
					})
					.filter((element) => element)
			);
		}

		renderElementFromModel(schemeElement, ref)
		{
			return this.editor.renderControl(
				ref,
				schemeElement.getType(),
				schemeElement.getName(),
				this.uid,
				{
					parent: this,
					schemeElement,
					model: this.model,
					readOnly: this.readOnly,
					isChanged: this.isChanged,
					mode: this.getMode(),
				},
			);
		}

		getId()
		{
			return this.id;
		}

		/**
		 * Method 'getControls' must be implemented in ancestors.
		 *
		 * @returns {EntityEditorBaseControl[]}
		 */
		getControls()
		{
			return [];
		}

		getValuesToSave()
		{
			let fieldValues = {};

			this.getControls().forEach((field) => {
				fieldValues = {
					...fieldValues,
					...field.getValuesToSave(),
				};
			});

			return fieldValues;
		}

		isEditable()
		{
			if (this.readOnly)
			{
				return false;
			}

			return this.schemeElement && this.schemeElement.isEditable();
		}

		isRequired()
		{
			return this.schemeElement && this.schemeElement.isRequired();
		}

		isShowRequired()
		{
			return this.schemeElement && this.schemeElement.isShowRequired();
		}

		validate(result)
		{
			const validator = EntityAsyncValidator.create();

			this.getControls().forEach((control) => {
				validator.addResult(control.validate(result));
			});

			return validator.validate();
		}

		isNewEntity()
		{
			return this.editor && this.editor.isNew;
		}

		getCreationPlaceholder()
		{
			return this.schemeElement && this.schemeElement.getCreationPlaceholder();
		}

		/**
		 * @param {number} mode
		 * @param {object} options
		 * @param {boolean} notify
		 * @return {Promise}
		 */
		setMode(mode, options = {}, notify = false)
		{
			if (!this.canChangeMode(mode))
			{
				return Promise.reject();
			}

			if (this.getMode() === mode)
			{
				return Promise.resolve();
			}

			return this.doSetMode(mode, options, notify);
		}

		/**
		 * @param {number} mode
		 * @param {object} options
		 * @param {boolean} notify
		 * @return {Promise}
		 */
		doSetMode(mode, options, notify)
		{
			return new Promise(resolve => {
				this.setState({ mode }, () => {
					this.processControlModeChange(notify);
					resolve();
				});
			});
		}

		/**
		 *
		 * @param {boolean} notify
		 */
		processControlModeChange(notify = false)
		{
			if (notify)
			{
				this.editor.processControlModeChange(this);
			}
		}

		canChangeMode(mode)
		{
			if (mode === BX.UI.EntityEditorMode.edit)
			{
				return this.isEditable();
			}

			return true;
		}

		getMode()
		{
			return this.state.mode;
		}

		isVisible()
		{
			return BX.UI.EntityEditorVisibilityPolicy.checkVisibility(this);
		}

		getVisibilityPolicy()
		{
			if (this.editor && !this.editor.isVisibilityPolicyEnabled())
			{
				return BX.UI.EntityEditorVisibilityPolicy.always;
			}

			return this.schemeElement && this.schemeElement.getVisibilityPolicy();
		}

		getName()
		{
			return this.schemeElement ? this.schemeElement.getName() : '';
		}

		isModeToggleEnabled()
		{
			return this.editor.isModeToggleEnabled();
		}

		getDataBooleanParam(name, defaultValue)
		{
			return (this.schemeElement ? this.schemeElement.getDataBooleanParam(name, defaultValue) : defaultValue);
		}

		isActive()
		{
			if (this.getMode() === BX.UI.EntityEditorMode.edit)
			{
				return true;
			}

			return (
				this
					.getControls()
					.some((control) => control && control.isActive())
			);
		}

		switchToViewMode()
		{
			if (this.getMode() === BX.UI.EntityEditorMode.view)
			{
				return this.switchControlsToViewMode();
			}

			return this.setMode(BX.UI.EntityEditorMode.view);
		}

		switchControlsToViewMode(controlToSkip = null)
		{
			return Promise.all(
				this.getControls().map(control => {
					if (controlToSkip && controlToSkip === control)
					{
						return Promise.resolve();
					}

					return control.switchToViewMode();
				}),
			);
		}

		blurInlineFields(fieldToSkip = null)
		{
			if (this.getMode() === BX.UI.EntityEditorMode.edit)
			{
				return Promise.resolve();
			}

			return Promise.all(
				this.getControls().map(control => control.blurInlineFields(fieldToSkip)),
			);
		}

		markAsChanged()
		{
			if (!this.isChanged)
			{
				this.isChanged = true;
			}

			if (this.parent)
			{
				this.parent.markAsChanged();
			}
			else
			{
				this.editor.markAsChanged();
			}
		}
	}

	jnexport(EntityEditorBaseControl);
})();