/**
 * @module intranet/qualification/const
 */
jn.define('intranet/qualification/const', (require, exports, module) => {
	const FieldType = {
		BALLOON_ONE_SELECT: 'balloon-one-select',
		PHONE_INPUT: 'phone-number',
		USER_INVITATION: 'user-invitation',
	};

	const TriggerType = {
		STEP_ACTIVATE: 'step_activate',
		STEP_DEACTIVATE: 'step_deactivate',
		TOAST_SHOW: 'toast_show',
		PRESET_SET: 'preset_set',
		SAVE_VALUE: 'save_value',
	};

	module.exports = {
		FieldType,
		TriggerType,
	};
});
