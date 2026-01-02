/**
 * @module im/messenger/controller/sidebar-v2/services/participants-service
 */
jn.define('im/messenger/controller/sidebar-v2/services/participants-service', (require, exports, module) => {
	const { getLogger } = require('im/messenger/lib/logger');
	const { RestMethod, UserRole } = require('im/messenger/const');
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');

	const logger = getLogger('sidebar--participants-service');

	const LIMIT = 50;

	/**
	 * @class ParticipantsService
	 */
	class ParticipantsService extends SidebarDataProvider
	{
		getInitialQueryHandler()
		{
			return (response) => this.#handlePage(response.data());
		}

		getInitialQueryMethod()
		{
			return RestMethod.imV2ChatMemberTail;
		}

		getInitialQueryParams()
		{
			const dialogModel = this.getDialogModel();
			const queryParams = {
				limit: LIMIT,
				dialogId: dialogModel.dialogId,
			};

			if (dialogModel.lastLoadParticipantId)
			{
				const relationId = dialogModel.lastLoadParticipantId;
				const role = this.#getRelationUserRole(relationId);

				return {
					...queryParams,
					cursor: {
						relationId,
						role,
					},
				};
			}

			return queryParams;
		}

		loadPage(offset = 0)
		{
			const dialogModel = this.getDialogModel();

			if (!dialogModel)
			{
				return new Promise().resolve(false);
			}

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					this.getInitialQueryMethod(),
					this.getInitialQueryParams(),
				).then((response) => {
					if (response.error() || response.status !== 200)
					{
						logger.error('getParticipantList.error', response.error(), response.ex);
						reject(response.error());

						return;
					}

					this.#handlePage(response.data());

					resolve(response);
				}).catch((error) => {
					this.logger.error('loadPage', error);

					reject(error);
				});
			});
		}

		#handlePage(data)
		{
			logger.info('SidebarServices.getParticipantList:', data);
			const { users, nextCursor } = data;

			const mutations = [];

			if (Array.isArray(users) && users.length > 0)
			{
				const participants = users
					.map(({ id }) => id)
					.filter((id) => id > 0);

				mutations.push(
					this.store.dispatch('usersModel/merge', users),
					this.store.dispatch('dialoguesModel/addParticipants', {
						participants,
						dialogId: this.getDialogId(),
						lastLoadParticipantId: nextCursor && users[users.length - 1].id,
						hasNextPage: participants?.length === LIMIT,
					}),
				);
			}

			return Promise.all(mutations);
		}

		getDialogId()
		{
			const { dialogId } = this.props;

			return dialogId;
		}

		getDialogModel()
		{
			return this.store.getters['dialoguesModel/getById'](this.getDialogId());
		}

		/**
		 * @return {UserRole}
		 */
		#getRelationUserRole(relationId)
		{
			const dialogModel = this.getDialogModel();

			if (Number(dialogModel.owner) === Number(relationId))
			{
				return UserRole.owner;
			}

			if (dialogModel.managerList.includes(relationId))
			{
				return UserRole.manager;
			}

			return UserRole.member;
		}
	}

	module.exports = {
		ParticipantsService,
	};
});
