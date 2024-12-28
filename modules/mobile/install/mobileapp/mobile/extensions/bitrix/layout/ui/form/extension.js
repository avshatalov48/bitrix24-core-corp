/**
 * @module layout/ui/form
 */
jn.define('layout/ui/form', (require, exports, module) => {
	const { validateProps, defaultProps, propTypes } = require('layout/ui/form/src/props-validator');
	const { CompactMode, SubmitFailureReason } = require('layout/ui/form/src/enums');
	const { FieldSchema } = require('layout/ui/form/src/field-schema');
	const { animate } = require('animation');
	const { Type } = require('type');

	const { PureComponent } = require('layout/pure-component');
	const { FieldFactory } = require('layout/ui/fields');
	const { Card } = require('ui-system/layout/card');

	const { clone, isEqual, isEmpty, mergeImmutable } = require('utils/object');
	const { debounce, useCallback } = require('utils/function');

	const { Logger, LogType } = require('utils/logger');
	const logger = new Logger([
		// LogType.INFO,
		LogType.ERROR,
	]);

	class Form extends PureComponent
	{
		// region init

		constructor(props)
		{
			super(props);

			validateProps(props);

			this.state = this.initState(props);
			this.prevState = this.state;

			/** @type {Object.<string, UIFormBaseField>} */
			this.fieldRefs = {};

			/** @type {Object.<string, UIFormBaseField>} */
			this.compactFieldRefs = {};

			/** @type {Object.<string, object>} */
			this.compactFieldContainerRefs = {};

			/** @type {Object.<string, object>} */
			this.secondaryFieldContainerRefs = {};

			this.nextTick = {
				animations: [],
				nextState: false,
			};

			this.setStateQueue = Promise.resolve();
		}

		componentWillReceiveProps(props)
		{
			logger.info('layout/ui/form: componentWillReceiveProps');

			super.componentWillReceiveProps(props);

			this.state = this.initState(props);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			logger.info('layout/ui/form: shouldComponentUpdate');

			this.prevState = this.state;

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		componentDidUpdate(prevProps, prevState)
		{
			logger.info('layout/ui/form: componentDidUpdate');

			super.componentDidUpdate(prevProps, prevState);

			const animations = this.nextTick.animations.map((item) => item.animation());
			if (animations.length > 0)
			{
				Promise.all(animations)
					.then(() => {
						if (this.nextTick.nextState)
						{
							this.setState(this.nextTick.nextState);
							this.nextTick.nextState = null;
						}
					})
					.catch((err) => logger.error('layout/ui/form: animations in componentDidUpdate', err));
			}
		}

		/**
		 * @private
		 * @return {{
		 *     values: Object.<string, any>,
		 *     extendedValues: Object.<string, any>,
		 *     originalValues: Object.<string, any>,
		 *     originalExtendedValues: Object.<string, any>,
		 *     schema: Object.<string, FieldSchema>
		 * }}
		 */
		initState(props)
		{
			const values = {};
			const extendedValues = {};
			const schema = {};

			const processField = (field, isPrimary) => {
				const id = field.props.id;

				values[id] = field.props.value;
				extendedValues[id] = field.props.config?.items;
				schema[id] = new FieldSchema({
					...field,
					isPrimary,
					defaultCompactMode: props.compactMode,
				});
			};

			[...props.primaryFields].forEach((field) => processField(field, true));
			[...props.secondaryFields].forEach((field) => processField(field, false));

			return {
				values,
				extendedValues,
				schema,
				originalValues: clone(values),
				originalExtendedValues: clone(extendedValues),
			};
		}

		// endregion

		// region animation API

		setState(nextState, callback)
		{
			logger.info('layout/ui/form: add to setState queue');

			this.setStateQueue = this.setStateQueue.then(() => {
				return new Promise((resolve) => {
					logger.info('layout/ui/form: setState');

					super.setState(nextState, () => {
						resolve();
						callback?.();
					});
				});
			});
		}

		fillAnimations()
		{
			if (isEqual(this.prevState, this.state))
			{
				return;
			}

			const { schema } = this.state;

			let { values: prevValues, extendedValues: prevExtendedValues } = this.prevState;
			prevValues = clone(prevValues);
			prevExtendedValues = clone(prevExtendedValues);

			let { values: nextValues, extendedValues: nextExtendedValues } = this.state;
			nextValues = clone(nextValues);
			nextExtendedValues = clone(nextExtendedValues);

			const animations = [];

			Object.values(schema).forEach((fieldSchema) => {
				if (fieldSchema.isPrimary())
				{
					return;
				}

				const fieldId = fieldSchema.getId();

				if (isEqual(prevValues[fieldId], nextValues[fieldId]))
				{
					return;
				}

				const hasPreviousValue = Type.isArray(prevValues[fieldId]) || Type.isObjectLike(prevValues[fieldId])
					? !isEmpty(prevValues[fieldId])
					: Boolean(prevValues[fieldId]);

				const hasUpcomingValue = Type.isArray(nextValues[fieldId]) || Type.isObjectLike(nextValues[fieldId])
					? !isEmpty(nextValues[fieldId])
					: Boolean(nextValues[fieldId]);

				// todo refactor to different methods
				const needHideCompactField = (
					fieldSchema.getCompactMode() === CompactMode.FILL_COMPACT_AND_HIDE
					&& !hasPreviousValue
					&& hasUpcomingValue
					&& !this.hasFieldAnimation(fieldId)
				);

				if (needHideCompactField)
				{
					this.state.values[fieldId] = prevValues[fieldId];
					this.state.extendedValues[fieldId] = prevExtendedValues[fieldId];

					animations.push({
						id: fieldId,
						type: 'hide',
						animation: () => animate(
							this.compactFieldContainerRefs[fieldId],
							{ width: 0, opacity: 0, duration: 100 },
						),
					});
				}

				const needShowSecondaryField = (
					!hasPreviousValue
					&& hasUpcomingValue
					&& !this.hasFieldShowAnimation(fieldId)
				);

				if (needShowSecondaryField)
				{
					animations.push({
						id: fieldId,
						type: 'show',
						animation: () => animate(
							this.secondaryFieldContainerRefs[fieldId],
							{ opacity: 1, duration: 250 },
						),
					});
				}

				const needHideSecondaryField = (
					hasPreviousValue
					&& !hasUpcomingValue
					&& !this.hasFieldHideAnimation(fieldId)
				);

				if (needHideSecondaryField)
				{
					this.state.values[fieldId] = prevValues[fieldId];
					this.state.extendedValues[fieldId] = prevExtendedValues[fieldId];

					animations.push({
						id: fieldId,
						type: 'hide',
						animation: () => animate(
							this.secondaryFieldContainerRefs[fieldId],
							{ opacity: 0, duration: 250 },
						),
					});
				}
			});

			this.nextTick.animations = animations;
			this.nextTick.nextState = {
				values: nextValues,
				extendedValues: nextExtendedValues,
			};

			logger.info('layout/ui/form: fillAnimations - nextTick.animations', this.nextTick.animations);
		}

		hasFieldAnimation(fieldId)
		{
			return this.nextTick.animations.some((item) => item.id === fieldId);
		}

		hasFieldShowAnimation(fieldId)
		{
			return this.nextTick.animations.some((item) => item.id === fieldId && item.type === 'show');
		}

		hasFieldHideAnimation(fieldId)
		{
			return this.nextTick.animations.some((item) => item.id === fieldId && item.type === 'hide');
		}

		// endregion

		// region public API

		/**
		 * @public
		 * @return {boolean}
		 */
		validate()
		{
			return this.getFields().every((field) => {
				const isValid = field.validate();

				if (!isValid)
				{
					if (this.props.onFieldValidationFailure)
					{
						this.props.onFieldValidationFailure(field, this);
					}

					if (this.props.scrollToInvalidField)
					{
						this.scrollToField(field.getId());
					}
				}

				return isValid;
			});
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isValid()
		{
			return this.getFields().every((field) => field.isValid());
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		hasUploadingFiles()
		{
			// todo we probably need some workaround for multiple/combined fields
			return this.getFields().some((field) => field.hasUploadingFiles && field.hasUploadingFiles());
		}

		/**
		 * @public
		 * @return {void}
		 */
		submit()
		{
			const emitFailure = (reason) => {
				if (this.props.onSubmitFailure)
				{
					this.props.onSubmitFailure(this, reason);
				}
			};

			if (!this.validate())
			{
				emitFailure(SubmitFailureReason.VALIDATION);

				return;
			}

			if (this.hasUploadingFiles())
			{
				emitFailure(SubmitFailureReason.FILES_NOT_UPLOADED);

				return;
			}

			if (this.props.onSubmit)
			{
				Promise.resolve(this.props.onSubmit(this))
					.then(() => this.flushOriginalValues())
					.catch((err) => logger.error('layout/ui/form: submit', err));
			}
		}

		/**
		 * @public
		 * @return {Object.<string, any>}
		 */
		serialize()
		{
			return clone(this.state.values);
		}

		/**
		 * @public
		 * @return {{ id: string, value: *}[]}
		 */
		serializeArray()
		{
			return Object.keys(this.state.values).map((id) => ({
				id,
				value: clone(this.state.values[id]),
			}));
		}

		/**
		 * @public
		 * @return {Object.<string, any>}
		 */
		serializeExtended()
		{
			return clone(this.state.extendedValues);
		}

		/**
		 * @public
		 * @return {UIFormBaseField[]}
		 */
		getFields()
		{
			return Object.values(this.fieldRefs);
		}

		/**
		 * @public
		 * @param {string} id
		 * @return {UIFormBaseField|undefined}
		 */
		getField(id)
		{
			return this.fieldRefs[id];
		}

		/**
		 * @public
		 * @param {string} id
		 * @return {UIFormBaseField|undefined}
		 */
		getCompactField(id)
		{
			return this.compactFieldRefs[id];
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		reset()
		{
			return new Promise((resolve) => {
				this.setState(
					() => {
						const values = clone(this.state.originalValues);
						const extendedValues = clone(this.state.originalExtendedValues);

						return { values, extendedValues };
					},
					resolve,
				);
			});
		}

		/**
		 * @public
		 * @param {string} fieldId
		 * @return {Promise}
		 */
		resetField(fieldId)
		{
			return new Promise((resolve) => {
				this.setState(
					(state) => {
						const values = clone(state.values);
						const extendedValues = clone(state.extendedValues);

						values[fieldId] = clone(state.originalValues[fieldId]);
						extendedValues[fieldId] = clone(state.originalExtendedValues[fieldId]);

						return { values, extendedValues };
					},
					() => resolve(this.state.values[fieldId], this.state.extendedValues[fieldId]),
				);
			});
		}

		/**
		 * @public
		 * @return {void}
		 */
		flushOriginalValues()
		{
			this.state.originalValues = clone(this.state.values);
			this.state.originalExtendedValues = clone(this.state.extendedValues);
		}

		// endregion

		// region internal services

		/**
		 * @private
		 * @return {{ create: (type: string, props: {}) => UIFormBaseField|null }}
		 */
		getRegularFieldFactory()
		{
			return this.props.fieldFactory || FieldFactory;
		}

		/**
		 * @private
		 * @return {{ create: (type: string, props: {}) => UIFormBaseField|null }|undefined}
		 */
		getCompactFieldFactory()
		{
			return this.props.compactFieldFactory;
		}

		/**
		 * @private
		 * @param {string} fieldId
		 */
		scrollToField(fieldId)
		{
			const scrollable = this.props?.scrollableProvider();
			const field = this.getField(fieldId);
			if (scrollable && field?.fieldContainerRef)
			{
				const position = scrollable.getPosition(field.fieldContainerRef);
				scrollable.scrollTo({ ...position, animated: true });
			}
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @return {FieldSchema}
		 */
		getFieldSchema(fieldId)
		{
			return this.state.schema[fieldId];
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {any} value
		 */
		onBlurRegularField(fieldId, value)
		{
			this.getFieldSchema(fieldId).onBlur(value);

			const originalValue = this.state.originalValues[fieldId];

			this.props.onBlur?.({
				fieldId,
				value,
				changed: !isEqual(value, originalValue),
				field: this.getField(fieldId),
				form: this,
			});
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {any} value
		 * @param {any} extendedValue
		 * @param {...any} rest
		 */
		onChangeRegularField(fieldId, value, extendedValue, ...rest)
		{
			this.updateFieldValue(fieldId, value, extendedValue)
				.then(() => this.invokeCustomOnChangeHandler(fieldId, value, extendedValue, ...rest))
				.catch((err) => logger.error('layout/ui/form: onChangeRegularField', err));
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {any} value
		 * @param {any} extendedValue
		 * @param {...any} rest
		 */
		onChangeCompactField(fieldId, value, extendedValue, ...rest)
		{
			const secondaryField = this.getField(fieldId);
			const wasVisible = secondaryField && this.isFieldVisible(secondaryField);

			const compactFieldRef = this.compactFieldRefs[fieldId];
			const schema = this.getFieldSchema(fieldId);
			const needHide = (
				schema.getCompactMode() === CompactMode.FILL_COMPACT_AND_HIDE
				&& compactFieldRef
				&& !compactFieldRef.isEmpty()
			);

			const handleUpdate = () => {
				this.updateFieldValue(fieldId, value, extendedValue, ...rest)
					.then(() => {
						const nowVisible = secondaryField && this.isFieldVisible(secondaryField);
						if (!wasVisible && nowVisible && this.props.scrollToAppearedField)
						{
							this.scrollToField(fieldId);
						}

						this.invokeCustomOnChangeHandler(fieldId, value, extendedValue, ...rest);
					})
					.catch(() => {});
			};

			if (needHide)
			{
				const container = this.compactFieldContainerRefs[fieldId];

				container?.animate({ width: 0, duration: 100 }, () => handleUpdate());
			}
			else
			{
				handleUpdate();
			}
		}

		get useState()
		{
			return this.props.useState ?? true;
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {any} value
		 * @param {any} extendedValue
		 * @return {Promise}
		 */
		updateFieldValue(fieldId, value, extendedValue)
		{
			return new Promise((resolve) => {
				if (this.useState)
				{
					this.setState(
						(state) => ({
							values: {
								...state.values,
								[fieldId]: value,
							},
							extendedValues: {
								...state.extendedValues,
								[fieldId]: extendedValue,
							},
						}),
						resolve,
					);
				}
				else
				{
					resolve();
				}
			});
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {any} value
		 * @param {any} extendedValue
		 * @param {...any} rest
		 * @return {void}
		 */
		invokeCustomOnChangeHandler(fieldId, value, extendedValue, ...rest)
		{
			this.getFieldSchema(fieldId).onChange(value, extendedValue, ...rest);

			this.props.onChange?.({
				fieldId,
				value,
				extendedValue,
				field: this.getField(fieldId),
				form: this,
			});
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {UIFormBaseField|undefined} ref
		 * @return {void}
		 */
		bindRegularFieldRef(fieldId, ref)
		{
			this.fieldRefs[fieldId] = ref;

			// we can get empty ref of already removed field here,
			// so we have to check if schema exists.
			this.getFieldSchema(fieldId)?.ref(ref);
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {UIFormBaseField|undefined} ref
		 */
		bindCompactFieldRef(id, ref)
		{
			this.compactFieldRefs[id] = ref;
		}

		/**
		 * @private
		 * @param {UIFormBaseField} field
		 * @param {object} containerRef
		 */
		bindCompactFieldContainerRef(field, containerRef)
		{
			this.compactFieldContainerRefs[field.getId()] = containerRef;
		}

		/**
		 * @private
		 * @param {UIFormBaseField} field
		 * @param {object} containerRef
		 */
		bindSecondaryFieldContainerRef(field, containerRef)
		{
			this.secondaryFieldContainerRefs[field.getId()] = containerRef;
		}

		/**
		 * @private
		 * @param {string} code
		 * @return {string}
		 */
		getTestId(code)
		{
			const prefix = this.props.testId || 'UIForm';

			return `${prefix}_${code}`;
		}

		// endregion

		// region render

		render()
		{
			logger.info('layout/ui/form: render');

			this.fillAnimations();

			return View(
				{
					testId: this.getTestId('Container'),
					style: this.getStyle().container,
				},
				this.renderBeforeFieldsSlot(),
				this.renderPrimaryFields(),
				this.renderAfterPrimaryFieldsSlot(),
				this.renderCompactBar(),
				this.renderAfterCompactBarSlot(),
				this.renderSecondaryFields(),
				this.renderAfterFieldsSlot(),
			);
		}

		renderPrimaryFields()
		{
			return View(
				{
					testId: this.getTestId('PrimaryContainer'),
					style: {
						paddingBottom: 10,
						...this.getStyle().primaryContainer,
					},
				},
				...this.props.primaryFields.map((item, index) => {
					const schema = this.getFieldSchema(item.props.id);
					const field = this.renderField(schema);
					if (!field)
					{
						return null;
					}

					const fieldStyle = this.getStyle().primaryField;

					return View(
						{
							testId: this.getTestId('PrimaryFieldContainer'),
							style: (typeof fieldStyle === 'function' ? fieldStyle(field) : fieldStyle),
						},
						this.renderBeforeFieldSlot(field, index, schema.isPrimary()),
						field,
						this.renderAfterFieldSlot(field, index, schema.isPrimary()),
					);
				}),
			);
		}

		renderSecondaryFields()
		{
			return View(
				{
					testId: this.getTestId('SecondaryContainer'),
					style: this.getStyle().secondaryContainer,
				},
				...this.props.secondaryFields.map((item, index) => {
					const fieldId = item.props.id;
					const schema = this.getFieldSchema(fieldId);
					const field = this.renderField(schema);
					if (!field)
					{
						return null;
					}

					const fieldStyle = this.getStyle().secondaryField;
					const display = this.isFieldVisible(field) || this.hasFieldAnimation(fieldId)
						? 'flex'
						: 'none';
					const opacity = this.hasFieldShowAnimation(fieldId) ? 0 : 1;

					return Card(
						{
							testId: this.getTestId('SecondaryFieldContainer'),
							style: {
								...(typeof fieldStyle === 'function' ? fieldStyle(field) : fieldStyle),
								display,
								opacity,
							},
							ref: (ref) => {
								this.bindSecondaryFieldContainerRef(field, ref);
							},
						},
						this.renderBeforeFieldSlot(field, index, schema.isPrimary()),
						field,
						this.renderAfterFieldSlot(field, index, schema.isPrimary()),
					);
				}),
			);
		}

		/**
		 * @param {FieldSchema} schema
		 * @return {UIFormBaseField|null}
		 */
		renderField(schema)
		{
			const fieldId = schema.getId();

			const changeHandler = async (nextValue, extendedValue, ...rest) => {
				if (!schema.isPrimary())
				{
					await this.animateFieldAppearance(fieldId, nextValue);
				}

				this.onChangeRegularField(fieldId, nextValue, extendedValue, ...rest);
			};

			const debouncedChangeHandler = debounce(changeHandler, schema.getDebounceTimeout());

			const decoratedProps = mergeImmutable(schema.getProps(), {
				value: this.state.values[fieldId],
				config: {
					items: this.state.extendedValues[fieldId],
					reloadEntityListFromProps: true,
					parentWidget: this.props.parentWidget,
				},
				ref: useCallback((ref) => this.bindRegularFieldRef(fieldId, ref), [fieldId]),
				onChange: useCallback(debouncedChangeHandler, [fieldId]),
				onBlur: useCallback((value) => this.onBlurRegularField(fieldId, value), [fieldId]),
			});

			const factory = schema.getFactory(this.getRegularFieldFactory());

			return factory(decoratedProps);
		}

		async animateFieldAppearance(fieldId, nextValue)
		{
			const prevValue = this.state.values[fieldId];
			const hasPreviousValue = Type.isArray(prevValue) || Type.isObjectLike(prevValue)
				? !isEmpty(prevValue)
				: Boolean(prevValue);

			const hasUpcomingValue = Type.isArray(nextValue) || Type.isObjectLike(nextValue)
				? !isEmpty(nextValue)
				: Boolean(nextValue);

			const needHideSecondaryField = (
				hasPreviousValue
				&& !hasUpcomingValue
				&& !this.hasFieldHideAnimation(fieldId)
			);

			if (needHideSecondaryField)
			{
				const animation = async () => animate(
					this.secondaryFieldContainerRefs[fieldId],
					{ opacity: 0, duration: 250 },
				);

				this.nextTick.animations.push({
					id: fieldId,
					type: 'hide',
					animation,
				});

				await animation();
			}
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		hasCompactVisibleFields()
		{
			return this.props.secondaryFields.some((item) => {
				const schema = this.getFieldSchema(item.props.id);
				const field = this.renderCompactField(schema);

				return this.isCompactFieldVisible(field);
			});
		}

		renderCompactBar()
		{
			const slotBefore = this.renderBeforeCompactFieldsSlot();
			const slotAfter = this.renderAfterCompactFieldsSlot();
			const visibleFieldIds = [];
			const order = this.#calculateCompactFieldsOrder();

			const fields = order.map((fieldId) => {
				const schema = this.getFieldSchema(fieldId);
				const field = this.renderCompactField(schema);
				if (field && this.isCompactFieldVisible(field))
				{
					visibleFieldIds.push(field.getId());
				}

				return field;
			}).filter(Boolean);

			if (!slotBefore && !slotAfter && visibleFieldIds.length === 0)
			{
				return null;
			}

			const { compactContainer, compactInnerContainer, compactField } = this.getStyle();

			return View(
				{},
				ScrollView(
					{
						horizontal: true,
						showsHorizontalScrollIndicator: false,
						style: {
							height: 58,
							width: '100%',
							...compactContainer,
						},
					},
					View(
						{
							testId: this.getTestId('CompactBarContainer'),
							style: {
								paddingBottom: 18, // todo replace with token?
								paddingTop: 0,
								flexDirection: 'row',
								justifyContent: 'flex-start',
								alignItems: 'center',
								...compactInnerContainer,
							},
						},
						View(
							{
								testId: this.getTestId('CompactBarContainer_SlotBefore'),
								style: {
									marginRight: 8, // todo replace with indent token
									...compactField,
									display: slotBefore ? 'flex' : 'none',
								},
							},
							slotBefore,
						),
						...fields.map((field, index) => {
							const isVisible = visibleFieldIds.includes(field.getId());
							const isLast = index === fields.length - 1;

							return View(
								{
									testId: this.getTestId('CompactBarContainer_Field'),
									style: {
										...compactField,
										width: isVisible ? undefined : 0,
										display: isVisible ? 'flex' : 'none',
										flexDirection: 'row',
										marginRight: isLast ? 0 : 8,
									},
									ref: (ref) => {
										this.bindCompactFieldContainerRef(field, ref);
									},
								},
								field,
							);
						}),
						View(
							{
								testId: this.getTestId('CompactBarContainer_SlotAfter'),
								style: {
									marginRight: 8, // todo replace with indent token
									...compactField,
									display: slotAfter ? 'flex' : 'none',
								},
							},
							slotAfter,
						),
					),
				),
			);
		}

		/**
		 * @param {FieldSchema} schema
		 * @return {UIFormBaseField|null}
		 */
		renderCompactField(schema)
		{
			const fieldId = schema.getId();

			const changeHandler = (nextValue, extendedValue, ...rest) => {
				this.onChangeCompactField(fieldId, nextValue, extendedValue, ...rest);
			};

			const decoratedProps = mergeImmutable(schema.getCompactProps(), {
				value: this.state.values[fieldId],
				config: {
					items: this.state.extendedValues[fieldId],
					reloadEntityListFromProps: true,
					showValue: schema.getCompactMode() !== CompactMode.FILL_COMPACT_AND_HIDE,
					parentWidget: this.props.parentWidget,
				},
				ref: useCallback((r) => this.bindCompactFieldRef(fieldId, r), [fieldId]),
				onChange: useCallback(changeHandler, [fieldId]),
			});

			const factory = schema.getCompactFactory(this.getCompactFieldFactory());

			/** @type {UIFormBaseField|null|undefined} */
			const field = factory(decoratedProps);

			if (!field)
			{
				return null;
			}

			return field;
		}

		renderBeforeFieldsSlot()
		{
			if (this.props.renderBeforeFields)
			{
				return this.props.renderBeforeFields(this);
			}

			return null;
		}

		renderAfterFieldsSlot()
		{
			if (this.props.renderAfterFields)
			{
				return this.props.renderAfterFields(this);
			}

			return null;
		}

		renderAfterPrimaryFieldsSlot()
		{
			if (this.props.renderAfterPrimaryFields)
			{
				return this.props.renderAfterPrimaryFields(this);
			}

			return null;
		}

		renderBeforeFieldSlot(field, index, isPrimary)
		{
			if (this.props.renderBeforeField)
			{
				const isSecondary = !isPrimary;

				return this.props.renderBeforeField(field, {
					index,
					isPrimary,
					isFirstPrimary: isPrimary && index === 0,
					isLastPrimary: isPrimary && (index === this.props.primaryFields.length - 1),
					isFirstSecondary: isSecondary && index === 0,
					isLastSecondary: isSecondary && (index === this.props.secondaryFields.length - 1),
					form: this,
				});
			}

			return null;
		}

		renderAfterFieldSlot(field, index, isPrimary)
		{
			if (this.props.renderAfterField)
			{
				const isSecondary = !isPrimary;

				return this.props.renderAfterField(field, {
					index,
					isPrimary,
					isFirstPrimary: isPrimary && index === 0,
					isLastPrimary: isPrimary && (index === this.props.primaryFields.length - 1),
					isFirstSecondary: isSecondary && index === 0,
					isLastSecondary: isSecondary && (index === this.props.secondaryFields.length - 1),
					form: this,
				});
			}

			return null;
		}

		renderAfterCompactBarSlot()
		{
			if (this.props.renderAfterCompactBar)
			{
				return this.props.renderAfterCompactBar(this);
			}

			return null;
		}

		renderBeforeCompactFieldsSlot()
		{
			if (this.props.renderBeforeCompactFields)
			{
				return this.props.renderBeforeCompactFields(this);
			}

			return null;
		}

		renderAfterCompactFieldsSlot()
		{
			if (this.props.renderAfterCompactFields)
			{
				return this.props.renderAfterCompactFields(this);
			}

			return null;
		}

		#calculateCompactFieldsOrder()
		{
			const defaultOrder = this.props.secondaryFields.map((item) => item.props.id);
			const placeholder = '...';

			if (Array.isArray(this.props.compactOrder))
			{
				const uniq = (val) => [...new Set(val)];
				const validCompactOrder = uniq(this.props.compactOrder.filter((item) => {
					return item === placeholder || defaultOrder.includes(item);
				}));
				const restFields = defaultOrder.filter((item) => !validCompactOrder.includes(item));

				if (restFields.length > 0)
				{
					const placeholderPosition = validCompactOrder.indexOf(placeholder);
					const injectPosition = placeholderPosition > -1 ? placeholderPosition : validCompactOrder.length;

					validCompactOrder.splice(injectPosition, 1, ...restFields);
				}

				return validCompactOrder;
			}

			return defaultOrder;
		}

		/**
		 * @private
		 * @param {UIFormBaseField} field
		 */
		isFieldVisible(field)
		{
			const mode = this.getFieldSchema(field.getId()).getCompactMode();

			if (mode === CompactMode.ONLY && !field.isRequired())
			{
				return false;
			}

			if (this.props.hideEmptyReadonlyFields && field.isEmpty() && field.isReadOnly())
			{
				return false;
			}

			const showWhenFilledOnly = (
				mode === CompactMode.FILL_COMPACT_AND_HIDE
				|| mode === CompactMode.FILL_COMPACT_AND_KEEP
			);

			// eslint-disable-next-line sonarjs/prefer-single-boolean-return
			if (showWhenFilledOnly && field.isEmpty())
			{
				return false;
			}

			return true;
		}

		/**
		 * @private
		 * @param {UIFormBaseField} field
		 */
		isCompactFieldVisible(field)
		{
			const mode = this.getFieldSchema(field.getId()).getCompactMode();

			if (this.props.hideCompactReadonly && field.isReadOnly())
			{
				return false;
			}

			if (mode === CompactMode.NONE)
			{
				return false;
			}

			// eslint-disable-next-line sonarjs/prefer-single-boolean-return
			if (mode === CompactMode.FILL_COMPACT_AND_HIDE && !field.isEmpty())
			{
				return false;
			}

			return true;
		}

		/**
		 * @private
		 * @return {{
		 * 	container: {},
		 * 	primaryContainer: {},
		 * 	primaryField: {}
		 * 	secondaryContainer: {},
		 * 	secondaryField: {} | function(UIFormBaseField):{},
		 * 	compactContainer: {},
		 * 	compactInnerContainer: {},
		 * 	compactField: {},
		 * }}
		 */
		getStyle()
		{
			const styles = this.props.style || {};
			const {
				primaryContainer = {},
				primaryField = {},
				secondaryContainer = {},
				secondaryField = {},
				compactContainer = {},
				compactInnerContainer = {},
				compactField = {},
				...rest
			} = styles;

			return {
				primaryContainer,
				primaryField,
				secondaryContainer,
				secondaryField,
				compactContainer,
				compactInnerContainer,
				compactField,
				container: { ...rest },
			};
		}

		// endregion
	}

	Form.defaultProps = defaultProps;
	Form.propTypes = propTypes;

	module.exports = { Form, CompactMode, SubmitFailureReason };
});
