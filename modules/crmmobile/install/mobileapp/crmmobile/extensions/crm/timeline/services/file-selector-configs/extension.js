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
					.then(() => {
						Haptics.notifySuccess();
						resolve();
					})
					.catch((err) => {
						console.error(err);
						Alert.alert(
							Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_TITLE'),
							Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_DESCRIPTION'),
							resolve,
						);
					});
			}),
		};

		return mergeImmutable(defaults, options);
	};

	module.exports = { TodoActivityConfig };
});
