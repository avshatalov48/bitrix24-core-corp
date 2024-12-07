export default {
	async saveRolePermission({ commit, getters }, action: string) {
		if (getters.setSaveInProgress)
		{
			return;
		}
		commit('setSaveInProgress', true);
		commit('setLastErrorMessage', '');

		try
		{
			const response = await BX.ajax.runComponentAction(
				'bitrix:crm.config.perms.role.edit.v2',
				'save',
				{
					mode: 'class',
					json: {
						values: getters.getSaveData,
					},
				},
			);

			const redirectUrl = response?.data?.redirectUrl;
			const roleUrl = response?.data?.roleUrl;

			if (action === 'save' && redirectUrl)
			{
				window.location.href = redirectUrl;

				return;
			}

			if (action === 'apply' && roleUrl && getters.getRoleId === 0)
			{
				window.location.href = roleUrl;

				return;
			}

			commit('resetTouchedAttributes');
			commit('resetTouchedTransitions');
		}
		catch (err)
		{
			const errMessage = err?.data?.message;
			commit('setLastErrorMessage', errMessage);
		}
		finally
		{
			commit('setSaveInProgress', false);
		}
	},

	async deleteRole({ commit, getters}){
		if (getters.setSaveInProgress)
		{
			return;
		}

		const roleId = getters.getRoleId;

		if (!roleId)
		{
			return;
		}

		commit('setSaveInProgress', true);
		commit('setLastErrorMessage', '');

		try
		{
			const response = await BX.ajax.runComponentAction(
				'bitrix:crm.config.perms.role.edit.v2',
				'delete',
				{
					mode: 'class',
					json: {
						values: { roleId },
					},
				},
			);

			let redirectUrl = response?.data?.redirectUrl;

			if (!redirectUrl)
			{
				redirectUrl = '/';
			}

			window.location.href = redirectUrl;
		}
		catch (err)
		{
			const errMessage = err?.data?.message;
			commit('setLastErrorMessage', errMessage);
		}
		finally
		{
			commit('setSaveInProgress', false);
		}
	}
};
