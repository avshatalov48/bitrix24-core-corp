import 'main.polyfill.intersectionobserver';

let intersectionObserver;
function observeIntersection(entity, callback)
{
	if (!intersectionObserver)
	{
		intersectionObserver = new IntersectionObserver(function(entries) {
			entries.forEach((entry) => {
				if (entry.isIntersecting)
				{
					intersectionObserver.unobserve(entry.target);
					const observedCallback = entry.target.observedCallback;
					delete entry.target.observedCallback;
					setTimeout(observedCallback);
				}
			});
		}, {
			threshold: 0
		});
	}
	entity.observedCallback = callback;

	intersectionObserver.observe(entity);
}


export {
	observeIntersection
}