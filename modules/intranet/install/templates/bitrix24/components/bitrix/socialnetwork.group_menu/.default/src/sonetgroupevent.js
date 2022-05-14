import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Common as UICommon, GroupMenu as UIGroupMenu } from 'socialnetwork.common';

export default class SonetGroupEvent
{
	constructor(params, additionalData)
	{
		this.moreButtonInstance = !Type.isUndefined(additionalData.moreButtonInstance) ? additionalData.moreButtonInstance : null;

		this.init(params);
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.urls = Type.isPlainObject(params.urls) ? params.urls : {};

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [ sliderEvent ] = event.getCompatData();

			if (sliderEvent.getEventId() === 'sonetGroupEvent')
			{
				this.sonetGroupEventHandler(sliderEvent.getData())
			}
		});

		EventEmitter.subscribe('sonetGroupEvent', (event) => {
			const [ eventData ] = event.getCompatData();

			this.sonetGroupEventHandler(eventData)
		});

	}

	sonetGroupEventHandler(eventData)
	{
		if (!Type.isStringFilled(eventData.code))
		{
			return;
		}

		if ([ 'afterJoinRequestSend', 'afterEdit' ].includes(eventData.code))
		{
			const joinContainerNode = document.getElementById('bx-group-menu-join-cont')
			if (joinContainerNode)
			{
				joinContainerNode.style.display = 'none';
			}
			UICommon.reload();
		}
		else if ([ 'afterSetFavorites' ].includes(eventData.code))
		{
			const sonetGroupMenu = UIGroupMenu.getInstance();
			const favoritesValue = sonetGroupMenu.favoritesValue;

			sonetGroupMenu.setItemTitle(!favoritesValue);
			sonetGroupMenu.favoritesValue = !favoritesValue;
		}
		else if (
			[ 'afterDelete', 'afterLeave' ].includes(eventData.code)
			&& Type.isPlainObject(eventData.data)
			&& !Type.isUndefined(eventData.data.groupId)
			&& Number(eventData.data.groupId) === this.groupId
		)
		{
			top.location.href = this.urls.GroupsList;
		}
		else if (
			[ 'afterSetSubscribe' ].includes(eventData.code)
			&& Type.isPlainObject(eventData.data)
			&& !Type.isUndefined(eventData.data.groupId)
			&& Number(eventData.data.groupId) === this.groupId
			&& this.moreButtonInstance
		)
		{
			this.moreButtonInstance.redrawMenu(eventData.data.value);
		}
	}
}
