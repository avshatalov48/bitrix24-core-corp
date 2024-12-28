/**
 * @module error
 */
jn.define('error', (require, exports, module) => {
	const { Alert, ButtonType } = require('alert');
	const { Loc } = require('loc');

	/**
     * @typedef {Object} BackendError
     * @property {String} code
     * @property {String} message
     * @property {Object} customData
     * @property {?Boolean} customData.public
     */

	/**
     * @param {?String} title
     * @param {?String} text
     * @return {Promise}
     */
	const showAlert = (title = null, text = null) => {
		return new Promise((resolve) => {
			Alert.confirm(
				title,
				text,
				[{
					type: ButtonType.DEFAULT,
					onPress: resolve,
				}],
			);
		});
	};

	/**
     * @return {Promise}
     */
	const showInternalAlert = () => {
		return showAlert(
			Loc.getMessage('ERROR_INTERNAL_ERROR_TITLE'),
			Loc.getMessage('ERROR_INTERNAL_ERROR_TEXT'),
		);
	};

	/**
     * @public
     * @param {BackendError} error
     * @return {Boolean}
     */
	const isPublicError = ({ customData, message }) => customData && customData.public && message;

	/**
     * @public
     * @param {BackendError[]} errors
     * @return {Boolean}
     */
	const hasPublicError = (errors) => errors.some((error) => isPublicError(error));

	/**
     * @param {BackendError[]} errors
     * @return {Promise}
     */
	const showPublicAlert = (errors) => {
		const publicError = errors.find((error) => isPublicError(error));

		return showAlert(
			Loc.getMessage('ERROR_PUBLIC_ERROR_TITLE'),
			publicError.message,
		);
	};

	/**
	 * Displays an alert with the message of the first error in the provided array of errors.
	 *
	 * @param {Array<{ message: string }>} errors - An array of error objects, each containing a message property.
	 * @returns {Promise} - A promise that resolves when the alert is dismissed.
	 */
	const showFirstErrorAlert = (errors) => {
		const firstError = errors[0];

		return showAlert(
			Loc.getMessage('ERROR_PUBLIC_ERROR_TITLE'),
			firstError.message,
		);
	};

	/**
	 * Handles AJAX errors by displaying an appropriate alert based on the error type.
	 * Use handler with mobile/lib/Trait/PublicErrorsTrait.php
	 *
	 * @param {Object} response - The response object containing errors.
	 * @param {boolean} [logErrorsInConsole=true] - Whether to log errors in the console.
	 * @returns {Promise<Object>} - A promise that resolves with the response object.
	 */
	const ajaxPublicErrorHandler = async (response, logErrorsInConsole = true) => {
		const { errors = [] } = response;

		const hasErrors = errors && errors.length > 0;
		if (!hasErrors)
		{
			return response;
		}

		if (logErrorsInConsole)
		{
			console.error(errors);
		}

		if (hasPublicError(errors))
		{
			await showPublicAlert(errors);
		}
		else
		{
			await showInternalAlert();
		}

		return response;
	};

	/**
	 * Handles AJAX errors by displaying an alert with the message of the first error in the provided response.
	 *
	 * @param {Object} response - The response object containing errors.
	 * @param {boolean} [logErrorsInConsole=true] - Whether to log errors in the console.
	 * @returns {Promise<Object>} - A promise that resolves with the response object.
	 */
	const ajaxAlertErrorHandler = async (response, logErrorsInConsole = true) => {
		const { errors = [] } = response;

		const hasErrors = errors && errors.length > 0;
		if (!hasErrors)
		{
			return response;
		}

		if (logErrorsInConsole)
		{
			console.error(errors);
		}

		await showFirstErrorAlert(errors);

		return response;
	};

	module.exports = {
		showAlert,
		showInternalAlert,
		isPublicError,
		hasPublicError,
		showPublicAlert,
		showFirstErrorAlert,
		ajaxPublicErrorHandler,
		ajaxAlertErrorHandler,
	};
});
