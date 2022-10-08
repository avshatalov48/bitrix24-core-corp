import "main.polyfill.intersectionobserver";

export class ViewObserver
{
	#element: Element;
	#handler: Function;
	#observer: IntersectionObserver;

	constructor(element: Element, handler: Function)
	{
		this.#element = element;
		this.#handler = handler;
	}

	static observe(element: Element, handler: Function): undefined
	{
		const instance = (new ViewObserver(element, handler));
		instance.#init();
	}

	#init(): undefined
	{
		this.#observer = new IntersectionObserver((entries, observer) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						this.#handler();
						observer.unobserve(entry.target);
					}
				})
			},
			{threshold: 0.5}
		);
		this.#observer.observe(this.#element);
	}
}