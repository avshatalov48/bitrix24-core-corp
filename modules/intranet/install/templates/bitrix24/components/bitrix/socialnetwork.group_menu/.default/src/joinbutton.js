import { ajax, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Common as UICommon} from 'socialnetwork.common';

export default class JoinButton
{
	constructor(params)
	{
		this.init(params);
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.urls = Type.isPlainObject(params.urls) ? params.urls : {};

		const joinButtonNode = document.getElementById('bx-group-menu-join');
		if (joinButtonNode)
		{
			joinButtonNode.addEventListener('click', this.sendJoinRequest.bind(this));
		}
	}

	sendJoinRequest(event)
	{
		const button = event.currentTarget;

		UICommon.showButtonWait(button);

		ajax.runAction('socialnetwork.api.usertogroup.join', {
			data: {
				params: {
					groupId: this.groupId,
				},
			}
		}).then((response) => {
			UICommon.hideButtonWait(button);

			if (
				response.data.success
				&& Type.isStringFilled(this.urls.view)
			)
			{
				const sonetGroupEventData = {
					code: 'afterJoinRequestSend',
					data: {
						groupId: this.groupId,
					}
				};
				EventEmitter.emit(window.top, 'sonetGroupEvent', new BaseEvent({
					compatData: [ sonetGroupEventData ],
					data: [ sonetGroupEventData ],
				}))

				window.location.href = this.urls.view;
			}
		}, () => {
			UICommon.hideButtonWait(button);
		});
	}
}
