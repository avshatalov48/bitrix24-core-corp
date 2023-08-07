/**
 * @module crm/timeline/services/file-selector-configs
 */
jn.define('crm/timeline/services/file-selector-configs', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { mergeImmutable } = require('utils/object');

	const assertRequired = (options = {}, requiredKeys = []) => {
		requiredKeys.forEach((key) => {
			if (!options.hasOwnProperty(key))
			{
				throw new Error(`File selector configs: option '${key}' is required`);
			}
		});
	};

	const TodoActivityConfig = (options) => {
		assertRequired(options, ['entityTypeId', 'entityId', 'activityId']);

		const defaults = {
			focused: false,
			required: false,
			files: [],
			controller: {
				endpoint: 'crm.FileUploader.TodoActivityUploaderController',
				options: {
					entityTypeId: options.entityTypeId,
					entityId: options.entityId,
					activityId: options.activityId,
				},
			},
			onSave: (selector) => new Promise((resolve) => {
				const data = {
					ownerTypeId: options.entityTypeId,
					ownerId: options.entityId,
					id: options.activityId,
					fileTokens: selector.getFiles().map((file) => file.token || file.id),
				};

				BX.ajax.runAction('crm.activity.todo.updateFiles', { data })
					.then(() => successHandler(resolve))
					.catch((err) => errorHandler(err, resolve));
			}),
		};

		return mergeImmutable(defaults, options);
	};

	const CommentConfig = (options) => {
		assertRequired(options, ['entityTypeId', 'entityId']);

		const defaults = {
			focused: false,
			required: false,
			files: [],
			controller: {
				endpoint: 'crm.FileUploader.CommentUploaderController',
				options: {
					entityTypeId: options.entityTypeId,
					entityId: options.entityId,
				},
			},
			onSave: (selector) => new Promise((resolve) => {
				const data = {
					ownerTypeId: options.entityTypeId,
					ownerId: options.entityId,
					id: options.id,
					files: selector.getFiles().map((file) => file.token || file.id),
				};

				BX.ajax.runAction('crm.timeline.comment.updateFiles', { data })
					.then(() => successHandler(resolve))
					.catch((err) => errorHandler(err, resolve));
			}),
		};

		return mergeImmutable(defaults, options);
	};

	const successHandler = (callback) => {
		Haptics.notifySuccess();
		callback();
	};

	const errorHandler = (error, callback) => {
		console.error(error);

		Alert.alert(
			Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_TITLE'),
			Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_DESCRIPTION'),
			callback,
		);
	};

	module.exports = { TodoActivityConfig, CommentConfig };
});
