/**
 * @module layout/ui/entity-editor/control/combined/im
 */
jn.define('layout/ui/entity-editor/control/combined/im', (require, exports, module) => {

	const { isOpenLine } = require('communication/connection');
	const { EntityEditorCombinedBase } = require('layout/ui/entity-editor/control/combined/base');

	/**
	 * @class EntityEditorIm
	 */
	class EntityEditorIm extends EntityEditorCombinedBase
	{
		prepareValue(value)
		{
			const { VALUE, VALUE_TYPE } = value;
			let link;

			if (isOpenLine(VALUE))
			{
				link = VALUE;
			}
			else
			{
				link = this.getLinkByType(VALUE_TYPE).replace(/#VALUE_URL#/i, VALUE);
			}

			return {
				...value,
				LINK: link,
			};
		}
	}

	module.exports = { EntityEditorIm };
});
