/**
 * @module layout/ui/entity-editor
 */
jn.define('layout/ui/entity-editor', (require, exports, module) => {

	const { EventEmitter } = require('event-emitter');
	const { useCallback } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { FadeView } = require('animation/components/fade-view');
	const { EntityEditorControlFactory } = require('layout/ui/entity-editor/control');
	const { EntityEditorColumn } = require('layout/ui/entity-editor/control/column');
	const { EntityEditorSection } = require('layout/ui/entity-editor/control/section');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { EntityEditorBaseControl } = require('layout/ui/entity-editor/control/base');
	const { EntityAsyncValidator } = require('layout/ui/entity-editor/validator/async');
	const { EntityEditorControllerFactory } = require('layout/ui/entity-editor/controller');
	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');

	/**
	 * @class EntityEditor
	 */
	class EntityEditor extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;

			/** @type {EntityEditorColumn[]} */
			this.controls = [];

			/** @type {EntityEditorBaseController[]} */
			this.controllers = [];

			this.isScrollToViewEnabled = Application.getApiVersion() >= 44;
			this.alreadyScrolledToInvalidField = false;

			this.scrollViewRef = null;
			this.scrollY = 0;

			this.init(props);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('UI.EntityEditor::switchToViewMode', this.switchToViewMode.bind(this));
		}

		switchToViewMode()
		{
			return Promise.all(this.getControls().map(control => control.switchToViewMode()));
		}

		componentWillReceiveProps(props)
		{
			this.init(props);
		}

		init(props)
		{
			this.id = CommonUtils.isNotEmptyString(props.id) ? props.id : Random.getString();

			this.uid = CommonUtils.isNotEmptyString(props.uid) ? props.uid : Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.settings = props.settings ? props.settings : {};

			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, 'model', null);
			/** @type {EntityScheme} */
			this.scheme = BX.prop.get(this.settings, 'scheme', null);
			/** @type {EntityConfig} */
			this.config = BX.prop.get(this.settings, 'config', null);

			this.payload = BX.prop.get(this.settings, 'payload', '');
			this.entityTypeName = BX.prop.getString(this.settings, 'entityTypeName', '');
			this.entityId = BX.prop.getInteger(this.settings, 'entityId', 0);
			this.context = BX.prop.getObject(this.settings, 'context', {});
			this.contextId = BX.prop.getString(this.settings, 'contextId', '');
			this.serviceUrl = BX.prop.getString(this.settings, 'serviceUrl', '');
			this.moduleId = BX.prop.getString(this.settings, 'moduleId', '');

			this.isNew = this.entityId <= 0 && this.model.isIdentifiable();

			this.readOnly = BX.prop.getBoolean(this.settings, 'readOnly', false);
			if (this.readOnly)
			{
				this.enableSectionEdit = this.enableSectionCreation = false;
			}

			this.toolbarAlwaysVisible = BX.prop.getBoolean(this.settings, 'isToolbarAlwaysVisible', false);

			this.enableVisibilityPolicy = BX.prop.getBoolean(this.settings, 'enableVisibilityPolicy', true);

			this.enableModeToggle = false;
			let initialMode = EntityEditorMode.intermediate;

			if (!this.readOnly)
			{
				this.enableModeToggle = BX.prop.getBoolean(this.settings, 'enableModeToggle', true);
				initialMode = EntityEditorMode.parse(BX.prop.getString(this.settings, 'initialMode', ''));
			}

			if (this.isNew && !this.readOnly)
			{
				this.mode = EntityEditorMode.edit;
			}
			else
			{
				this.mode = initialMode !== EntityEditorMode.intermediate ? initialMode : EntityEditorMode.view;
			}

			this.processControlModeChange(this);

			this.isChanged = false;
			this.entityDetailsUrl = BX.prop.getString(this.settings, 'entityDetailsUrl', '');

			this.customEventEmitter.emit('UI.EntityEditor::onInit', [{
				readOnly: this.readOnly,
			}]);
		}

		getMode()
		{
			return this.mode;
		}

		getName()
		{
			return this.id;
		}

		getEntityDetailsUrl()
		{
			return this.entityDetailsUrl;
		}

		render()
		{
			const { onScroll, showBottomPadding } = this.props;

			return ScrollView(
				{
					ref: (ref) => this.scrollViewRef = ref,
					style: {
						flex: 1,
					},
					resizableByKeyboard: true,
					showsVerticalScrollIndicator: false,
					showsHorizontalScrollIndicator: false,
					onScroll: (params) => {
						this.scrollY = params.contentOffset.y;
						if (onScroll)
						{
							onScroll(params);
						}
					},
					scrollEventThrottle: 15,
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					slot: () => {
						return View(
							{
								style: {
									flexDirection: 'column',
									paddingTop: 12,
								},
							},
							...this.renderControls(),
							...this.initializeControllers(),
							showBottomPadding && View({ style: { height: 80 } }),
						);
					},
				}),
			);
		}

		scrollTop(animated = true)
		{
			if (this.scrollViewRef)
			{
				this.scrollViewRef.scrollToBegin(animated);
			}
		}

		scrollTo(position, animated = true)
		{
			if (this.scrollViewRef)
			{
				this.scrollViewRef.scrollTo({ ...position, animated });
			}
		}

		scrollToInvalidField(fieldView, animated = true)
		{
			if (this.isScrollToViewEnabled && !this.alreadyScrolledToInvalidField)
			{
				this.alreadyScrolledToInvalidField = true;

				const position = this.scrollViewRef.getPosition(fieldView);
				this.scrollTo(position, animated);
			}
		}

		scrollToFocusedField(fieldView, animated = true)
		{
			if (this.isScrollToViewEnabled)
			{
				const { y } = this.scrollViewRef.getPosition(fieldView);

				if (y > this.scrollY + device.screen.height * 0.4)
				{
					const positionY = y - 150;
					this.scrollTo({ y: positionY }, animated);
				}
			}
		}

		getValuesFromModel()
		{
			if (this.model)
			{
				return this.model.getFields();
			}

			return {};
		}

		getValuesToSave()
		{
			let controlValues = {};

			[...this.controls, ...this.controllers].forEach((control) => {
				controlValues = { ...controlValues, ...control.getValuesToSave() };
			});

			const loading = (
				Object
					.keys(controlValues)
					.filter((name) => controlValues[name] instanceof Promise)
					.map((name) => controlValues[name])
			);

			return (
				Promise
					.all(loading)
					.then((processedFields) => {
						processedFields.forEach((field) => {
							controlValues = { ...controlValues, ...field };
						});

						return controlValues;
					})
			);
		}

		validate()
		{
			this.alreadyScrolledToInvalidField = false;

			const validator = EntityAsyncValidator.create();

			this.getControls().forEach((control) => {
				validator.addResult(control.validate());
			});

			return validator.validate();
		}

		renderControls()
		{
			return (
				this.scheme.getElements()
					.map((element, index) => {
						return this.renderControl(
							useCallback((ref) => this.controls[index] = ref, [index]),
							element.getType(),
							element.getName(),
							this.uid,
							{
								schemeElement: element,
								readOnly: this.readOnly,
								mode: this.mode,
								isChanged: this.isChanged,
							},
						);
					})
					.filter((element) => element)
			);
		}

		initializeControllers()
		{
			const controllers = BX.prop.getArray(this.settings, 'controllers', []);

			return (
				controllers
					.map((controller, index) => this.createController(
						ref => {
							this.controllers[index] = ref;
						},
						BX.prop.getString(controller, 'name', ''),
						BX.prop.getString(controller, 'type', ''),
						this.uid,
						BX.prop.getObject(controller, 'config', {}),
					))
					.filter((controller) => controller)
			);
		}

		getId()
		{
			return this.id;
		}

		getEntityTypeName()
		{
			return this.entityTypeName;
		}

		getEntityId()
		{
			return this.entityId;
		}

		getControls()
		{
			return this.controls.filter((control) => control);
		}

		blurInlineFields(fieldToSkip = null)
		{
			return Promise.all(this.getControls().map(control => control.blurInlineFields(fieldToSkip)));
		}

		getControlById(controlId)
		{
			return this.getControls().find(control => control.getId() === controlId);
		}

		/**
		 * @param {String} controlId
		 * @param {EntityEditorBaseControl[]|null} controls
		 * @return {EntityEditorBaseControl|EntityEditorField|null}
		 */
		getControlByIdRecursive(controlId, controls = null)
		{
			if (!controls)
			{
				controls = this.getControls();
			}

			return controls.reduce((result, control) => {
				if (result)
				{
					return result;
				}

				if (!(control instanceof EntityEditorBaseControl))
				{
					return result;
				}

				if (control && control.getId() === controlId)
				{
					return control;
				}
				else if (
					control instanceof EntityEditorColumn
					|| control instanceof EntityEditorSection
				)
				{
					const found = this.getControlByIdRecursive(controlId, control.getControls());

					return found ? found : result;
				}

				return result;
			}, null);
		}

		/**
		 * @param {EntityEditorBaseControl[]|null} controls
		 * @returns {EntityEditorField[]}
		 */
		getFieldControls(controls = null)
		{
			if (!controls)
			{
				controls = this.getControls();
			}

			return (
				controls
					.reduce((result, control) => {
						if (!(control instanceof EntityEditorBaseControl))
						{
							return result;
						}

						if (control instanceof EntityEditorField)
						{
							result.unshift(control);
						}
						else
						{
							result = [...result, ...this.getFieldControls(control.getControls())];
						}

						return result;
					}, [])
			);
		}

		renderControl(ref, type, id, uid, settings)
		{
			settings['serviceUrl'] = this.serviceUrl;
			settings['model'] = this.model;
			settings['editor'] = this;

			return EntityEditorControlFactory.create({ ref, type, id, uid, settings, layout: this.layout });
		}

		createController(ref, id, type, uid, settings)
		{
			settings['model'] = this.model;
			settings['editor'] = this;

			return EntityEditorControllerFactory.create({ ref, type, id, uid, settings });
		}

		canChangeScheme()
		{
			return this.config && this.config.isChangeable();
		}

		isModeToggleEnabled()
		{
			return this.enableModeToggle;
		}

		/**
		 * @param {EntityEditorBaseControl} control
		 * @param mode
		 * @param options
		 * @returns {Promise}
		 */
		switchControlMode(control, mode, options = {})
		{
			if (!this.isModeToggleEnabled())
			{
				if (control.getMode() === EntityEditorMode.edit)
				{
					return control.setState({ focus: true });
				}

				return Promise.reject();
			}

			return control.setMode(mode, options, true);
		}

		/**
		 * @param {EntityEditor|EntityEditorBaseControl} control
		 */
		processControlModeChange(control)
		{
			if (control.getMode() === EntityEditorMode.edit)
			{
				this.emitSetEditMode(control);
			}
			else if (!this.isToolPanelAlwaysVisible() && !this.hasActiveControl())
			{
				this.emitViewEditMode(control);
			}
		}

		hasActiveControl()
		{
			return this.getControls().find(control => control.isActive());
		}

		isToolPanelAlwaysVisible()
		{
			return this.toolbarAlwaysVisible;
		}

		/**
		 * @param {EntityEditorBaseControl} control
		 */
		emitSetEditMode(control)
		{
			this.customEventEmitter.emit('UI.EntityEditor::onSetEditMode', [control.getName()]);
		}

		/**
		 * @param {EntityEditorBaseControl} control
		 */
		emitViewEditMode(control)
		{
			this.customEventEmitter.emit('UI.EntityEditor::onSetViewMode', [control.getName()]);
		}

		markAsChanged()
		{
			this.isChanged = true;
		}

		isVisibilityPolicyEnabled()
		{
			return this.enableVisibilityPolicy;
		}
	}

	module.exports = { EntityEditor };
});