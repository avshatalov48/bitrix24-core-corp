/**
 * @module layout/ui/fields/entity-selector/theme/air
 */
jn.define('layout/ui/fields/entity-selector/theme/air', (require, exports, module) => {
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Content } = require('layout/ui/fields/entity-selector/theme/air/src/content');
	const { AirThemeEntity } = require('layout/ui/fields/entity-selector/theme/air/src/entity');

	/**
	 * @param  {EntitySelectorField} field - instance of the EntitySelectorFieldClass.
	 * @return {function} - functional component
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		Content((props) => new AirThemeEntity(props))({ field }),
	);

	/** @type {function(object): object} */
	const EntitySelectorField = withTheme(EntitySelectorFieldClass, AirTheme);

	module.exports = {
		Content,
		AirTheme,
		FieldWrapper,
		AirThemeEntity,
		EntitySelectorField,
	};
});
