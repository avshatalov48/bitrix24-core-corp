(function()
{
	const projectCache = new Map();
	const projectKeys = [
		'ID', 'NAME', 'OPENED', 'NUMBER_OF_MEMBERS',
		'AVATAR', 'AVATAR_TYPE', 'AVATAR_TYPES',
		'ADDITIONAL_DATA',
	];

	class ProjectBackgroundAction
	{
		constructor()
		{
			BX.addCustomEvent(
				'projectbackground::project::action',
				(data) => {
					ProjectBackgroundAction.executeAction(data);
				}
			);
		}

		static executeAction(data)
		{
			const projectId = data.projectId ? parseInt(data.projectId) : 0;
			const action = data.action ? data.action : null;
			const siteId = data.siteId ? data.siteId : null;
			const siteDir = data.siteDir ? data.siteDir : null;
			const item = data.item ? data.item : null;
			const newsPathTemplate = data.newsPathTemplate || '';
			const calendarWebPathTemplate = data.calendarWebPathTemplate || '';
			const currentUserId = data.currentUserId || env.userId;

			if (action === 'view')
			{
				ProjectBackgroundAction.openProject(item, {
					projectId,
					siteId,
					siteDir,
					newsPathTemplate,
					calendarWebPathTemplate,
					currentUserId,
				});
			}
		}

		static openProject(item, params)
		{
			if (
				Application.getApiVersion() < 41
				|| params.projectId <= 0
			)
			{
				return;
			}

			if (item === null)
			{
				BX.postComponentEvent('project.background::showLoadingIndicator');

				ProjectBackgroundAction.getProjectData(params)
					.then((result) => {
						BX.postComponentEvent('project.background::hideLoadingIndicator');

						const data = result.data || null;
						if (!data)
						{
							return;
						}

						const item = {
							id: params.projectId,
							title: (data.NAME || ''),
							params: {
								avatar: WorkgroupUtil.getAvatarUrl(data),
								initiatedByType: data.ADDITIONAL_DATA.INITIATED_BY_TYPE,
								features: data.ADDITIONAL_DATA.FEATURES,
								membersCount: parseInt(data.NUMBER_OF_MEMBERS || 0),
								role: data.ADDITIONAL_DATA.ROLE,
								opened: (data.OPENED || 'N'),
							},
						};

						params.newsPathTemplate = (data.ADDITIONAL_DATA.projectNewsPathTemplate || '');
						params.calendarWebPathTemplate = (data.ADDITIONAL_DATA.projectCalendarWebPathTemplate || '');

						ProjectBackgroundAction.openComponent(item, params);
					})
					.catch(() => {
						BX.postComponentEvent('project.background::hideLoadingIndicator');
					});
			}
			else
			{
				ProjectBackgroundAction.openComponent(item, params);
			}
		}

		static openComponent(item, params)
		{
			const {
				siteId,
				siteDir,
			} = params;

			const subtitle = WorkgroupUtil.getSubtitle(item.params.membersCount);
			const guid = WorkgroupUtil.createGuid();

			const tabs = WorkgroupUtil.getTabsItems({
				availableFeatures: item.params.features,
				projectNewsPathTemplate: (params.newsPathTemplate || ''),
				siteId: siteId,
				siteDir: siteDir,
				guid: guid,
			}, item);

			PageManager.openComponent('JSStackComponent', {
				scriptPath: availableComponents['project.tabs'].publicUrl,
				componentCode: 'project.tabs',
				canOpenInDefault: true,
				params: {
					id: item.id,
					subtitle: subtitle,
					item: item,
					calendarWebPathTemplate: (params.calendarWebPathTemplate || ''),
					currentUserId: (params.currentUserId || env.userId),
					siteId: siteId,
					guid: guid,
				},
				title: item.title,
				rootWidget: {
					name: 'tabs',
					settings: {
						objectName: 'tabs',
						titleParams: {
							text: item.title,
							detailText: subtitle,
							imageUrl: item.params.avatar,
							userLargeTitleMode: true,
						},
						grabTitle: false,
						tabs: {
							items: tabs,
						},
					},
				},
			});
		}

		static getProjectData(params)
		{
			const {
				projectId,
				siteId,
				siteDir,
			} = params;

			return new Promise((resolve, reject) => {

				if (projectCache.has(projectId))
				{
					resolve({
						data: projectCache.get(projectId),
					});
				}
				else
				{
					BX.ajax.runAction('socialnetwork.api.workgroup.get', {
						data: {
							params: {
								groupId: projectId,
								mode: 'mobile',
								select: [ 'AVATAR', 'AVATAR_TYPES' ],
								features: [
									'tasks',
									'blog',
									'files',
									'calendar',
								],
								mandatoryFeatures: [ 'blog' ],
								siteId: siteId,
								siteDir: siteDir,
							}
						}
					}).then((response) => {

						if (!response.data)
						{
							response.data = {};
						}

						for (const key in response.data)
						{
							if (!projectKeys.includes(key))
							{
								delete response.data[key];
							}
						}

						projectCache.set(projectId, response.data);

						return {
							data: response.data,
						};

					}).then((result) => {
						const data = result.data;

						BX.ajax.runAction('mobile.option.get', {
							data: {
								params: {
									name: [
										'projectNewsPathTemplate',
										'projectCalendarWebPathTemplate',
									],
									siteId: siteId,
									siteDir: siteDir,
								}
							}
						}).then((result) => {
							const optionData = result.data;
							data.ADDITIONAL_DATA.projectNewsPathTemplate = optionData.projectNewsPathTemplate;
							data.ADDITIONAL_DATA.projectCalendarWebPathTemplate = optionData.projectCalendarWebPathTemplate;

							resolve({
								data,
							});
						}).catch(() => {
							reject({
								errors: result.errors,
							});
						});
					}).catch((response) => {
						reject({
							errors: result.errors,
						});
					});
				}
			});
		}
	}

	this.ProjectBackgroundAction = new ProjectBackgroundAction();
})();