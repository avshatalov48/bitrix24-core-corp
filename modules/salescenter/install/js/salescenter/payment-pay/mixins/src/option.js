export default {
	methods: {
		option(name, defaultValue) {
			const parts = name.split('.');
			let currentOption = this.options;
			let found = false;

			parts.map((part) => {
				if (currentOption && currentOption.hasOwnProperty(part)) {
					currentOption = currentOption[part];
					found = true;
				} else {
					currentOption = null;
					found = false;
				}
			});

			return found ? currentOption : defaultValue;
		},
	},
};