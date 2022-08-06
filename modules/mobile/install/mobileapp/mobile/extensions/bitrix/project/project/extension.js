/**
 * @bxjs_lang_path extension.php
 */

(() => {
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/project/project';

	class Workgroup
	{
		/**
		 * @param params
		 * @param {String} params.newsPathTemplate path template to a group news page
		 * @param {String} params.calendarWebPathTemplate path template to a web group calendar page
		 * @param {Number} params.currentUserId current user id
		 */
		constructor(params = {})
		{
			this.groupId = parseInt(params.groupId || 0);
			this.newsPathTemplate = (params.newsPathTemplate || '');
			this.calendarWebPathTemplate = (params.calendarWebPathTemplate || '');
			this.currentUserId = parseInt(params.currentUserId || 0);
			this.tabs = (params.tabs || null);
			this.subtitle = (params.subtitle || '');
			this.item = (params.item || {});
			this.guid = (params.guid || WorkgroupUtil.createGuid());

			if (
				this.groupId <= 0
				|| !this.tabs
			)
			{
				return;
			}

			this.bindEvents();
			this.fillEmptyData();
		}

		fillEmptyData()
		{
			if (
				!this.subtitle
				|| !this.item.params.avatar
			)
			{
				WorkgroupUtil.getGroupData(this.groupId).then(
					(data) => {

						this.tabs.setTitle({
							text: data.NAME,
							detailText: WorkgroupUtil.getSubtitle(data.NUMBER_OF_MEMBERS),
							imageUrl: WorkgroupUtil.getAvatarUrl(data),
							userLargeTitleMode: true,
						});
					},
					response => console.error(response)
				)
			}
		}

		bindEvents()
		{
			this.tabs.on('titleClick', () => ProjectViewManager.open(this.currentUserId, this.groupId));
			this.tabs.on('onTabSelected', (tab) => {
				this.onTabSelected({
					tab: tab,
					groupId: this.groupId,
				})
			});

			BX.addCustomEvent('tasks.list:setVisualCounter', data => this.onTasksCounterSet(data));
			BX.addCustomEvent('tasks.list:updateTitle', (data) => this.onTasksTitleUpdated(data));
			BX.addCustomEvent('background:updateTasksCounter', (data) => this.updateTasksCounter(data));
		}

		onTasksCounterSet(data)
		{
			if (data.guid === this.guid)
			{
				WorkgroupUtil.updateTasksCounter(data.value);
			}
		}

		onTasksTitleUpdated(data)
		{
			if (data.guid === this.guid)
			{
				this.tabs.setTitle({useProgress: data.useProgress}, true);
			}
		}

		updateTasksCounter(data)
		{
			this.tabs.updateItem(WorkgroupUtil.tabNames.tasks, data);
		}

		onTabSelected(params)
		{
			const tab = params.tab || null;
			const groupId = parseInt(params.groupId || 0);

			if (
				tab === null
				|| groupId <= 0
			)
			{
				return;
			}

			if (tab.id === WorkgroupUtil.tabNames.calendar)
			{
				WorkgroupUtil.onTabSelectedCalendar(this.calendarWebPathTemplate.replace('#group_id#', groupId));
			}
		}
	}

	this.Workgroup = Workgroup;
})();