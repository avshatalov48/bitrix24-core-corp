/**
 * @module crm/in-app-url/routes
 */
jn.define('crm/in-app-url/routes', (require, exports, module) => {

	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { TypeId, TypeName } = require('crm/type');

	const openCrmEntity = (
		entityTypeId,
		entityId,
		{ linkText = '', canOpenInDefault, ...restPayload } = {}) => {

		const extensionData = jnExtensionData.get('crm:in-app-url/routes');

		if (typeof extensionData === 'undefined' || !extensionData.isUniversalActivityScenarioEnabled)
		{
			return;
		}

		EntityDetailOpener.open(
			{ entityId, entityTypeId, ...restPayload },
			{
				titleParams: { text: linkText },
			},
			null,
			canOpenInDefault,
		);
	};

	const openCrm = ({ activeTab }) => {
		const componentParams = {};

		if (activeTab === TypeName.Company || activeTab === TypeName.Contact)
		{
			componentParams.activeTabName = activeTab;
		}

		ComponentHelper.openLayout(
			{
				widgetParams: {
					titleParams: {
						text: 'CRM',
					},
				},
				name: 'crm:crm.tabs',
				canOpenInDefault: true,
				componentParams,
			},
		);
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = function(inAppUrl) {
		inAppUrl.register('/crm/deal/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Deal, id, context);

		}).name('crm:deal');

		inAppUrl.register('/crm/deal/', () => {
			openCrm({
				activeTab: TypeName.Deal,
			});
		}).name('crm:dealList');

		inAppUrl.register('/crm/lead/details/:id/', ({ id }) => {
			PageManager.openPage({
				url: `/mobile/crm/lead/?page=view&lead_id=${id}`,
			});
		}).name('crm:lead');

		inAppUrl.register('/crm/lead/', ({ id }) => {
			PageManager.openPage({
				url: `/mobile/crm/lead/`,
			});
		}).name('crm:leadList');

		inAppUrl.register('/crm/contact/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Contact, id, context);

		}).name('crm:contact');

		inAppUrl.register('/crm/contact/list/', () => {
			openCrm({
				activeTab: TypeName.Contact,
			});
		}).name('crm:contactList');

		inAppUrl.register('/crm/contact/', () => {
			openCrm({
				activeTab: TypeName.Contact,
			});
		}).name('crm:contactList');

		inAppUrl.register('/crm/company/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Company, id, context);

		}).name('crm:company');

		inAppUrl.register('/crm/company/list/', () => {
			openCrm({
				activeTab: TypeName.Company,
			});
		}).name('crm:companyList');

		inAppUrl.register('/crm/company/', () => {
			openCrm({
				activeTab: TypeName.Company,
			});
		}).name('crm:companyList');

	};

});