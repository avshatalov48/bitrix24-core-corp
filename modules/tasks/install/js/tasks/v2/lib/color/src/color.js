export type ColorComponents = [number, number, number, number];

export class Color
{
	#limit: number = 255;
	#components: ColorComponents;

	constructor(color: string)
	{
		this.#components = this.parse(color);
	}

	toRgb(): string
	{
		const [r, g, b, a] = this.#components;

		return `rgb(${[r, g, b].map((x) => limitComponent(setComponentAlpha(x, a), this.#limit)).join(', ')})`;
	}

	toRgba(): string
	{
		const [r, g, b, a] = this.#components.map((x) => limitComponent(x, this.#limit));

		return `rgba(${r}, ${g}, ${b}, ${a})`;
	}

	setOpacity(alpha: number): Color
	{
		this.#components[3] = alpha;

		return this;
	}

	limit(limit: number): Color
	{
		this.#limit = limit;

		return this;
	}

	isDark(): boolean
	{
		const [r, g, b] = this.#components;
		const brightness = (r * 299 + g * 587 + b * 114) / 1000;

		return brightness < 170;
	}

	parse(color: string): ColorComponents
	{
		if (color.slice(0, 3) === 'rgb')
		{
			const [r, g, b, a] = color.match(/\d+(\.\d+)?/g).map((x) => parseFloat(x));

			return [r, g, b, a ?? 1];
		}

		const [r, g, b, a] = color.match(/\w\w/g).map((x) => parseInt(x, 16));

		return [r, g, b, a ?? 1];
	}
}

const setComponentAlpha = (x: number, a: number) => Math.round((a * (x / 255) + (1 - a)) * 255);

const limitComponent = (x: number, limit: number) => (x < limit ? x : limit);
