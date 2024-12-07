import {Type, Loc, Uri} from 'main.core';
import {CommentAux} from 'socialnetwork.commentaux';

export class CommentRenderer
{
	static getCommentPart(entity): string
	{
		let message = '';

		try
		{
			message = Loc.getMessage(entity.CODE);
		}
		catch (e)
		{

		}

		if (
			!Type.isStringFilled(message)
			|| !Type.isPlainObject(entity.REPLACE_LIST)
		)
		{
			return message;
		}

		let liveData = {};
		if (Type.isPlainObject(entity.REPLACE_LIST.LIVE_DATA))
		{
			liveData = entity.REPLACE_LIST.LIVE_DATA;
			delete entity.REPLACE_LIST.LIVE_DATA;
		}

		Object.keys(entity.REPLACE_LIST).forEach((search) => {
			message = message.replace(search, entity.REPLACE_LIST[search]);
		});

		message = message.replaceAll(/\[USER=(\d+)\](.+?)\[\/USER\]/g, (match, id, name) => {
			return CommentAux.renderEntity({
				ENTITY_TYPE: 'U',
				NAME: name,
				LINK: Loc.getMessage('SONET_EXT_COMMENTAUX_USER_PATH').replace('#user_id#', id),
			});
		});

		const userId = Number(Loc.getMessage('USER_ID'));
		const actionList = [
			'EFFICIENCY',
			'DEADLINE',
			'DEADLINE_CHANGE',
			'TASK_APPROVE',
			'TASK_DISAPPROVE',
			'TASK_COMPLETE',
			'TASK_CHANGE_RESPONSIBLE',
		];
		actionList.forEach((action) => {
			const start = `#${action}_START#`;
			const end = `#${action}_END#`;

			if (
				message.indexOf(start) === -1
				&& message.indexOf(end) === -1
			)
			{
				return;
			}

			switch (action)
			{
				case 'EFFICIENCY':
					if (liveData.EFFICIENCY_MEMBERS.includes(userId))
					{
						let efficiencyUrlStart = Loc.getMessage('SONET_RENDERPARTS_EFFICIENCY_PATH');
						efficiencyUrlStart = efficiencyUrlStart.replace('#user_id#', userId);
						efficiencyUrlStart = `<a href="${efficiencyUrlStart}" target="_blank">`;

						message = message.replace(start, efficiencyUrlStart);
						message = message.replace(end, '</a>');
					}
					else
					{
						message = this.removeAnchors(message, start, end);
					}
					break;

				case 'DEADLINE':
					const regExp = new RegExp(`${start}\\d+${end}`, 'g');
					message = message.replaceAll(regExp, (timestamp) => {
						if (timestamp)
						{
							timestamp = this.removeAnchors(timestamp, start, end);

							return BX.date.format(liveData.DATE_FORMAT, Number(timestamp));
						}
					});
					message = this.removeAnchors(message, start, end);
					break;

				case 'DEADLINE_CHANGE':
				case 'TASK_APPROVE':
				case 'TASK_DISAPPROVE':
				case 'TASK_COMPLETE':
				case 'TASK_CHANGE_RESPONSIBLE':
					if (
						!Type.isUndefined(liveData.TASK_ID)
						&& Number(liveData.TASK_ID) > 0
						&& Object.keys(liveData.RIGHTS[action]).map((id) => Number(id)).includes(userId)
						&& liveData.RIGHTS[action][userId]
					)
					{
						const taskActionLink = this.getTaskActionLink({
							action,
							userId,
							taskId: liveData.TASK_ID,
							deadline: liveData.DEADLINE || null,
						});
						message = message.replace(start, `<a href="${taskActionLink}">`);
						message = message.replace(end, '</a>');
					}
					else
					{
						message = this.removeAnchors(message, start, end);
					}
					break;

				default:
					message = this.removeAnchors(message, start, end);
					break;
			}
		});

		return message.replace("\n", '<br>');
	}

	static getTaskActionLink(params)
	{
		const actionMap = {
			DEADLINE_CHANGE: 'deadlineChange',
			TASK_APPROVE: 'taskApprove',
			TASK_DISAPPROVE: 'taskDisapprove',
			TASK_COMPLETE: 'taskComplete',
			TASK_CHANGE_RESPONSIBLE: 'taskChangeResponsible',
		};

		let link = Loc.getMessage('SONET_RENDERPARTS_TASK_PATH');
		link = link
			.replace('#user_id#', Loc.getMessage('USER_ID'))
			.replace('#task_id#', params.taskId)
		;
		link = Uri.addParam(link, { commentAction: actionMap[params.action] });

		if (params.action === 'DEADLINE_CHANGE' && params.deadline)
		{
			link = Uri.addParam(link, { deadline: params.deadline });
		}

		return link;
	}

	static removeAnchors(message, start, end)
	{
		message = message.replace(start, '');
		message = message.replace(end, '');

		return message;
	}
}