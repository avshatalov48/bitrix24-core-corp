import "./style.css"

let loadAppPromise = null;
let isValidatorAdded = false;

const FieldResourceBooking = {
	props: ['field'],
	template: `
		<div :key="field.randomId"></div>
	`,
	data: function()
	{
		return {
			randomId: Math.random()
		};
	},
	mounted()
	{
		this.load();
	},
	watch: {
		field(field)
		{
			if (field.randomId !== this.randomId)
			{
				this.randomId = field.randomId;
				this.load();
			}
		},
	},
	methods: {
		load()
		{
			const loadField = () => {
				if (!window.BX || !window.BX.Calendar || !window.BX.Calendar.Resourcebooking)
				{
					return;
				}

				this.liveFieldController = BX.Calendar.Resourcebooking.getLiveField({
					wrap: this.$el,
					field: this.field.booking,
					actionAgent: (action, options) => {
						let formData = new FormData();
						const data = options.data || {};
						for( let key in data)
						{
							if (!data.hasOwnProperty(key))
							{
								continue;
							}
							let value = data[key];
							if (typeof value === 'object')
							{
								value = JSON.stringify(value);
							}
							formData.set(key, value);
						}

						return window.b24form.App.post(
							this.$root.form.identification.address + '/bitrix/services/main/ajax.php?action=' + action,
							formData
						).then((response) => {
							return response.json();
						});
					}
				});

				if (this.liveFieldController && typeof this.liveFieldController.check === 'function' && !isValidatorAdded)
				{
					this.field.validators.push(() => this.liveFieldController.check());
					isValidatorAdded = true;
				}

				this.liveFieldController.subscribe('change', event => {
					this.field.items = [];
					(event.data || [])
						.filter(value => !!value)
						.forEach((value) => {
							this.field.addItem({value, selected: true});
						});
				});
			};

			let scriptLink = (b24form.common.properties && b24form.common.properties.resourcebooking)
				? b24form.common.properties.resourcebooking.link
				: null;
			if (!loadAppPromise)
			{
				loadAppPromise = new Promise((resolve, reject) => {
					const node = document.createElement('script');
					node.src = scriptLink + '?' + (Date.now()/60000|0);
					node.onload = resolve;
					node.onerror = reject;
					document.head.appendChild(node);
				});
			}
			loadAppPromise.then(loadField)
			/*.catch((e) => {
				this.message = 'App load failed:' + e;
			})*/;
		},
	},
};

export {
	FieldResourceBooking
}