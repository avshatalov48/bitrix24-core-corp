/**
 * @module bizproc/task/tasks-performer/informers
 */
jn.define('bizproc/task/tasks-performer/informers', (require, exports, module) => {
	const { SimilarTasksInformer } = require('bizproc/task/tasks-performer/informers/similar-tasks-informer');
	const { TaskListInformer } = require('bizproc/task/tasks-performer/informers/task-list-informer');

	module.exports = { SimilarTasksInformer, TaskListInformer };
});
