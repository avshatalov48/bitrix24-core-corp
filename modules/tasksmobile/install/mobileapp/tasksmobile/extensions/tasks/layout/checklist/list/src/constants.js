/**
 * @module tasks/layout/checklist/list/src/constants
 */
jn.define('tasks/layout/checklist/list/src/constants', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction } = require('tariff-plan-restriction');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/checklist/list/`;

	const directions = {
		LEFT: 'left',
		RIGHT: 'right',
	};

	const CHECKBOX_SIZE = 24;
	const MEMBER_TYPE = {
		accomplice: 'accomplice',
		auditor: 'auditor',
	};

	const MEMBER_TYPE_ICONS = {
		[MEMBER_TYPE.auditor]: Icon.OBSERVER,
		[MEMBER_TYPE.accomplice]: Icon.GROUP,
	};

	const MEMBER_TYPE_RESTRICTION_FEATURE_META = {
		[MEMBER_TYPE.accomplice]: getFeatureRestriction(FeatureId.ACCOMPLICE_AUDITOR),
		[MEMBER_TYPE.auditor]: getFeatureRestriction(FeatureId.ACCOMPLICE_AUDITOR),
	};

	module.exports = {
		pathToExtension,
		directions,
		CHECKBOX_SIZE,
		MEMBER_TYPE,
		MEMBER_TYPE_ICONS,
		MEMBER_TYPE_RESTRICTION_FEATURE_META,
	};
});
