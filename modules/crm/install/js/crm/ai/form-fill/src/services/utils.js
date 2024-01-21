
export const timeout = (ms: number) => {
	return new Promise((resolve) => {
		setTimeout(resolve, ms);
	});
};

export const myScrollTo = async (element: Element, to: number, duration: number) => {
	if (duration <= 0)
	{
		return;
	}
	const difference = to - element.scrollTop;
	const perTick = difference / duration * 10;
	await timeout(10);
	element.scrollTop += perTick;
	if (element.scrollTop === to)
	{
		return;
	}
	await myScrollTo(element, to, duration - 10);
};
