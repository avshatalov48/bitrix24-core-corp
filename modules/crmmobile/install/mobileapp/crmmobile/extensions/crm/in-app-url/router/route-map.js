/**
 * @module crm/in-app-url/router/route-map
 */
jn.define('crm/in-app-url/router/route-map', (require, exports, module) => {

	const { TypeName } = require('crm/type');

	/**
	 * @class routeMap
	 */
	const routeMap = {
		[TypeName.Deal]: {
			mobileEvents: {
				onCRMDealView: {
					name: 'detail',
					params: 'deal_id',
				},
				onCRMDealList: {
					name: 'list'
				},
			},
			detail: {
				name: 'crm:deal',
				pattern: '/crm/deal/details/:id/',
				params: 'id',
			},
			list: {
				name: 'crm:dealList',
				pattern: '/crm/deal/',
			}
		},
		[TypeName.Contact]: {
			mobileEvents: {
				onCRMContactView: {
					name: 'detail',
					params: 'contact_id'
				},
				onCRMContactList: {
					name: 'list'
				},
			},
			detail: {
				name: 'crm:contact',
				pattern: '/crm/contact/details/:id/',
				params: 'id',
			},
			list: {
				name: 'crm:contactList',
				pattern: '/crm/contact/',
			}
		},
		[TypeName.Company]: {
			mobileEvents: {
				onCRMCompanyView: {
					name: 'detail',
					params: 'company_id'
				},
				onCRMCompanyList: {
					name: 'list'
				},
			},
			detail: {
				name: 'crm:company',
				pattern: '/crm/company/details/:id/',
				params: 'id',
			},
			list: {
				name: 'crm:companyList',
				pattern: '/crm/company/',
			}
		},
		user: {
			detail: {
				name: 'crm:user',
				pattern: '/company/personal/user/:userId/',
				params: 'user_id',
			},
		},
	};

	module.exports = { routeMap };
});
