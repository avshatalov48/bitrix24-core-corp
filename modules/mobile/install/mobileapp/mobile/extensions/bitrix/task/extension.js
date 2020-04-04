/**
* @bxjs_lang_path extension.php
*/

(() =>
{
	class TaskView
	{
		/**
		 *
		 * @param data
		 */
		static open(data)
		{
			PageManager.openComponent("JSStackComponent",
				{

					scriptPath:data.path,
					componentCode: "tasks.list",
					params : data.params,
					rootWidget:{
						name:"tasks.list",
						settings:{
							objectName: "list",
							filter:"view_all",
							useSearch:true,
							menuSections:[{id:"presets"}, {id: "counters", itemTextColor:"#f00"}],
							menuItems:[
								{
									'id': "view_all",
									'title':BX.message("TASKS_ROLE_VIEW_ALL"),
									'sectionCode':'presets',
									'showAsTitle':true,
									'badgeCount':0
								},
								{
									'id': "view_role_accomplice",
									'title':BX.message("TASKS_ROLE_ACCOMPLICE"),
									'sectionCode':'presets',
									'showAsTitle':true,
									'badgeCount':0
								},
								{
									'id': "view_role_auditor",
									'title':BX.message("TASKS_ROLE_AUDITOR"),
									'sectionCode':'presets',
									'showAsTitle':true,
									'badgeCount':0
								},

								{

									'id': "view_role_originator",
									'title':BX.message("TASKS_ROLE_ORIGINATOR"),
									'sectionCode':'presets',
									'showAsTitle':true,
									'badgeCount':0
								},


							]
						},
					}
				}
			)
		}
	}

	this.TaskView = TaskView;
})();