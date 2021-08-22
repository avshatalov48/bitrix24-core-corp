let loaded = null;
const callbacks = [];
function load(callback)
{
	if (loaded)
	{
		callback();
		return;
	}

	callbacks.push(callback);
	if (loaded === false)
	{
		return;
	}

	loaded = false;
	const node = document.createElement('SCRIPT');
	node.setAttribute("type", "text/javascript");
	node.setAttribute("async", "");
	node.setAttribute("src", 'https://www.google.com/recaptcha/api.js');
	node.onload = () => window.grecaptcha.ready(() => {
		loaded = true;
		callbacks.forEach(callback => callback());
	});
	(document.getElementsByTagName('head')[0] || document.documentElement).appendChild(node);
}

export default {
	props: ['form'],
	methods: {
		canUse()
		{
			return this.form.recaptcha.canUse();
		},
		renderCaptcha()
		{
			this.form.recaptcha.render(this.$el.children[0]);
		}
	},
	mounted()
	{
		if (!this.canUse())
		{
			return;
		}

		load(() => this.renderCaptcha());
	},
	template: `<div v-if="canUse()" class="b24-form-recaptcha"><div></div></div>`,
};