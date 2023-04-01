/**
 * @module layout/ui/entity-editor/control/combined
 */
jn.define('layout/ui/entity-editor/control/combined', (require, exports, module) => {

	const { FieldFactory, ImType, WebType, PhoneType } = require('layout/ui/fields');
	const { get } = require('utils/object');
	const { EntityEditorIm } = require('layout/ui/entity-editor/control/combined/im');
	const { EntityEditorWeb } = require('layout/ui/entity-editor/control/combined/web');
	const { EntityEditorPhone } = require('layout/ui/entity-editor/control/combined/phone');
	const { EntityEditorCombinedBase } = require('layout/ui/entity-editor/control/combined/base');

	const combinedControls = {
		[ImType]: EntityEditorIm,
		[WebType]: EntityEditorWeb,
		[PhoneType]: EntityEditorPhone,
	};

	/**
	 * @function EntityEditorCombined
	 */
	function EntityEditorCombined(props)
	{
		const schemeType = get(props, ['settings', 'schemeElement', 'data', 'type'], '').toLowerCase();
		const { type } = props;

		if (combinedControls.hasOwnProperty(schemeType))
		{
			const combinedControl = combinedControls[schemeType];

			return new combinedControl(props);
		}

		if (FieldFactory.has(type))
		{
			return new EntityEditorCombinedBase(props);
		}

		return null;
	}

	module.exports = { EntityEditorCombined };
});