/**
 * @module crm/entity-detail/component/right-buttons-provider/use-in-duplicates
 */
jn.define('crm/entity-detail/component/right-buttons-provider/use-in-duplicates', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @function useInDuplicates
	 */
	const useInDuplicates = (buttons, detailCard) => {
		const componentParams = detailCard.getComponentParams();
		const { useDuplicate, uid, rightButtonName } = componentParams;
		const isSaveButton = buttons.some(({ id }) => id === 'save-entity');

		if (!useDuplicate || !uid || !rightButtonName || isSaveButton)
		{
			return buttons;
		}

		const { entityTypeId, entityId } = componentParams;
		const customEventEmitter = EventEmitter.createWithUid(uid);

		return [
			{
				id: 'useDuplicate',
				name: rightButtonName,
				color: AppTheme.colors.accentMainLinks,
				callback: () => {
					customEventEmitter.emit('Duplicate::onUseContact', [entityId, entityTypeId]);
					detailCard.close();
				},
			},
		];
	};

	module.exports = { useInDuplicates };
});
