/**
 * @bxjs_lang_path extension.php
 */

/**
 * @module crm/entity-detail/component/ajax-error-handler
 */
jn.define('crm/entity-detail/component/ajax-error-handler', (require, exports, module) => {
	const { Alert } = require('alert');
	const { NotifyManager } = require('notify-manager');
	const { getEntityMessage } = require('crm/loc');
	const { RequiredFields } = require('crm/required-fields');

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {Object} response
	 * @param {Object} payload
	 */
	const ajaxErrorHandler = (detailCard, response, payload) => {
		const { error, errors = [] } = response;

		if (!error && errors.length === 0)
		{
			return Promise.resolve(response);
		}

		console.warn('Detail card: handle ajax errors', response);

		const hasAccessDeniedError = (errorsToCheck) => {
			return errorsToCheck.some(({ code }) => code === 'ACCESS_DENIED');
		};

		const hasNotFoundError = (errorsToCheck) => {
			return errorsToCheck.some(({ code }) => code === 'NOT_FOUND');
		};

		const hasPublicError = (errorsToCheck) => {
			return errorsToCheck.some(({ customData, message }) => customData && customData.public && message);
		};

		const showAlertAndCloseDetailCard = (title = null, text = null) => {
			const handleDeniedConfirm = () => {
				detailCard.emitEntityUpdate();
				detailCard.close();
			};

			Alert.alert(title, text, handleDeniedConfirm, BX.message('M_CRM_ENTITY_ALERT_CONFIRM'));
		};

		const showAccessDeniedAlert = () => {
			const { entityTypeId } = detailCard.getComponentParams();

			const title = getEntityMessage('M_CRM_ENTITY_ACCESS_DENIED_TITLE', entityTypeId);
			const text = BX.message('M_CRM_ENTITY_ACCESS_DENIED_TEXT');

			showAlertAndCloseDetailCard(title, text);
		};

		const showNotFoundAlert = () => {
			const { entityTypeId } = detailCard.getComponentParams();

			showAlertAndCloseDetailCard(null, getEntityMessage('M_CRM_ENTITY_NOT_FOUND_TITLE', entityTypeId));
		};

		const showPublicAlert = (errorsToShow, reject) => {
			const showedError = errorsToShow.find(({ customData, message }) => customData && customData.public && message);

			Alert.alert(
				BX.message('M_CRM_ENTITY_ERROR_ON_SAVE'),
				showedError.message,
				() => reject([]),
				BX.message('M_CRM_ENTITY_ALERT_CONFIRM'),
			);
		};

		const showDefaultAlert = (reject) => {
			Alert.alert(
				BX.message('M_CRM_ENTITY_ERROR_ON_SAVE_INTERNAL'),
				BX.message('M_CRM_ENTITY_ERROR_ON_SAVE_INTERNAL_TEXT'),
				() => reject([]),
				BX.message('M_CRM_ENTITY_ALERT_CONFIRM'),
			);
		};

		return new Promise((resolve, reject) => {
			if (hasAccessDeniedError(errors))
			{
				showAccessDeniedAlert();
				detailCard.customEventEmitter.emit('DetailCard::onAccessDenied', [detailCard.getComponentParams()]);
				reject([]);
			}
			else if (hasNotFoundError(errors))
			{
				showNotFoundAlert();
				reject([]);
			}
			else if (RequiredFields.hasRequiredFields(errors))
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				RequiredFields.show({
					action: 'save',
					errors,
					params: detailCard.getComponentParams(),
					onSave: ({ value }) => {
						NotifyManager.showLoadingIndicator();
						detailCard.runSave(payload, value, false)
							.then(resolve)
							.catch(reject);
					},
					// some timeout to prevent strange app crash
					onCancel: () => setTimeout(() => reject([]), 50),
				});
			}
			else if (hasPublicError(errors))
			{
				showPublicAlert(errors, reject);
			}
			else
			{
				showDefaultAlert(reject);
			}
		});
	};

	module.exports = { ajaxErrorHandler };
});
