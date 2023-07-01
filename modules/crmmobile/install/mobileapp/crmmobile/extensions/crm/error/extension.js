/**
 * @module crm/error
 */
jn.define('crm/error', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');

	/**
	 * @typedef {Object} BackendError
	 * @property {String} code
	 * @property {String} message
	 * @property {Object} customData
	 * @property {?Boolean} customData.public
	 */

	/**
	 * @public
	 * @param {Object} response
	 * @param {Number} entityTypeId
	 * @return {Promise}
	 */
	const handleErrors = (response, entityTypeId) => {
		const { error, errors = [] } = response;

		if (!error && errors.length === 0)
		{
			return Promise.resolve();
		}

		console.warn('crm/error: handling errors', response);

		return new Promise((resolve, reject) => {
			if (hasAccessDeniedError(errors))
			{
				showAccessDeniedAlert(entityTypeId, reject);
			}
			else if (hasNotFoundError(errors))
			{
				showNotFoundAlert(entityTypeId, reject);
			}
			else if (hasPublicError(errors))
			{
				showPublicAlert(errors, reject);
			}
			else
			{
				showInternalAlert(reject);
			}
		});
	};

	/**
	 * @public
	 * @param {BackendError[]} errors
	 * @return {Boolean}
	 */
	const hasAccessDeniedError = (errors) => {
		return errors.some(({ code }) => code === 'ACCESS_DENIED');
	};

	/**
	 * @param {Number} entityTypeId
	 * @param {?Function} confirmCallback
	 */
	const showAccessDeniedAlert = (entityTypeId, confirmCallback) => {
		const title = getEntityMessage('MCRM_ERROR_ACCESS_DENIED_TITLE', entityTypeId);
		const text = Loc.getMessage('MCRM_ERROR_ACCESS_DENIED_TEXT');

		showAlert(title, text, confirmCallback);
	};

	/**
	 * @public
	 * @param {BackendError[]} errors
	 * @return {Boolean}
	 */
	const hasNotFoundError = (errors) => {
		return errors.some(({ code }) => code === 'NOT_FOUND');
	};

	/**
	 * @param {Number} entityTypeId
	 * @param {?Function} confirmCallback
	 */
	const showNotFoundAlert = (entityTypeId, confirmCallback) => {
		showAlert(
			null,
			getEntityMessage('MCRM_ERROR_NOT_FOUND_TITLE', entityTypeId),
			confirmCallback,
		);
	};

	/**
	 * @public
	 * @param {BackendError[]} errors
	 * @return {Boolean}
	 */
	const hasPublicError = (errors) => errors.some(isPublicError);

	/**
	 * @public
	 * @param {BackendError} error
	 * @return {Boolean}
	 */
	const isPublicError = ({ customData, message }) => customData && customData.public && message;

	/**
	 * @param {BackendError[]} errors
	 * @param {Function} confirmCallback
	 */
	const showPublicAlert = (errors, confirmCallback) => {
		const publicError = errors.find(isPublicError);

		showAlert(
			Loc.getMessage('MCRM_ERROR_PUBLIC_ERROR_TITLE'),
			publicError.message,
			confirmCallback,
		);
	};

	/**
	 * @param {Function} confirmCallback
	 */
	const showInternalAlert = (confirmCallback) => {
		showAlert(
			Loc.getMessage('MCRM_ERROR_INTERNAL_ERROR_TITLE'),
			Loc.getMessage('MCRM_ERROR_INTERNAL_ERROR_TEXT'),
			confirmCallback,
		);
	};

	/**
	 * @param {?String} title
	 * @param {?String} text
	 * @param {?Function} confirmCallback
	 */
	const showAlert = (title = null, text = null, confirmCallback = () => {}) => {
		Alert.alert(
			title,
			text,
			confirmCallback,
			Loc.getMessage('MCRM_ERROR_ALERT_CONFIRM'),
		);
	};

	module.exports = {
		handleErrors,
		hasAccessDeniedError,
		showAccessDeniedAlert,
		hasNotFoundError,
		showNotFoundAlert,
		hasPublicError,
		isPublicError,
		showPublicAlert,
		showInternalAlert,
	};
});
