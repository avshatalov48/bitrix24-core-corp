/**
 * @module crm/entity-actions/copy-entity
 */
jn.define('crm/entity-actions/copy-entity', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('crm/type');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @function getActionToCopyEntity
	 * @param {Number} entityTypeId
	 * @returns {Object}
	 */
	const getActionToCopyEntity = (entityTypeId) => {
		const id = 'copyEntity';

		const title = Loc.getMessage('M_CRM_ENTITY_ACTION_COPY_ENTITY_MSGVER_1');

		const svgIcon = '<svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.70938 4.06836C7.59672 4.06836 6.69798 4.97645 6.70949 6.08904L6.71041 6.1788H16.7298C17.8344 6.1788 18.7298 7.07423 18.7298 8.1788V18.4835H19.5188C20.6315 18.4835 21.5302 17.5754 21.5187 16.4628L21.411 6.04768C21.3996 4.95123 20.5076 4.06836 19.4111 4.06836H8.70938ZM2.03503 10.4475C2.03503 9.34292 2.93046 8.44749 4.03503 8.44749H14.4645C15.5691 8.44749 16.4645 9.34292 16.4645 10.4475V20.8769C16.4645 21.9815 15.5691 22.8769 14.4645 22.8769H4.03503C2.93047 22.8769 2.03503 21.9815 2.03503 20.8769V10.4475ZM13.9671 10.9449H4.53244V20.3795H13.9671V10.9449Z" fill="#6a737f"/></svg>';

		const iconUrl = '/bitrix/mobileapp/crmmobile/extensions/crm/entity-actions/images/copy.png';

		/**
		 * @param {Object} params
		 * @param {Number} params.entityId
		 * @param {?Number} params.categoryId
		 * @returns {Promise}
		 */
		const onAction = async ({ entityId, categoryId = null, analytics = {} }) => {
			if (!Type.existsById(entityTypeId) || !Number.isInteger(Number(entityId)))
			{
				return null;
			}

			const event = new AnalyticsEvent({
				...BX.componentParameters.get('analytics', {}),
				...analytics,
			}).setEvent('entity_copy_open');
			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

			return EntityDetailOpener.open({
				payload: {
					entityTypeId,
					entityId,
					categoryId,
					copy: true,
				},
				analytics: event,
			});
		};

		return { id, title, svgIcon, iconUrl, onAction };
	};

	module.exports = { getActionToCopyEntity };
});
