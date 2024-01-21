/**
 * @module bizproc/workflow/timeline/components/stubs
 * */

jn.define('bizproc/workflow/timeline/components/stubs', (require, exports, module) => {
	const { ContentStub } = require('bizproc/workflow/timeline/components/stubs/content-stub');
	const { UserStub } = require('bizproc/workflow/timeline/components/stubs/user-stub');

	module.exports = {
		ContentStub,
		UserStub,
	};
});
