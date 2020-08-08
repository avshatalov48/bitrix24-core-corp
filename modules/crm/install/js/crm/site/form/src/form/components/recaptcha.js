let loaded = false;

export default {
	props: ['form'],
	mounted()
	{
		if (!this.canUse())
		{
			return;
		}
		if (loaded)
		{
			this.renderCaptcha();
			return;
		}
		loaded = true;

		const node = document.createElement('SCRIPT');
		node.setAttribute("type", "text/javascript");
		node.setAttribute("async", "");
		node.setAttribute("src", 'https://www.google.com/recaptcha/api.js');
		node.onload = () => window.grecaptcha.ready(() => this.renderCaptcha());
		(document.getElementsByTagName('head')[0] || document.documentElement).appendChild(node);
	},
	template: `<div v-if="canUse()" class="b24-form-recaptcha"><div></div></div>`,
	methods: {
		canUse()
		{
			return this.form.recaptcha.canUse();
		},
		renderCaptcha()
		{
			this.form.recaptcha.render(this.$el.children[0]);
		}
	}
};