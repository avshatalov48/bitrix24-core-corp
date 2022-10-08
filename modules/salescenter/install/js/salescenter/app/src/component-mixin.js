export default {
	data()
	{
		return {
			isFaded: false,
		};
	},
	mounted()
	{
		this.createPinner();
	},
	created()
	{
		this.$root.$on('on-start-progress', () => {
			this.startFade();
		});

		this.$root.$on('on-stop-progress', () => {
			this.endFade();
		});
	},
	methods: {
		startFade()
		{
			this.isFaded = true;
		},
		endFade()
		{
			this.isFaded = false;
		},
		createPinner()
		{
			let buttonsPanel = this.$refs['buttonsPanel'];
			if(buttonsPanel)
			{
				this.$root.$el.parentNode.appendChild(buttonsPanel);
				new BX.UI.Pinner(
					buttonsPanel,
					{
						fixBottom: this.$root.$app.isFrame,
						fullWidth: this.$root.$app.isFrame
					}
				);
			}
		},
		close()
		{
			this.$root.$app.closeApplication();
		},
	},
	computed: {
		isOrderPublicUrlAvailable()
		{
			return this.$root.$app.isOrderPublicUrlAvailable;
		},
		compilation()
		{
			return this.$root.$app.compilation;
		},
		wrapperClass()
		{
			return {'salescenter-app-wrapper-fade': this.isFaded};
		},
		wrapperStyle()
		{
			const position = BX.pos(this.$root.$el);

			let offset = position.top + 20;
			if(this.$root.$nodes.footer)
			{
				offset += BX.pos(this.$root.$nodes.footer).height;
			}
			const buttonsPanel = this.$refs['buttonsPanel'];
			if(buttonsPanel)
			{
				offset += BX.pos(buttonsPanel).height;
			}

			//?auto
			return {'minHeight': 'calc(100vh - ' + offset + 'px)'};
		},
	}
}
