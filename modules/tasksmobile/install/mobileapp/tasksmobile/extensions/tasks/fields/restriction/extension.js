/**
 * @module tasks/fields/restriction
 */
jn.define('tasks/fields/restriction', (require, exports, module) => {
	const { RestrictionType } = require('layout/ui/fields/base/restriction-type');
	const { getFeatureRestriction } = require('tariff-plan-restriction');
	const { TaskField, FeatureId } = require('tasks/enum');
	const { useCallback } = require('utils/function');

	/**
	 * @param {string} fieldId
	 * @return {RestrictionType}
	 */
	const getFieldRestrictionPolicy = (fieldId) => {
		const fieldRestrictionMap = {
			...Object.fromEntries(Object.values(TaskField).map((field) => [field, false])),
			[TaskField.FLOW]: getFeatureRestriction(FeatureId.FLOW).isRestricted(),
			[TaskField.PROJECT]: getFeatureRestriction('socialnetwork_projects_groups').isRestricted(),
			[TaskField.ACCOMPLICES]: getFeatureRestriction(FeatureId.ACCOMPLICE_AUDITOR).isRestricted(),
			[TaskField.AUDITORS]: getFeatureRestriction(FeatureId.ACCOMPLICE_AUDITOR).isRestricted(),
			[TaskField.CRM]: getFeatureRestriction(FeatureId.CRM).isRestricted(),
		};

		return (fieldRestrictionMap[fieldId] ? RestrictionType.FULL : RestrictionType.NONE);
	};

	const getFieldShowRestrictionCallback = (fieldId, parentWidget) => {
		const fieldFeatureMap = {
			[TaskField.FLOW]: FeatureId.FLOW,
			[TaskField.PROJECT]: 'socialnetwork_projects_groups',
			[TaskField.ACCOMPLICES]: FeatureId.ACCOMPLICE_AUDITOR,
			[TaskField.AUDITORS]: FeatureId.ACCOMPLICE_AUDITOR,
			[TaskField.CRM]: FeatureId.CRM,
		};

		return useCallback(
			() => getFeatureRestriction(fieldFeatureMap[fieldId]).showRestriction({ parentWidget }),
		);
	};

	const isFieldRestricted = (fieldId) => (getFieldRestrictionPolicy(fieldId) === RestrictionType.FULL);

	module.exports = {
		isFieldRestricted,
		getFieldRestrictionPolicy,
		getFieldShowRestrictionCallback,
	};
});
