/**
 * @module im/messenger/controller/sidebar-v2/services/user-department-service
 */
jn.define('im/messenger/controller/sidebar-v2/services/user-department-service', (require, exports, module) => {
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');
	const { RestMethod } = require('im/messenger/const/rest');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('user-department-service');

	class SidebarUserDepartmentService extends SidebarDataProvider
	{
		constructor(userId)
		{
			super();
			this.userId = userId;
		}

		getInitialQueryMethod()
		{
			return RestMethod.imUserGetDepartment;
		}

		getInitialQueryParams()
		{
			return {
				id: this.userId,
			};
		}

		getInitialQueryHandler()
		{
			return (response) => this.#handle(response.data());
		}

		#handle(data)
		{
			if (!data || !data.name)
			{
				logger.error('Failed to get department name for user', this.userId);
			}

			this.store.dispatch('usersModel/update', [{ id: this.userId, departmentName: data?.name || null }]);

			return data?.name;
		}

		loadPage(offset)
		{
			return Promise.resolve();
		}
	}

	module.exports = { SidebarUserDepartmentService };
});
