/**
 * @bxjs_lang_path extension.php
 */

(() => {
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/project/utils';

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

			const result = [];

			if (availableFeatures.includes('tasks'))
			{
				result.push(
					WorkgroupUtil.getTasksTab({
						siteId,
						siteDir,
						guid,
						item,
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
				result.push(
					WorkgroupUtil.getDiskTab({ item }),
				);
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
				title: BX.message('MOBILE_PROJECT_TAB_NEWS'),
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
					componentCode: WorkgroupUtil.getTaskListComponentCode(),
					canOpenInDefault: true,
					scriptPath: WorkgroupUtil.getTaskListScriptPath(),
					rootWidget: WorkgroupUtil.getTaskListRootWidget(),
					params: {
						COMPONENT_CODE: WorkgroupUtil.getTaskListComponentCode(),
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
					},
				},
			};
		}

		static getTaskListComponentCode()
		{
			return 'tasks.dashboard';
		}

		static getTaskListScriptPath()
		{
			const componentName = 'tasks:tasks.dashboard';

			return availableComponents[componentName].publicUrl;
		}

		static getTaskListRootWidget()
		{
			return {
				name: 'layout',
				settings: {
					objectName: 'layout',
					useSearch: true,
					useLargeTitleMode: true,
				},
			};
		}

		static getDiskTab(params)
		{
			const item = params.item;

			return {
				id: WorkgroupUtil.tabNames.disk,
				title: BX.message('MOBILE_PROJECT_TAB_DRIVE'),
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
			});
		}
	}

	this.WorkgroupUtil = WorkgroupUtil;
})();
