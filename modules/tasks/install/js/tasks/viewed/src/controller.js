import { ajax } from 'main.core';

export class Controller
{
	userComments(data={})
	{
		return ajax.runAction(
			'tasks.viewedGroup.user.markAsRead',
			{
				data:{
					fields: data
				}
			}
		)
	}

	projectComments(data={})
	{
		return ajax.runAction(
			'tasks.viewedGroup.project.markAsRead',
			{
				data:{
					fields: data
				}
			}
		)
	}

	scrumComments(data={})
	{
		return ajax.runAction(
			'tasks.viewedGroup.scrum.markAsRead',
			{
				data:{
					fields: data
				}
			}
		)
	}
}