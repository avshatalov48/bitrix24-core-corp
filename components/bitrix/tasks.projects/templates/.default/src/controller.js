import {PULL as Pull} from 'pull.client';

import {ActionsController} from './actions-controller';
import {Filter} from './filter/filter';
import {MembersPopup} from './members-popup';
import {ScrumMembersPopup} from './scrum-members-popup';
import {PullController} from './pull-controller';
import {TourGuideController} from './tourguide/tour-guide-controller';

class Controller
{
	constructor(options)
	{
		this.membersPopup = new MembersPopup(options);
		this.scrumMembersPopup = new ScrumMembersPopup(options);
		this.filter = new Filter(options);
		this.tourGuideController = new TourGuideController(options);

		options.filter = this.filter;

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

	getScrumMembersPopup(): ScrumMembersPopup
	{
		return this.scrumMembersPopup;
	}

	getFilter(): Filter
	{
		return this.filter;
	}
}

export {Controller, ActionsController};