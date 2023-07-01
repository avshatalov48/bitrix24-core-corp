/**
 * @module layout/ui/detail-card/action/check-for-changes
 */
jn.define('layout/ui/detail-card/action/check-for-changes', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');

	/**
	 * @function CheckForChanges
	 * @param {DetailCardComponent} detailCard
	 */
	const checkForChanges = (detailCard) => {
		return new Promise((resolve, reject) => {
			if (detailCard.isChanged)
			{
				showConfirmSaveEntity(detailCard, resolve, reject);
			}
			else
			{
				resolve();
			}
		});
	};

	const showConfirmSaveEntity = (detailCard, resolve, reject) => {
		Haptics.impactLight();

		Alert.confirm(
			Loc.getMessage('M_DC_ACTION_SAVE_ENTITY_ALERT_TITLE'),
			Loc.getMessage('M_DC_ACTION_SAVE_ENTITY_ALERT_TEXT2'),
			[
				{
					text: Loc.getMessage('M_DC_ACTION_SAVE_ENTITY_ALERT_SAVE'),
					type: 'default',
					onPress: () => onSave(detailCard, resolve, reject),
				},
				{
					text: Loc.getMessage('M_DC_ACTION_SAVE_ENTITY_ALERT_CANCEL'),
					type: 'cancel',
					onPress: reject,
				},
			],
		);
	};

	const onSave = (detailCard, resolve, reject) => {
		const delayForSuccessLoaderAnimation = 300;

		detailCard.handleSave()
			.then(() => setTimeout(resolve, delayForSuccessLoaderAnimation))
			.catch(reject);
	};

	module.exports = { checkForChanges };
});
