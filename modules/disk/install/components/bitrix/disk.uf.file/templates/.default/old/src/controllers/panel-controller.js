import DefaultController from './default-controller';

export default class PanelController extends DefaultController
{
	constructor({container, eventObject})
	{
		super({
			container: container.querySelector('[data-bx-role="control-panel"]'),
			eventObject: eventObject
		});
	}
}