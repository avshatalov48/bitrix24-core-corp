export type LineSegment = [number, number];

export class Segments
{
	#segments: LineSegment[] = [];

	constructor(segments: LineSegment[] = [])
	{
		this.#segments = segments;
	}

	toArray(): LineSegment[]
	{
		return this.#segments;
	}

	add(segment: LineSegment): LineSegment[]
	{
		// <...[+++]...> new segment doesnt add anything
		if (this.#segments.some((it: LineSegment) => segment[0] >= it[0] && segment[1] <= it[1]))
		{
			return this.#segments;
		}

		const intersects = this.#segments.some((it: LineSegment) => segment[1] > it[0] && segment[0] < it[1]);
		if (!intersects)
		{
			this.#segments = [...this.#segments, segment];

			return this.#segments;
		}

		this.#segments = this.#segments.map((it: LineSegment) => {
			// <...[...>+++] extend right
			if (segment[0] >= it[0] && segment[0] <= it[1] && segment[1] >= it[1])
			{
				return [it[0], segment[1]];
			}

			// [+++<...]...> extend left
			if (segment[1] >= it[0] && segment[1] <= it[1] && segment[1] <= it[0])
			{
				return [segment[0], it[1]];
			}

			// [+++<...>+++] replace
			if (segment[0] < it[0] && segment[1] > it[1])
			{
				return segment;
			}

			return it;
		});

		return this.#segments;
	}

	subtract(segment: LineSegment): LineSegment[]
	{
		this.#segments = this.#segments.flatMap((it: LineSegment) => {
			// <...[--->---] cut right
			if (segment[0] >= it[0] && segment[0] <= it[1] && segment[1] >= it[1])
			{
				return [[it[0], segment[0]]];
			}

			// [---<---]...> cut left
			if (segment[1] >= it[0] && segment[1] <= it[1] && segment[1] <= it[0])
			{
				return [[segment[1], it[1]]];
			}

			// <...[---]...> split into 2
			if (segment[0] > it[0] && segment[1] < it[1])
			{
				return [
					[it[0], segment[0]],
					[segment[1], it[1]],
				];
			}

			// [---<--->---] remove
			if (segment[0] < it[0] && segment[1] > it[1])
			{
				return null;
			}

			return [it];
		}).filter((it: LineSegment) => it !== null && (it[1] - it[0] > 0));

		return this.#segments;
	}
}
