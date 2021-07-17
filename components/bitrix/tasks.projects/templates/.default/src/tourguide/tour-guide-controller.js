import {FirstProjectCreationTourGuide} from './first-project-creation-tour-guide';

export class TourGuideController
{
	constructor(options)
	{
		this.tours = options.tours;

		this.initGuides(options);
	}

	initGuides(options)
	{
		if (this.tours.firstProjectCreation.show)
		{
			this.firstProjectCreationTourGuide = new FirstProjectCreationTourGuide(options);
			this.firstProjectCreationTourGuide.start();
		}
	}
}