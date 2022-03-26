import {FirstProjectCreationTourGuide} from './first-project-creation-tour-guide';
import {FirstScrumCreationTourGuide} from './first-scrum-creation-tour-guide';

export class TourGuideController
{
	constructor(options)
	{
		this.tours = options.tours;

		this.initGuides(options);
	}

	initGuides(options)
	{
		if (this.tours.firstProjectCreation && this.tours.firstProjectCreation.show)
		{
			this.firstProjectCreationTourGuide = new FirstProjectCreationTourGuide(options);
			this.firstProjectCreationTourGuide.start();
		}

		if (this.tours.firstScrumCreation && this.tours.firstScrumCreation.show)
		{
			this.firstScrumCreationTourGuide = new FirstScrumCreationTourGuide(options);
			this.firstScrumCreationTourGuide.start();
		}
	}
}