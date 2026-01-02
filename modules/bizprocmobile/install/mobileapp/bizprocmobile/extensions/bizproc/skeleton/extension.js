/**
 * @module bizproc/skeleton
 */
jn.define('bizproc/skeleton', (require, exports, module) => {
	const { workflowDetailsSkeleton } = require('bizproc/skeleton/workflow-details');

	module.exports = {
		WorkflowDetailsSkeleton: workflowDetailsSkeleton,
	};
});
