import { TourInterface } from './tour.js';

export default class Queue
{
	#stack: Map<string, TourInterface> = new Map();

	get(id: string): TourInterface | null
	{
		return this.#stack.get(id) ?? null;
	}

	push(tour: TourInterface): void
	{
		this.#stack.set(tour.getGuide().getId(), tour);
	}

	pop(): TourInterface | null
	{
		const lastTour = [...this.#stack.values()].pop();
		if (!lastTour)
		{
			return null;
		}

		this.delete(lastTour.getGuide().getId());

		return lastTour;
	}

	peek(): TourInterface | null
	{
		const [firstTour] = this.#stack.values();
		if (!firstTour)
		{
			return null;
		}

		this.delete(firstTour.getGuide().getId());

		return firstTour;
	}

	delete(id: string): boolean
	{
		return this.#stack.delete(id);
	}

	size(): number
	{
		return this.#stack.size();
	}
}
