/**
 * @module bizproc/workflow/timeline/components
 * */

jn.define('bizproc/workflow/timeline/components', (require, exports, module) => {
	const { UserStub, ContentStub } = require('bizproc/workflow/timeline/components/stubs');
	const { Counter } = require('bizproc/workflow/timeline/components/counter');
	const { StepWrapper } = require('bizproc/workflow/timeline/components/step-wrapper');
	const { StepContent } = require('bizproc/workflow/timeline/components/step-content');
	const { StepsListCollapsed } = require('bizproc/workflow/timeline/components/steps-list-collapsed');

	module.exports = {
		StepWrapper,
		StepContent,
		Counter,
		UserStub,
		ContentStub,
		StepsListCollapsed,
	};
});
