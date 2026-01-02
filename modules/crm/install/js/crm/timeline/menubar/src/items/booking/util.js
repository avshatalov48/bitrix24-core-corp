export function Resolvable(): Promise
{
	let resolve;
	const promise = new Promise((res) => {
		resolve = res;
	});

	promise.resolve = resolve;

	return promise;
}
