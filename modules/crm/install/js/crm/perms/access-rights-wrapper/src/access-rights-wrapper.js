import { AccessRights } from 'ui.accessrights';

export class AccessRightsWrapper
{
	accessRightsInstance;

	draw(userGroups, accessRights, renderTo)
	{
		this.accessRightsInstance = new AccessRights({
			component: 'bitrix:crm.config.perms.v2',
			actionSave: 'save',
			actionDelete: 'delete',
			renderTo,
			userGroups,
			accessRights,
			isSaveOnlyChangedRights: true,
			useEntitySelectorDialogAsPopup: true,
			entitySelectorDialogOptions: {
				options: this.#dialogOptions(),
				entitiesIdsEncoder: this.#entitiesIdsEncoder(),
				entitiesIdsDecoder: this.#entitiesIdsDecoder(),
				normalizeType: this.#normalizeType(),
			},
		});

		this.accessRightsInstance.draw();
	}

	sendActionRequest()
	{
		if (this.accessRightsInstance)
		{
			this.accessRightsInstance.sendActionRequest();
		}
	}

	fireEventReset()
	{
		if (this.accessRightsInstance)
		{
			this.accessRightsInstance.fireEventReset();
		}
	}

	#dialogOptions()
	{
		return {
			enableSearch: true,
			context: 'CRM_PERMS',
			entities: [
				{
					id: 'user',
					options: {
						intranetUsersOnly: true,
						emailUsers: false,
						inviteEmployeeLink: false,
						inviteGuestLink: false,
					},
				},
				{
					id: 'department',
					options: {
						selectMode: 'usersAndDepartments',
						allowSelectRootDepartment: true,
						allowFlatDepartments: true,
					},
				},
				{
					id: 'meta-user',
					options: { 'all-users': true }
				},
				{
					id: 'projectmembers',
					dynamicLoad: true,
					options: {
						addProjectMembersCategories: true,
					},
					itemOptions: {
						default: {
							link: '',
							linkTitle: '',
						},
					},
				},
				{
					id: 'site_groups',
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
		};
	}

	#entitiesIdsEncoder()
	{
		return (code) => {
			if (/^U(\d+)$/.test(code))
			{
				const match = code.match(/^U(\d+)$/) || null;
				const userId = match ? match[1] : null;

				return { entityName: 'user', id: userId };
			}
			else if (/^DR(\d+)$/.test(code))
			{
				const match = code.match(/^DR(\d+)$/) || null;
				const departmentId = match ? match[1] : null;

				return { entityName: 'department', id: `${departmentId}:F` };
			}
			else if (/^D(\d+)$/.test(code))
			{
				const match = code.match(/^D(\d+)$/) || null;
				const departmentId = match ? match[1] : null;

				return { entityName: 'department', id: departmentId };
			}
			else if (/^G(\d+)$/.test(code))
			{
				return { entityName: 'site_groups', id: code };
			}
			else if (/^SG(\d+)_([AEK])$/.test(code))
			{
				const match = code.match(/^SG(\d+)_([AEK])$/) || null;

				const projectId = match ? match[1] : null;
				const postfix = match ? match[2] : null;

				return { entityName: 'project', id: `${projectId}:${postfix}` }
			}

			return { entityName: 'unknown', id: code };
		};
	}

	#entitiesIdsDecoder()
	{
		return (item) => {
			const entityId = item.entityId;

			let code = '';

			switch (entityId)
			{
				case 'user':
					code = `U${item.id}`;
					break;
				case 'department':
					if (/:F$/.test(item.id))
					{
						const match = item.id.match(/^(\d+):F$/);
						const originalId = match ? match[1] : null;
						code = `DR${originalId}`;
					}
					else
					{
						code = `D${item.id}`;
					}
					break;
				case 'site_groups':
					if (/^(\d+)$/.test(item.id))
					{
						code = `G${item.id}`;
					}
					else
					{
						code = item.id;
					}

					break;
				case 'projectmembers':
					const subType = item.customData.get('memberCategory');
					const originalId = item.customData.get('parentId');
					switch (subType)
					{
						case 'owner':
							code = `SG${originalId}_A`;
							break;
						case 'moderator':
							code = `SG${originalId}_E`;
							break;
						case 'all':
							code = `SG${originalId}_K`;
							break;
					}
					break;
			}

			return code;
		};
	}

	#normalizeType()
	{
		return (originalType) => {
			switch (originalType)
			{
				case 'user':
					return 'users';
				case 'intranet':
					return 'departments';
				case 'socnetgroup':
					return 'sonetgroups';
				case 'group':
					return 'groups';
				default:
					return '';
			}
		};
	}
}
