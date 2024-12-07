/**
 * @module layout/ui/form/src/field-schema
 */
jn.define('layout/ui/form/src/field-schema', (require, exports, module) => {
	const { CompactMode } = require('layout/ui/form/src/enums');
	const { mergeImmutable } = require('utils/object');

	class FieldSchema
	{
		/**
		 * @param {UIFormFieldSchema} schema
		 */
		constructor(schema)
		{
			this.type = schema.type;
			this.factory = schema.factory;
			this.props = schema.props || {};
			this.isPrimaryField = Boolean(schema.isPrimary);
			this.debounceTimeout = schema.debounceTimeout || 0;

			if (typeof schema?.compact === 'function')
			{
				this.compact = {
					mode: schema.defaultCompactMode,
					factory: schema.compact,
					extraProps: {},
				};
			}
			else
			{
				this.compact = {
					mode: schema?.compact?.mode || schema.defaultCompactMode,
					factory: schema?.compact?.factory,
					extraProps: schema?.compact?.extraProps || {},
				};
			}
		}

		/**
		 * @public
		 * @param {any} value
		 * @param {any} extendedValue
		 * @param {...any} rest
		 */
		onChange(value, extendedValue, ...rest)
		{
			this.props.onChange?.(value, extendedValue, ...rest);
		}

		/**
		 * @public
		 * @param {any} value
		 */
		onBlur(value)
		{
			this.props.onBlur?.(value);
		}

		/**
		 * @public
		 * @param {UIFormBaseField} ref
		 */
		ref(ref)
		{
			this.props.ref?.(ref);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getCompactMode()
		{
			const { mode } = this.compact;

			if (mode && CompactMode.has(mode))
			{
				return mode;
			}

			return CompactMode.default;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getId()
		{
			return this.props.id;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getDebounceTimeout()
		{
			return this.debounceTimeout;
		}

		/**
		 * @public
		 * @return {object}
		 */
		getProps()
		{
			return {
				...this.props,
				testId: this.props.testId || this.props.id,
			};
		}

		/**
		 * @public
		 * @return {object}
		 */
		getCompactProps()
		{
			const testId = this.compact.extraProps.testId
				|| this.props.testId
				|| `${this.props.id}_compact`;

			return mergeImmutable(
				this.getProps(),
				this.compact.extraProps,
				{ testId },
			);
		}

		/**
		 * @public
		 * @param {UIFormFieldFactory} fallbackFactory
		 * @return {UIFormFieldFactoryFn}
		 */
		getFactory(fallbackFactory)
		{
			return this.factory ?? ((props) => fallbackFactory.create(this.type, props));
		}

		/**
		 * @public
		 * @param {UIFormFieldFactory} fallbackFactory
		 * @return {UIFormFieldFactoryFn}
		 */
		getCompactFactory(fallbackFactory)
		{
			return this.compact.factory ?? ((props) => fallbackFactory.create(this.type, props));
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isPrimary()
		{
			return this.isPrimaryField;
		}
	}

	module.exports = { FieldSchema };
});
