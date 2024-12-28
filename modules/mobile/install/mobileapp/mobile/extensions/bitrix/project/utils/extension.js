/**
 * @bxjs_lang_path extension.php
 */
(() => {
	const require = (extension) => jn.require(extension);
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { qrauth } = require('qrauth/utils');

	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/project/utils';
	const projectCache = new Map();
	const projectKeys = new Set([
		'ID',
		'NAME',
		'OPENED',
		'NUMBER_OF_MEMBERS',
		'AVATAR',
		'AVATAR_TYPE',
		'AVATAR_TYPES',
		'ADDITIONAL_DATA',
		'TYPE',
		'DIALOG_ID',
	]);

	class WorkgroupUtil
	{
		static get tabNames()
		{
			return {
				tasks: 'tasks',
				news: 'news',
				disk: 'disk',
				calendar: 'calendar',
			};
		}

		static getTabsItems(additionalData, item)
		{
			const availableFeatures = additionalData.availableFeatures || [];
			const projectNewsPathTemplate = additionalData.projectNewsPathTemplate || `${env.siteDir}mobile/log/?group_id=#group_id#`;
			const siteId = additionalData.siteId || env.siteId;
			const siteDir = additionalData.siteDir || env.siteDir;
			const guid = additionalData.guid || WorkgroupUtil.createGuid();
			const { analyticsLabel = {} } = additionalData;

			const result = [];

			const isTasksMobileInstalled = BX.prop.getBoolean(jnExtensionData.get('project/utils'), 'isTasksMobileInstalled', false);

			const isAirDiskFeatureEnable = BX.prop.getBoolean(jnExtensionData.get('project/utils'), 'isAirDiskFeatureEnable', false);

			if (availableFeatures.includes('tasks') && isTasksMobileInstalled)
			{
				result.push(
					WorkgroupUtil.getTasksTab({
						siteId,
						siteDir,
						guid,
						item,
						analyticsLabel: {
							c_section: 'tasks',
							...analyticsLabel,
						},
						currentUserId: env.userId,
					}),
				);
			}

			if (availableFeatures.includes('blog'))
			{
				result.push(
					WorkgroupUtil.getNewsTab(projectNewsPathTemplate.replace('#group_id#', item.id)),
				);
			}

			if (availableFeatures.includes('files'))
			{
				if (isAirDiskFeatureEnable)
				{
					result.push(WorkgroupUtil.getAirDiskTab({ item }));
				}
				else
				{
					result.push(WorkgroupUtil.getDiskTab({ item }));
				}
			}

			if (availableFeatures.includes('calendar'))
			{
				result.push(
					WorkgroupUtil.getCalendarTab({ item }),
				);
			}

			return result;
		}

		static getNewsTab(newsWebPath)
		{
			return {
				id: WorkgroupUtil.tabNames.news,
				title: BX.message('MOBILE_PROJECT_TAB_NEWS2'),
				component: {
					name: 'JSStackComponent',
					componentCode: `web: ${newsWebPath}`,
					rootWidget: {
						name: 'web',
						settings: {
							page: {
								preload: false,
								url: newsWebPath,
								useSearchBar: true,
							},
							cache: false,
						},
					},
				},
			};
		}

		static getTasksTab(params)
		{
			const item = params.item;
			const guid = params.guid || WorkgroupUtil.createGuid();
			const siteId = params.siteId || env.siteId;
			const siteDir = params.siteDir || env.siteDir;
			const currentUserId = params.currentUserId || env.userId;

			return {
				id: WorkgroupUtil.tabNames.tasks,
				title: BX.message('MOBILE_PROJECT_TAB_TASKS'),
				component: {
					name: 'JSStackComponent',
					componentCode: 'tasks.dashboard',
					canOpenInDefault: true,
					scriptPath: availableComponents['tasks:tasks.dashboard'].publicUrl,
					rootWidget: {
						name: 'layout',
						settings: {
							objectName: 'layout',
							useSearch: true,
							useLargeTitleMode: true,
						},
					},
					params: {
						COMPONENT_CODE: 'tasks.dashboard',
						GROUP_ID: item.id,
						USER_ID: currentUserId,
						DATA: {
							groupId: item.id,
							groupName: item.title,
							groupImageUrl: (item.params.avatar || ''),
							groupOpened: item.params.opened,
							relationInitiatedByType: (item.params.initiatedByType || ''),
							relationRole: (item.params.role || ''),
							ownerId: currentUserId,
							getProjectData: (item.params.getProjectData || false),
						},
						IS_TABS_MODE: true,
						TABS_GUID: guid,
						SITE_ID: siteId,
						SITE_DIR: siteDir,
						LANGUAGE_ID: env.languageId,
						PATH_TO_TASK_ADD: `${siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`,
						analyticsLabel: params.analyticsLabel,
					},
				},
			};
		}

		static getDiskTab(params)
		{
			const item = params.item;

			return {
				id: WorkgroupUtil.tabNames.disk,
				title: BX.message('MOBILE_PROJECT_TAB_DRIVE_MSGVER_1'),
				component: {
					name: 'JSStackComponent',
					scriptPath: availableComponents['user.disk'].publicUrl,
					componentCode: 'user.disk',
					canOpenInDefault: false,
					rootWidget: {
						settings: {
							objectName: 'list',
							useSearch: true,
							doNotHideSearchResult: true,
						},
						name: 'list',
					},
					params: {
						ownerId: item.id,
						title: item.title,
						entityType: 'group',
					},
				},
			};
		}

		static getAirDiskTab(params)
		{
			const item = params.item;

			return {
				id: WorkgroupUtil.tabNames.disk,
				title: BX.message('MOBILE_PROJECT_TAB_DRIVE_MSGVER_1'),
				component: {
					name: 'JSStackComponent',
					componentCode: 'disk.tabs.group',
					scriptPath: availableComponents['disk:disk.tabs.group'].publicUrl,
					canOpenInDefault: false,
					rootWidget: {
						name: 'layout',
						settings: {
							objectName: 'layout',
							useSearch: true,
							useLargeTitleMode: true,
						},
					},
					params: {
						GROUP_ID: item.id,
					},
				},
			};
		}

		static getCalendarTab()
		{
			return {
				id: WorkgroupUtil.tabNames.calendar,
				title: BX.message('MOBILE_PROJECT_TAB_CALENDAR'),
				selectable: false,
			};
		}

		static createGuid()
		{
			const s4 = function() {
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
			};

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		static getSubtitle(membersCount)
		{
			if (membersCount > 0)
			{
				const pluralForm = CommonUtils.getPluralForm(membersCount);

				return BX.message(`MOBILE_PROJECT_TAB_MEMBERS_${pluralForm}`).replace('#NUM#', membersCount);
			}

			return '';
		}

		static getAvatarUrl(data)
		{
			let image = `${pathToExtension}/images/default-group-avatar.png`;
			if (data.AVATAR)
			{
				image = data.AVATAR;
			}
			else if (
				data.AVATAR_TYPE
				&& data.AVATAR_TYPES
			)
			{
				image = data.AVATAR_TYPES[data.AVATAR_TYPE].mobileUrl;
			}

			return image;
		}

		static getGroupData(groupId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.workgroup.get', {
					params: {
						groupId,
						select: [
							'AVATAR',
							'AVATAR_TYPES',
						],
					},
				}))
					.call()
					.then(
						(response) => resolve(response.result),
						(response) => reject(response),
					)
				;
			});
		}

		static updateTasksCounter(value)
		{
			BX.postComponentEvent('background:updateTasksCounter', [{
				title: BX.message('MOBILE_PROJECT_TAB_TASKS'),
				counter: Number(value),
				label: (value > 0 ? String(value) : ''),
			}]);
		}

		static onTabSelectedCalendar(url)
		{
			qrauth.open({
				redirectUrl: url || '',
				showHint: true,
				title: BX.message('MOBILE_PROJECT_TAB_CALENDAR_QR_TITLE'),
				analyticsSection: 'project',
			});
		}

		static async openProject(item, initialParams)
		{
			const params = {
				projectId: initialParams.projectId ? parseInt(initialParams.projectId, 10) : 0,
				siteId: initialParams.siteId || null,
				siteDir: initialParams.siteDir || null,
				newsPathTemplate: initialParams.newsPathTemplate || '',
				calendarWebPathTemplate: initialParams.calendarWebPathTemplate || '',
				currentUserId: initialParams.currentUserId || env.userId,
				analyticsLabel: {
					c_section: 'project',
				},
			};

			if (params.projectId <= 0)
			{
				return;
			}

			await tariffPlanRestrictionsReady();
			const { isRestricted, showRestriction } = getFeatureRestriction('socialnetwork_projects_groups');
			if (isRestricted())
			{
				showRestriction({ showInComponent: true });

				return;
			}

			if (item === null)
			{
				BX.postComponentEvent('project.background::showLoadingIndicator');

				WorkgroupUtil.getProjectData(params)
					.then((result) => {
						BX.postComponentEvent('project.background::hideLoadingIndicator');

						const data = result.data || null;
						if (!data)
						{
							return;
						}

						params.newsPathTemplate = (data.ADDITIONAL_DATA.projectNewsPathTemplate || '');
						params.calendarWebPathTemplate = (data.ADDITIONAL_DATA.projectCalendarWebPathTemplate || '');

						WorkgroupUtil.openComponent(
							{
								id: params.projectId,
								title: (data.NAME || ''),
								params: {
									avatar: WorkgroupUtil.getAvatarUrl(data),
									initiatedByType: data.ADDITIONAL_DATA.INITIATED_BY_TYPE,
									features: data.ADDITIONAL_DATA.FEATURES,
									membersCount: parseInt(data.NUMBER_OF_MEMBERS || 0, 10),
									role: data.ADDITIONAL_DATA.ROLE,
									opened: (data.OPENED || 'N'),
									isCollab: data.TYPE === 'collab',
									dialogId: data.DIALOG_ID,
								},
							},
							params,
						);
					})
					.catch(() => BX.postComponentEvent('project.background::hideLoadingIndicator'))
				;
			}
			else
			{
				WorkgroupUtil.openComponent(item, params);
			}
		}

		static openComponent(item, params)
		{
			const {
				siteId,
				siteDir,
				newsPathTemplate,
				calendarWebPathTemplate,
				currentUserId,
				analyticsLabel,
			} = params;

			const subtitle = WorkgroupUtil.getSubtitle(item.params.membersCount);
			const guid = WorkgroupUtil.createGuid();
			const tabs = WorkgroupUtil.getTabsItems(
				{
					siteId,
					siteDir,
					guid,
					availableFeatures: item.params.features,
					projectNewsPathTemplate: (newsPathTemplate || ''),
					analyticsLabel,
				},
				item,
			);

			PageManager.openComponent('JSStackComponent', {
				scriptPath: availableComponents['project.tabs'].publicUrl,
				componentCode: 'project.tabs',
				canOpenInDefault: true,
				params: {
					id: item.id,
					subtitle,
					item,
					calendarWebPathTemplate: (calendarWebPathTemplate || ''),
					currentUserId: (currentUserId || env.userId),
					siteId,
					guid,
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
								select: ['AVATAR', 'AVATAR_TYPES'],
								features: [
									'tasks',
									'blog',
									'files',
									'calendar',
								],
								mandatoryFeatures: ['blog'],
								siteId,
								siteDir,
							},
						},
					}).then((response) => {
						if (!response.data)
						{
							response.data = {};
						}

						for (const key in response.data)
						{
							if (!projectKeys.has(key))
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
									siteId,
									siteDir,
								},
							},
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

	this.WorkgroupUtil = WorkgroupUtil;

	/**
	 * @module project/utils
	 */
	jn.define('project/utils', (require, exports, module) => {
		module.exports = { WorkgroupUtil };
	});
})();
