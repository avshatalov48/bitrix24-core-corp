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
	node.setAttribute('type', 'text/javascript');
	node.setAttribute('defer', '');
	node.setAttribute('src', 'https://smartcaptcha.yandexcloud.net/captcha.js?render=onload&onload=onloadFunction');
	node.onload = () => {
		loaded = true;
		callbacks.forEach((innerCallback) => innerCallback());
	};
	(document.getElementsByTagName('head')[0] || document.documentElement).appendChild(node);
}

export default {

	props: ['form'],

	methods:
	{
		canUse(): boolean
		{
			return this.form.yandexCaptcha.canUse();
		},
		renderCaptcha(): void
		{
			this.form.yandexCaptcha.render(this.$el.children[0]);
		},
	},

	mounted(): void
	{
		if (!this.canUse())
		{
			return;
		}

		load(() => this.renderCaptcha());
	},

	template: `
		<div v-if="canUse()" class="b24-form-yandex-captcha"><div></div></div>
	`,
};
