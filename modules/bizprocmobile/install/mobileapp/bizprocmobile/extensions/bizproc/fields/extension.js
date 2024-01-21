/**
 * @module bizproc/fields
 */
jn.define('bizproc/fields', (require, exports, module) => {
	const { FieldFactory, StringType } = require('layout/ui/fields');
	const { propertyToField } = require('bizproc/fields/types');
	const { isFunction, merge } = require('utils/object');
	const { isNil } = require('utils/type');

	const isEmptyValue = (value) => value === '' || isNil(value);
	const extractValue = (property, value, meta) => {

		const { extractor } = propertyToField[property.Type] || {};

		if (isFunction(extractor))
		{
			return extractor(value, meta);
		}

		return value;
	};

	const renderProperty = function(property, value, fieldProps = {})
	{
		const { type, config } = propertyToField[property.Type] || { type: StringType };
		const rootConfig = fieldProps.config || {};

		console.log(`render "${type}" with value "${value}" (default: "${property.Default}")`);

		return View(
			{},
			FieldFactory.create(type, {
				testId: `PROPERTY_${property.Id.toUpperCase()}`,
				config: merge(rootConfig, isFunction(config) ? config(property) : (config || {})),
				...fieldProps,
				title: property.Name || property.Id,
				value,
				required: property.Required || false,
				multiple: property.Multiple || false,
			}),
		);
	};

	module.exports = { renderProperty, isEmptyValue, extractValue };
});
