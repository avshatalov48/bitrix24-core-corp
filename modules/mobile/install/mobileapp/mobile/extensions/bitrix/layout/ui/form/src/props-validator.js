/**
 * @module layout/ui/form/src/props-validator
 */
jn.define('layout/ui/form/src/props-validator', (require, exports, module) => {
	const { assertDefined, assertUnique, PropTypes } = require('utils/validation');

	const validateProps = ({ primaryFields = [], secondaryFields = [] }) => {
		const fields = [
			...primaryFields,
			...secondaryFields,
		];

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
	};

	const defaultProps = {
		scrollToInvalidField: true,
		scrollToAppearedField: false,
		hideCompactReadonly: true,
	};

	const FieldSchemaPropType = PropTypes.shape({
		type: PropTypes.string,
		factory: PropTypes.func,
		props: PropTypes.shape({
			id: PropTypes.string,
			value: PropTypes.any,
		}),
		compact: PropTypes.oneOfType([
			PropTypes.func,
			PropTypes.shape({
				mode: PropTypes.string,
				factory: PropTypes.func,
				extraProps: PropTypes.object,
			}),
		]),
		debounceTimeout: PropTypes.number,
	});

	const propTypes = {
		style: PropTypes.object,
		ref: PropTypes.func,
		parentWidget: PropTypes.object,
		fieldFactory: PropTypes.shape({
			create: PropTypes.func,
		}),
		compactFieldFactory: PropTypes.shape({
			create: PropTypes.func,
		}),
		compactMode: PropTypes.string,
		compactOrder: PropTypes.arrayOf(PropTypes.string),
		hideCompactReadonly: PropTypes.bool,
		scrollableProvider: PropTypes.func,
		scrollToInvalidField: PropTypes.bool,
		scrollToAppearedField: PropTypes.bool,
		onChange: PropTypes.func,
		onBlur: PropTypes.func,
		onSubmit: PropTypes.func,
		onSubmitFailure: PropTypes.func,
		onFieldValidationFailure: PropTypes.func,
		renderBeforeFields: PropTypes.func,
		renderAfterFields: PropTypes.func,
		renderBeforeField: PropTypes.func,
		renderAfterField: PropTypes.func,
		renderAfterPrimaryFields: PropTypes.func,
		renderAfterCompactBar: PropTypes.func,
		renderBeforeCompactFields: PropTypes.func,
		renderAfterCompactFields: PropTypes.func,
		primaryFields: PropTypes.arrayOf(FieldSchemaPropType),
		secondaryFields: PropTypes.arrayOf(FieldSchemaPropType),
	};

	module.exports = { validateProps, defaultProps, propTypes };
});
