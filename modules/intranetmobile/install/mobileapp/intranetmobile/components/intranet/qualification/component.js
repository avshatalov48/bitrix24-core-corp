(() => {
	const require = (extension) => jn.require(extension);
	const { Qualification } = require('intranet/qualification');

	BX.onViewLoaded(() => Qualification.showComponent(layout, BX.componentParameters.get('QUALIFICATION_DATA')));
})();
