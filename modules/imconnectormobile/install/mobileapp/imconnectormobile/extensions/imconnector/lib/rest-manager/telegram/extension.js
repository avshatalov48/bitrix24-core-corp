/**
 * @module imconnector/lib/rest-manager/telegram
 */
jn.define('imconnector/lib/rest-manager/telegram', (require, exports, module) => {
	/**
	 * @class TelegramRestManager
	 */
	class TelegramRestManager
	{
		constructor()
		{
			this.conncetorId = 'telegrambot';
		}

		/**
		 * @param {boolean} withConnector
		 * @return {Promise<TelegramOpenLine[]>}
		 */
		getLineList(withConnector = true)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					'imconnector.Openlines.list',
					{
						withConnector,
						connectorId: this.conncetorId,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							return;
						}

						resolve(result.data());
					},
				);
			});
		}

		/**
		 * @param {number} lineId
		 * @return {Promise<TelegramSettings>}
		 */
		getSettings(lineId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					'imconnector.Openlines.get',
					{
						lineId,
						connectorId: this.conncetorId,
						withQr: true,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							return;
						}

						resolve(result.data());
					},
				);
			});
		}

		/**
		 * @param {number} lineId
		 * @return {Promise<boolean>}
		 */
		disableConnector(lineId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					'imconnector.Openlines.delete',
					{
						lineId,
						connectorId: this.conncetorId,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							return;
						}

						resolve(result.data());
					},
				);
			});
		}

		/**
		 * @param {number} lineId
		 * @param {number[]} userIds
		 * @return {*}
		 */
		setUsers(lineId, userIds)
		{
			return BX.rest.callMethod(
				'imconnector.Openlines.setUsers',
				{
					connectorId: this.conncetorId,
					lineId,
					userIds,
				},
			);
		}

		/**
		 * @param {number[]} userIdList
		 * @return {Promise<TelegramUserData[]>}
		 */
		getUsersData(userIdList)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					'im.user.list.get',
					{
						ID: userIdList,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							return;
						}

						const data = Object.values(result.data()).map((user) => {
							return {
								id: user.id,
								name: user.name,
								icon: user.avatar,
								workPosition: user.work_position,
							};
						});

						resolve(data);
					},
				);
			});
		}

		/**
		 * @param {string} token
		 * @param {?TelegramOpenLine} line
		 * @return {Promise<TelegramSettings>}
		 */
		registry(token, line)
		{
			const params = {
				connectorId: this.conncetorId,
				botToken: token,
				withQr: true,
			};
			if (line)
			{
				params.lineId = line.lineId;
			}

			return new Promise((resolve, reject) => {
				BX.rest.callBatch(
					{
						registry: {
							method: 'imconnector.Openlines.create',
							params,
						},
						getUsers: {
							method: 'im.user.list.get',
							params: {
								ID: '$result[registry][userIds]',
							},
						},
					},
					(result) => {
						if (result.registry.error())
						{
							console.log(result.registry.error());
							reject(result.registry.error());
							return;
						}

						const data = result.registry.data();
						const userData = result.getUsers.data();

						data.users = Object.values(userData).map((user) => {
							return {
								id: user.id,
								name: user.name,
								icon: user.avatar,
								workPosition: user.work_position,
							};
						});

						if (line)
						{
							data.canEditLine = line.canEditLine;
							data.canEditConnector = line.canEditConnector;
						}
						else
						{
							data.canEditLine = false;
							data.canEditConnector = true;
						}

						resolve(data);
					},
				);
			});
		}

		/**
		 * @param {number} userId
		 * @return {Promise<boolean>}
		 */
		hasAccess(userId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					'imconnector.openlines.hasAccess',
					{
						userId,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							return;
						}

						resolve(result.data());
					},
				);
			});
		}

		/**
		 *
		 * @param userId
		 * @return {Promise<{permissions: TelegramPermissions, freeLines: TelegramOpenLine[]}>}
		 */
		getRegistryParams(userId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callBatch(
					{
						freeLines: {
							method: 'imconnector.Openlines.list',
							params: {
								connectorId: this.conncetorId,
								withConnector: false,
							},
						},
						permissions: {
							method: 'imconnector.openlines.hasAccess',
							params: {
								userId,
							},
						},
					},
					(result) => {
						if (result.freeLines.error())
						{
							reject(result.freeLines.error());
							return;
						}

						if (result.permissions.error())
						{
							reject(result.permissions.error());
							return;
						}
						const data = {
							freeLines: result.freeLines.data(),
							permissions: result.permissions.data(),
						};

						resolve(data);
					},
				);
			});
		}
	}

	module.exports = { TelegramRestManager };
});
