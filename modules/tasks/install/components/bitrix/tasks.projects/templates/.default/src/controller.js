import {PULL as Pull} from 'pull.client';

import {ActionsController} from './actions-controller';
import {Filter} from './filter/filter';
import {MembersPopup} from './members-popup';
import {PullController} from './pull-controller';
import {TourGuideController} from './tourguide/tour-guide-controller';

class Controller
{
	constructor(options)
	{
		this.membersPopup = new MembersPopup(options);
		this.filter = new Filter(options);
		this.tourGuideController = new TourGuideController(options);

		ActionsController.setOptions(options);

		this.initPull(options);
	}

	initPull(options)
	{
		this.pullController = new PullController(options);

		this.pullClient = Pull;
		this.pullClient.subscribe(this.pullController);
	}

	getMembersPopup(): MembersPopup
	{
		return this.membersPopup;
	}

	getFilter(): Filter
	{
		return this.filter;
	}
}

export {Controller, ActionsController};