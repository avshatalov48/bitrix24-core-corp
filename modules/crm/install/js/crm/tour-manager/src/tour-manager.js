import { TourInterface } from './tour.js';
import { BannerDispatcher } from 'crm.integration.ui.banner-dispatcher';
import Queue from './queue.js';

export class TourManager
{
	static TOUR_FINISH_EVENT = 'UI.Tour.Guide:onFinish';

	static instance: ?TourManager = null;
	#queue: Queue = new Queue();
	#current: ?TourInterface = null;
	#bannerDispatcher: BannerDispatcher;

	static getInstance(): TourManager
	{
		if (!TourManager.instance)
		{
			TourManager.instance = new TourManager();
		}

		return TourManager.instance;
	}

	constructor()
	{
		this.#bannerDispatcher = new BannerDispatcher();
	}

	registerWithLaunch(tour: TourInterface): void
	{
		this.register(tour);
		this.launch();
	}

	launch(): void
	{
		if (this.#current || this.#isBannerDispatcherAvailable())
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

		if (this.#isBannerDispatcherAvailable())
		{
			this.#toBannerDispatcherQueue(tour);

			return;
		}

		this.#queue.push(tour);
		this.#subscribeTourFinish(tour);
	}

	#isBannerDispatcherAvailable(): boolean
	{
		return this.#bannerDispatcher.isAvailable();
	}

	#toBannerDispatcherQueue(tour: TourInterface): void
	{
		this.#bannerDispatcher.toQueue((onDone: Function) => {
			tour.getGuide().subscribe(TourManager.TOUR_FINISH_EVENT, onDone);
			tour.show();
		});
	}

	#subscribeTourFinish(tour: TourInterface): void
	{
		tour.getGuide().subscribe(TourManager.TOUR_FINISH_EVENT, this.#showNextTour.bind(this));
	}

	#showNextTour(): void
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
