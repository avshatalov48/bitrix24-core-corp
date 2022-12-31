/**
 * @module layout/ui/entity-editor/control/combined/web
 */
jn.define('layout/ui/entity-editor/control/combined/web', (require, exports, module) => {

	const { isValidLink } = require('utils/url');
	const { EntityEditorCombinedBase } = require('layout/ui/entity-editor/control/combined/base');

	/**
	 * @class EntityEditorWeb
	 */
	class EntityEditorWeb extends EntityEditorCombinedBase
	{
		prepareValue(value)
		{
			const { VALUE, VALUE_TYPE } = value;

			return {
				...value,
				LINK: (
					isValidLink(VALUE)
						? VALUE
						: this.getLinkByType(VALUE_TYPE).replace(/#VALUE_URL#/i, VALUE)
				),
			};
		}
	}

	module.exports = { EntityEditorWeb };
});
