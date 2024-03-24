import { TourInterface } from './tour.js';
import Queue from './queue.js';

export class TourManager
{
	static TOUR_FINISH_EVENT = 'UI.Tour.Guide:onFinish';

	static instance: ?TourManager = null;
	#queue: Queue = new Queue();
	#current: ?TourInterface = null;

	static getInstance(): TourManager
	{
		if (!TourManager.instance)
		{
			TourManager.instance = new TourManager();
		}

		return TourManager.instance;
	}

	registerWithLaunch(tour: TourInterface): void
	{
		this.register(tour);
		this.launch();
	}

	launch(): void
	{
		if (this.#current)
		{
			return;
		}

		this.#current = this.#queue.peek();
		this.#current?.show();
	}

	register(tour: TourInterface): void
	{
		if (!tour.canShow())
		{
			return;
		}

		this.#queue.push(tour);
		this.#subscribeTourFinish(tour);
	}

	#subscribeTourFinish(tour: TourInterface): void
	{
		tour.getGuide().subscribe(TourManager.TOUR_FINISH_EVENT, this.showNextTour.bind(this));
	}

	showNextTour(): void
	{
		const nextTour = this.#queue.peek();
		if (!nextTour)
		{
			this.#current = null;

			return;
		}

		this.#current = nextTour;
		this.#current.show();
	}
}
