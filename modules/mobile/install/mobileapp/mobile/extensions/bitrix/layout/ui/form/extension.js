/**
 * @module layout/ui/form
 */
jn.define('layout/ui/form', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { FieldFactory } = require('layout/ui/fields');
	const { clone, mergeImmutable } = require('utils/object');
	const { debounce, useCallback } = require('utils/function');
	const { assertDefined, assertUnique, PropTypes } = require('utils/validation');

	const ErrorCodes = {
		VALIDATION: 'VALIDATION',
		FILES_NOT_UPLOADED: 'FILES_NOT_UPLOADED',
	};

	const CompactMode = {
		NONE: 'NONE',
		ONLY: 'ONLY',
		BOTH: 'BOTH',
		FILL_COMPACT_AND_HIDE: 'FILL_COMPACT_AND_HIDE',
		FILL_COMPACT_AND_KEEP: 'FILL_COMPACT_AND_KEEP',
	};

	class Form extends PureComponent
	{
		// region init

		constructor(props)
		{
			super(props);

			/** @type {Object.<string, BaseField>} */
			this.fieldRefs = {};

			/** @type {Object.<string, BaseField>} */
			this.compactFieldRefs = {};

			this.validateProps(props);

			const values = this.initValues();

			this.state = {
				values,
				originalValues: clone(values),
			};
		}

		/**
		 * @private
		 * @param {object} formProps
		 */
		validateProps(formProps)
		{
			const { fields = [] } = formProps;

			const ids = [];

			fields.forEach((field) => {
				const { type, factory, props = {} } = field;

				if (!factory)
				{
					assertDefined(type, 'layout/ui/form: type or factory must be defined for every field');
				}

				assertDefined(props.id, 'layout/ui/form: id property must be defined for every field');

				ids.push(props.id);
			});

			assertUnique(ids, 'layout/ui/form: field ids must me unique');
		}

		/**
		 * @private
		 */
		initValues()
		{
			return Object.fromEntries(this.props.fields.map((field) => [
				field.props.id,
				field.props.value,
			]));
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
				emitFailure(ErrorCodes.VALIDATION);

				return;
			}

			if (this.hasUploadingFiles())
			{
				emitFailure(ErrorCodes.FILES_NOT_UPLOADED);

				return;
			}

			if (this.props.onSubmit)
			{
				Promise.resolve(this.props.onSubmit(this))
					.then(() => {
						this.state.originalValues = clone(this.state.values);
					})
					.catch(() => {});
			}
		}

		/**
		 * @public
		 * @return {Object.<string, any>}}
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
				value: this.state.values[id],
			}));
		}

		/**
		 * @public
		 * @return {BaseField[]}
		 */
		getFields()
		{
			return Object.values(this.fieldRefs);
		}

		/**
		 * @public
		 * @param {string} id
		 * @return {BaseField|undefined}
		 */
		getField(id)
		{
			return this.fieldRefs[id];
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		reset()
		{
			return new Promise((resolve) => {
				const values = clone(this.state.originalValues);

				this.setState({ values }, resolve);
			});
		}

		// endregion

		// region internal services

		/**
		 * @private
		 * @return {{ create: (type: string, props: {}) => BaseField|null }}
		 */
		getRegularFieldFactory()
		{
			return this.props.fieldFactory || FieldFactory;
		}

		/**
		 * @private
		 * @return {{ create: (type: string, props: {}) => BaseField|null }|undefined}
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
			if (scrollable && field && field.fieldContainerRef)
			{
				const position = scrollable.getPosition(field.fieldContainerRef);
				scrollable.scrollTo({ ...position, animated: true });
			}
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @return {{}|undefined}
		 */
		getFieldSchema(fieldId)
		{
			return this.props.fields.find((schema) => schema.props.id === fieldId);
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {any} value
		 * @param {...any} rest
		 */
		onChangeRegularField(id, value, ...rest)
		{
			this.updateFieldValue(id, value)
				.then(() => this.invokeCustomOnChangeHandler(id, value, ...rest))
				.catch((err) => console.error(err));
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {any} value
		 * @param {...any} rest
		 */
		onChangeCompactField(id, value, ...rest)
		{
			const regularField = this.getField(id);
			const wasVisible = regularField && this.isFieldVisible(regularField);

			this.updateFieldValue(id, value)
				.then(() => {
					const nowVisible = regularField && this.isFieldVisible(regularField);
					if (!wasVisible && nowVisible && this.props.scrollToAppearedField)
					{
						this.scrollToField(id);
					}

					this.invokeCustomOnChangeHandler(id, value, ...rest);
				})
				.catch(() => {});
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {any} value
		 * @return {Promise}
		 */
		updateFieldValue(id, value)
		{
			return new Promise((resolve) => {
				const values = {
					...this.state.values,
					[id]: value,
				};
				this.setState({ values }, resolve);
			});
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {any} value
		 * @param {...any} rest
		 * @return {void}
		 */
		invokeCustomOnChangeHandler(id, value, ...rest)
		{
			const schema = this.getFieldSchema(id) || {};
			if (schema.props && schema.props.onChange)
			{
				schema.props.onChange(value, ...rest);
			}
		}

		/**
		 * @private
		 * @param {string} fieldId
		 * @param {BaseField|undefined} ref
		 */
		bindRegularFieldRef(fieldId, ref)
		{
			this.fieldRefs[fieldId] = ref;

			const schema = this.getFieldSchema(fieldId) || {};

			if (schema.props && schema.props.ref)
			{
				schema.props.ref(ref);
			}
		}

		/**
		 * @private
		 * @param {string} id
		 * @param {BaseField|undefined} ref
		 */
		bindCompactFieldRef(id, ref)
		{
			this.compactFieldRefs[id] = ref;
		}

		// endregion

		// region render

		render()
		{
			return View(
				{
					style: this.getStyle().container,
				},
				this.renderBeforeFieldsSlot(),
				this.renderFields(),
				this.renderCompactBar(),
				this.renderAfterFieldsSlot(),
			);
		}

		renderFields()
		{
			return View(
				{},
				...this.props.fields.map((schema, index) => {
					const field = this.renderField(schema);
					if (!field)
					{
						return null;
					}

					const isVisible = this.isFieldVisible(field);

					return View(
						{
							style: {
								display: isVisible ? 'flex' : 'none',
							},
						},
						this.renderBeforeFieldSlot(field, index),
						field,
						this.renderAfterFieldSlot(field, index),
					);
				}),
			);
		}

		/**
		 * @param {object} data
		 * @return {BaseField|null}
		 */
		renderField(data)
		{
			const { type, props, factory, debounceTimeout = 0 } = data;

			const { id } = props;

			const debouncedChangeHandler = debounce(
				(nextVal, ...rest) => this.onChangeRegularField(id, nextVal, ...rest),
				debounceTimeout,
			);

			const decoratedProps = {
				...props,
				value: this.state.values[id],
				ref: useCallback((r) => this.bindRegularFieldRef(id, r), [id]),
				onChange: useCallback(debouncedChangeHandler, [id]),
			};

			if (factory)
			{
				return factory(decoratedProps);
			}

			return this.getRegularFieldFactory().create(type, decoratedProps);
		}

		renderCompactBar()
		{
			const nodes = [
				this.renderBeforeCompactBarSlot(),
				...this.props.fields.map((props) => this.renderCompactField(props)),
				this.renderAfterCompactBarSlot(),
			].filter(Boolean);

			if (nodes.length === 0)
			{
				return null;
			}

			return ScrollView(
				{
					horizontal: true,
					style: {
						width: '100%',
						height: 68,
						marginBottom: 18,
					},
				},
				View(
					{
						style: {
							paddingVertical: 18,
							flexDirection: 'row',
							justifyContent: 'flex-start',
							alignItems: 'center',
						},
					},
					...nodes,
				),
			);
		}

		renderCompactField(data)
		{
			const { type, props, compact = {} } = data;

			const { factory, extraProps = {}, mode = CompactMode.NONE } = compact;

			if (mode === CompactMode.NONE)
			{
				return null;
			}

			const { id } = props;

			const decoratedProps = mergeImmutable(props, extraProps, {
				value: this.state.values[id],
				ref: useCallback((r) => this.bindCompactFieldRef(id, r), [id]),
				onChange: useCallback(
					(nextVal, ...rest) => this.onChangeCompactField(id, nextVal, ...rest),
					[id],
				),
			});

			/** @type {BaseField} */
			const field = factory
				? factory(decoratedProps)
				: this.getCompactFieldFactory()?.create(type, decoratedProps);

			if (mode === CompactMode.FILL_COMPACT_AND_HIDE && !field.isEmpty())
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

		renderBeforeFieldSlot(field, index)
		{
			if (this.props.renderBeforeField)
			{
				return this.props.renderBeforeField(field, {
					index,
					isFirst: index === 0,
					isLast: index === this.props.fields.length - 1,
					form: this,
				});
			}

			return null;
		}

		renderAfterFieldSlot(field, index)
		{
			if (this.props.renderAfterField)
			{
				return this.props.renderAfterField(field, {
					index,
					isFirst: index === 0,
					isLast: index === this.props.fields.length - 1,
					form: this,
				});
			}

			return null;
		}

		renderBeforeCompactBarSlot()
		{
			if (this.props.renderBeforeCompactBar)
			{
				return this.props.renderBeforeCompactBar(this);
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

		/**
		 * @private
		 * @param {BaseField} field
		 */
		isFieldVisible(field)
		{
			const { compact = {} } = this.getFieldSchema(field.getId()) || {};
			const { mode } = compact;

			if (mode === CompactMode.ONLY && !field.isRequired())
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
		 * @return {{ container: {} }}
		 */
		getStyle()
		{
			return {
				container: this.props.style || {},
			};
		}

		// endregion
	}

	Form.defaultProps = {
		scrollToInvalidField: true,
		scrollToAppearedField: true,
	};

	Form.propTypes = {
		style: PropTypes.object,
		ref: PropTypes.func,
		fieldFactory: PropTypes.shape({
			create: PropTypes.func,
		}),
		compactFieldFactory: PropTypes.shape({
			create: PropTypes.func,
		}),
		scrollableProvider: PropTypes.func,
		scrollToInvalidField: PropTypes.bool,
		scrollToAppearedField: PropTypes.bool,
		onSubmit: PropTypes.func,
		onSubmitFailure: PropTypes.func,
		onFieldValidationFailure: PropTypes.func,
		renderBeforeFields: PropTypes.func,
		renderAfterFields: PropTypes.func,
		renderBeforeField: PropTypes.func,
		renderAfterField: PropTypes.func,
		renderBeforeCompactBar: PropTypes.func,
		renderAfterCompactBar: PropTypes.func,
		fields: PropTypes.arrayOf(PropTypes.shape({
			type: PropTypes.string,
			factory: PropTypes.func,
			props: PropTypes.shape({
				id: PropTypes.string,
				value: PropTypes.any,
			}),
			compact: PropTypes.shape({
				mode: PropTypes.string,
				factory: PropTypes.func,
				extraProps: PropTypes.object,
			}),
			debounceTimeout: PropTypes.number,
		})),
	};

	module.exports = { Form, CompactMode };
});
