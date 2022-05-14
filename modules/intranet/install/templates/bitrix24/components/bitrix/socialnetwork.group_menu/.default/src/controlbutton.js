import {Runtime, Type} from 'main.core';

export default class ControlButton
{
	constructor(params)
	{
		this.init(params);
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.inIframe = !Type.isUndefined(params.inIframe) ? !!params.inIframe : false;

		const controlButtonContainer = document.getElementById('group-menu-control-button-cont');
		if (controlButtonContainer)
		{
			Runtime.loadExtension('intranet.control-button').then(exports => {
				const { ControlButton } = exports;

				new ControlButton({
					container: controlButtonContainer,
					entityType: 'workgroup',
					entityId: this.groupId,
					buttonClassName: `intranet-control-btn-no-hover${this.inIframe ? ' ui-btn-themes' : ''}`,
				});
			});
		}

	}
}
