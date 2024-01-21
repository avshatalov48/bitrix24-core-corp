/**
 * @module calendar/sync-page/wizard/stage
 */
jn.define('calendar/sync-page/wizard/stage', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class SyncWizardStage
	 */
	class SyncWizardStage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				status: 'default',
			};

			this.iconRef = null;
		}

		render()
		{
			return View(
				{
					style: {
						marginTop: 14,
						marginBottom: 14,
						flexDirection: 'row',
					},
				},
				this.renderIcon(),
				this.renderTitle(),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: {
						width: 35,
						height: 35,
						alignItems: 'center',
					},
				},
				this.isDefault() && this.renderDefaultIcon(),
				this.isLoading() && this.renderLoadingIcon(),
				this.isCompleted() && this.renderCompleteIcon(),
			);
		}

		renderLoadingIcon()
		{
			return LottieView(
				{
					style: {
						height: 22,
						width: 22,
					},
					data: {
						content: lottie.loading,
					},
					params: {
						loopMode: 'loop',
					},
					autoPlay: true,
				},
			);
		}

		renderDefaultIcon()
		{
			return Image(
				{
					style: {
						width: 20,
						height: 20,
					},
					svg: {
						content: icons.default,
					},
					ref: (ref) => {
						this.iconRef = ref;
					},
				},
			);
		}

		renderCompleteIcon()
		{
			return LottieView(
				{
					style: {
						height: 35,
						width: 35,
					},
					data: {
						content: lottie.complete,
					},
					params: {
						loopMode: 'playOnce',
					},
					autoPlay: true,
				},
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						paddingLeft: 7,
						paddingRight: 35,
					},
				},
				Text(
					{
						text: this.props.title,
						style: {
							color: this.isDefault()
								? AppTheme.colors.base3
								: AppTheme.colors.base1,
							fontSize: 17,
							fontWeight: '400',
						},
					},
				),
			);
		}

		isCompleted()
		{
			return this.state.status === stageState.completed;
		}

		isLoading()
		{
			return this.state.status === stageState.loading;
		}

		isDefault()
		{
			return this.state.status === stageState.default;
		}

		/**
		 * @public
		 */
		setCompleteState()
		{
			const status = stageState.completed;

			this.setState({ status });
		}

		/**
		 * @public
		 */
		setLoadingState()
		{
			const status = stageState.loading;

			this.setState({ status });
		}
	}

	const stageState = {
		default: 'default',
		loading: 'loading',
		completed: 'completed',
	};

	const icons = {
		complete: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 10C20 15.5228 15.5228 20 10 20C4.47715 20 0 15.5228 0 10C0 4.47715 4.47715 0 10 0C15.5228 0 20 4.47715 20 10Z" fill="${AppTheme.colors.accentMainSuccess}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.54786 11.0331L6.46936 8.9336L5 10.4178L8.4679 13.9207L8.46933 13.9192L8.5493 14L15 7.48419L13.5306 6L8.54786 11.0331Z" fill="${AppTheme.colors.base8}"/></svg>`,
		default: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 10C20 15.5228 15.5228 20 10 20C4.47715 20 0 15.5228 0 10C0 4.47715 4.47715 0 10 0C15.5228 0 20 4.47715 20 10Z" fill="${AppTheme.colors.base4}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.54786 11.0331L6.46936 8.9336L5 10.4178L8.4679 13.9207L8.46933 13.9192L8.5493 14L15 7.48419L13.5306 6L8.54786 11.0331Z" fill="${AppTheme.colors.base7}"/></svg>`,
	};

	const lottie = {
		loading: '{"nm":"Frame 388","v":"5.9.6","fr":60,"ip":0,"op":47,"w":20,"h":20,"ddd":0,"markers":[],"assets":[{"nm":"[GROUP] Subtract - Null / Subtract / Subtract - Null / Subtract","fr":60,"id":"lm61gaefrg1ftmc70od","layers":[{"ty":3,"ddd":0,"ind":6,"hd":false,"nm":"1 - Null","ks":{"a":{"a":0,"k":[10,10]},"o":{"a":0,"k":100},"p":{"a":0,"k":[10,10]},"r":{"a":1,"k":[{"t":0,"s":[0],"o":{"x":[0.5],"y":[0.25]},"i":{"x":[0.5],"y":[0.75]}},{"t":48,"s":[360]}]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":48,"bm":0,"sr":1},{"ty":3,"ddd":0,"ind":7,"hd":false,"nm":"Subtract - Null","parent":6,"ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0.0493]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":48,"bm":0,"sr":1},{"ty":4,"ddd":0,"ind":8,"hd":false,"nm":"Subtract","parent":7,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"st":0,"ip":0,"op":48,"bm":0,"sr":1,"shapes":[{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[19.9506,10.9507],[10,19.9506],[0,9.9506],[9,0],[9,3.0215],[3,9.9506],[10,16.9506],[16.9291,10.9507],[19.9506,10.9507]],"i":[[0,0],[5.1853,0],[0,5.5229],[-5.0533,0.5017],[0,0],[0,-3.5265],[-3.866,0],[-0.4853,3.3923],[0,0]],"o":[[-0.5017500000000013,5.0533100000000015],[-5.52284,0],[0,-5.18536],[0,0],[-3.3923000000000005,0.48523000000000005],[0,3.8659999999999997],[3.52646,0],[0,0],[0,0]]}}},{"ty":"fl","o":{"a":0,"k":100},"c":{"a":0,"k":[0.8745098039215686,0.8784313725490196,0.8901960784313725,1]},"nm":"Fill","hd":false,"r":1},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]}]},{"ty":3,"ddd":0,"ind":9,"hd":false,"nm":"Subtract - Null","parent":6,"ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[10,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":48,"bm":0,"sr":1},{"ty":4,"ddd":0,"ind":10,"hd":false,"nm":"Subtract","parent":9,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"st":0,"ip":0,"op":48,"bm":0,"sr":1,"shapes":[{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[10,10],[0,0],[0,3],[7,10],[10,10]],"i":[[0,0],[5.5229,0],[0,0],[0,-3.866],[0,0]],"o":[[0,-5.52285],[0,0],[3.86599,0],[0,0],[0,0]]}}},{"ty":"fl","o":{"a":0,"k":100},"c":{"a":0,"k":[0.1843137254901961,0.7764705882352941,0.9647058823529412,1]},"nm":"Fill","hd":false,"r":1},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]}]}]},{"nm":"[FRAME] Frame 388 - Null / 1","fr":60,"id":"lm61gaeencd70zugje","layers":[{"ty":3,"ddd":0,"ind":11,"hd":false,"nm":"Frame 388 - Null","ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":48,"bm":0,"sr":1},{"ddd":0,"ind":12,"ty":0,"nm":"1","refId":"lm61gaefrg1ftmc70od","sr":1,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"ao":0,"w":20,"h":20,"ip":0,"op":48,"st":0,"hd":false,"bm":0}]}],"layers":[{"ddd":0,"ind":1,"ty":0,"nm":"Frame 388","refId":"lm61gaeencd70zugje","sr":1,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"ao":0,"w":20,"h":20,"ip":0,"op":48,"st":0,"hd":false,"bm":0}],"meta":{"a":"","d":"","tc":"","g":"Aninix"}}',
		complete: '{"assets":[{"id":"Cs1cpqQf8soPqZ5LntOpu","layers":[{"ddd":0,"ind":2,"ty":4,"ln":"layer_2","sr":1,"ks":{"a":{"a":0,"k":[7.875,6.75]},"o":{"a":0,"k":100},"p":{"a":0,"k":[460.5,461]},"r":{"a":0,"k":0},"s":{"a":0,"k":[133.33,133.33]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"ao":0,"ip":0,"op":45,"st":0,"bm":0,"shapes":[{"ty":"gr","nm":"surface1029","it":[{"ty":"gr","it":[{"ty":"sh","d":1,"ks":{"a":0,"k":{"c":true,"i":[[0,0],[0,0],[0,0],[0,0],[0,0],[0,0],[0,0],[0,0]],"o":[[0,0],[0,0],[0,0],[0,0],[0,0],[0,0],[0,0],[0,0]],"v":[[5.59,8.49],[2.31,4.95],[0,7.45],[5.46,13.37],[5.46,13.36],[5.59,13.5],[15.75,2.5],[13.44,0]]}}},{"ty":"fl","c":{"a":0,"k":[1,1,1,1]},"r":2,"o":{"a":0,"k":100}},{"ty":"tr","nm":"Transform","a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}}]},{"ty":"tr","nm":"Transform","a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}}]}]},{"ddd":0,"ind":3,"ty":4,"ln":"layer_3","sr":1,"ks":{"a":{"a":0,"k":[17.25,17.25]},"o":{"a":0,"k":100},"p":{"a":0,"k":[460,460]},"r":{"a":0,"k":0},"s":{"a":0,"k":[133.33,133.33]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"ao":0,"ip":0,"op":45,"st":0,"bm":0,"shapes":[{"ty":"gr","nm":"surface1034","it":[{"ty":"gr","it":[{"ty":"sh","d":1,"ks":{"a":0,"k":{"c":true,"i":[[0,0],[9.53,0],[0,9.53],[-9.53,0],[0,-9.53]],"o":[[0,9.53],[-9.53,0],[0,-9.53],[9.53,0],[0,0]],"v":[[34.5,17.25],[17.25,34.5],[0,17.25],[17.25,0],[34.5,17.25]]}}},{"ty":"fl","c":{"a":0,"k":[0.62,0.81,0,1]},"r":1,"o":{"a":0,"k":100}},{"ty":"tr","nm":"Transform","a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}}]},{"ty":"tr","nm":"Transform","a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}}]}]}]}],"ddd":0,"fr":60,"h":46,"ip":0,"layers":[{"ddd":0,"ind":1,"ty":0,"nm":"","ln":"precomp_T7drIXbDz1Yc6MEVtsgg21","sr":1,"ks":{"a":{"a":0,"k":[460,460]},"o":{"a":0,"k":100},"p":{"a":0,"k":[23,23]},"r":{"a":0,"k":0},"s":{"a":1,"k":[{"t":0,"s":[100,100],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":20,"s":[100,100],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":21,"s":[99.59,99.59],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":22,"s":[90.37,90.37],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":23,"s":[82.2,82.2],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":24,"s":[75.03,75.03],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":25,"s":[68.8,68.8],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":26,"s":[63.44,63.44],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":27,"s":[58.9,58.9],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":28,"s":[55.13,55.13],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":29,"s":[52.05,52.05],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":30,"s":[49.62,49.62],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":31,"s":[47.76,47.76],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":32,"s":[46.43,46.43],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":33,"s":[45.57,45.57],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":34,"s":[45.11,45.11],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":35,"s":[45,45],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":36,"s":[45.18,45.18],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":37,"s":[45.59,45.59],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":38,"s":[46.16,46.16],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":39,"s":[46.85,46.85],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":40,"s":[47.59,47.59],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":41,"s":[48.32,48.32],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":42,"s":[48.99,48.99],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":43,"s":[49.53,49.53],"i":{"x":0,"y":0},"o":{"x":1,"y":1}},{"t":44,"s":[49.88,49.88],"i":{"x":0,"y":0},"o":{"x":1,"y":1}}]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"ao":0,"w":920,"h":920,"ip":0,"op":45,"st":0,"bm":0,"refId":"Cs1cpqQf8soPqZ5LntOpu"}],"meta":{"g":"https://jitter.video"},"nm":"Bullet:-Finished","op":45,"v":"5.7.4","w":46}',
	};

	module.exports = { SyncWizardStage };
});
