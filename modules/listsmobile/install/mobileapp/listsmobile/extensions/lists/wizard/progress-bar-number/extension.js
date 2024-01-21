/**
 * @module lists/wizard/progress-bar-number
 */
jn.define('lists/wizard/progress-bar-number', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');

	class ProgressBarNumber extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {Boolean} props.isCompleted
		 * @param {Number} props.number
		 */
		constructor(props) {
			super(props);

			this.state = {
				isCompleted: props.isCompleted,
			};
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			if (props.isCompleted !== undefined)
			{
				this.setState({
					isCompleted: props.isCompleted,
				});
			}
		}

		get number()
		{
			return this.props.number;
		}

		render()
		{
			const contentStyles = this.state.isCompleted ? this.styles.completed.content : this.styles.uncompleted.content;
			const textStyles = this.state.isCompleted ? this.styles.completed.text : this.styles.uncompleted.text;

			return View(
				{ style: this.styles.wrapper },
				View(
					{ style: { ...this.styles.content, ...contentStyles } },
					Text({
						text: String(this.number),
						style: { ...this.styles.text, ...textStyles },
					}),
					!this.state.isCompleted && this.renderUncompletedLottieView(),
					this.state.isCompleted && this.renderCompletedLottieView(),
				),
			);
		}

		get styles()
		{
			return {
				wrapper: {
					width: 46,
					height: 46,
					alignItems: 'flex-start',
					justifyContent: 'flex-end',
					marginLeft: 1,
				},
				content: {
					width: 44,
					height: 44,
					alignItems: 'center',
					justifyContent: 'center',
				},
				text: {
					height: 34,
					width: 34,
					borderRadius: 100,
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 19,
					fontWeight: '600',
					lineHeight: 22.67,
					textAlign: 'center',
				},
				lottie: {
					height: 44,
					width: 44,
					position: 'absolute',
				},
				uncompleted: {
					content: {
						borderColor: AppTheme.colors.accentExtraAqua,
					},
					text: {
						backgroundColor: AppTheme.colors.accentExtraAqua,
					},
				},
				completed: {
					content: {
						borderColor: AppTheme.colors.accentMainSuccess,
					},
					text: {
						backgroundColor: AppTheme.colors.accentMainSuccess,
					},
				},
			};
		}

		renderCompletedLottieView()
		{
			return LottieView(
				{
					style: this.styles.lottie,
					data: {
						content: '{"nm":"circleatroke2","v":"5.9.6","fr":60,"ip":0,"op":300,"w":46,"h":46,"ddd":0,"markers":[],"assets":[{"nm":"[FRAME] circleatroke2 - Null / ? circleatroke2 - Null / ? circleatroke2 - Stroke","fr":60,"id":"lgdm1qe5a8umik1bb6b","layers":[{"ty":3,"ddd":0,"ind":6694,"hd":false,"nm":"circleatroke2 - Null","ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":300,"bm":0,"sr":1},{"ty":3,"ddd":0,"ind":72800,"hd":false,"nm":"? circleatroke2 - Null","parent":6694,"ks":{"a":{"a":0,"k":[22,22]},"o":{"a":0,"k":100},"p":{"a":0,"k":[23,23]},"r":{"a":1,"k":[{"t":0,"s":[0],"o":{"x":[0],"y":[0]},"i":{"x":[1],"y":[1]}},{"t":300,"s":[360]}]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":300,"bm":0,"sr":1},{"ddd":0,"ind":68357,"hd":false,"nm":"? circleatroke2 - Stroke","parent":72800,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"st":0,"ip":0,"op":300,"bm":0,"sr":1,"ty":4,"shapes":[{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[44,22],[42.9242,28.798],[39.798,34.9316],[34.9316,39.798],[28.798,42.9242],[22,44],[15.202,42.9242],[9.0684,39.798],[4.202,34.9316],[1.0758,28.798],[0,22],[1.0758,15.202],[4.202,9.0684],[9.0684,4.202],[15.202,1.0758],[22,0],[28.798,1.0758],[34.9316,4.202],[39.798,9.0684],[42.9242,15.202],[44,22],[44,22]],"i":[[0,0],[0.7128,-2.1956],[1.3574,-1.8678],[1.8678,-1.3574],[2.1956,-0.7128],[2.3078,0],[2.1956,0.7128],[1.8678,1.3574],[1.3574,1.8678],[0.7128,2.1956],[0,2.3078],[-0.7128,2.1956],[-1.3574,1.8678],[-1.8678,1.3574],[-2.1956,0.7128],[-2.3078,0],[-2.1956,-0.7128],[-1.8678,-1.3574],[-1.3574,-1.8678],[-0.7128,-2.1956],[0,-2.3078],[0,0]],"o":[[0,2.3078000000000003],[-0.7128000000000014,2.195599999999999],[-1.3573999999999984,1.8678000000000026],[-1.8678000000000026,1.3573999999999984],[-2.195599999999999,0.7128000000000014],[-2.3078000000000003,0],[-2.1956000000000007,-0.7128000000000014],[-1.8678,-1.3573999999999984],[-1.3574000000000002,-1.8678000000000026],[-0.7128,-2.195599999999999],[0,-2.3078000000000003],[0.7128000000000001,-2.1956000000000007],[1.3574000000000002,-1.8678],[1.867799999999999,-1.3574000000000002],[2.1956000000000007,-0.7128],[2.3078000000000003,0],[2.195599999999999,0.7128000000000001],[1.8678000000000026,1.3574000000000002],[1.3573999999999984,1.867799999999999],[0.7128000000000014,2.1956000000000007],[0,0],[0,0]]}}},{"ty":"st","o":{"a":0,"k":100},"w":{"a":0,"k":2},"c":{"a":0,"k":[0.615686274509804,0.8117647058823529,0,1]},"ml":4,"lc":1,"lj":1,"nm":"Stroke","hd":false,"d":[{"n":"o","nm":"Offset","v":{"a":0,"k":0}},{"n":"d","nm":"Dash","v":{"a":0,"k":3}},{"n":"g","nm":"Gap","v":{"a":0,"k":3}}]},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]},{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"rc","nm":"Rectangle","hd":false,"p":{"a":0,"k":[22,22]},"s":{"a":0,"k":[48,48]},"r":{"a":0,"k":0}},{"ty":"fl","o":{"a":0,"k":0},"c":{"a":0,"k":[0,1,0,1]},"nm":"Fill","hd":false,"r":1},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]}]}]}],"layers":[{"ddd":0,"ty":0,"nm":"circleatroke2","refId":"lgdm1qe5a8umik1bb6b","sr":1,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"ao":0,"w":46,"h":46,"ip":0,"op":300,"st":0,"hd":false}],"meta":{"a":"","d":"","tc":"","g":"Aninix"}}',
					},
					params: { loopMode: 'loop' },
					autoPlay: true,
				},
			);
		}

		renderUncompletedLottieView()
		{
			return LottieView(
				{
					style: this.styles.lottie,
					data: {
						content: '{"nm":"circleatroke2","v":"5.9.6","fr":60,"ip":0,"op":300,"w":46,"h":46,"ddd":0,"markers":[],"assets":[{"nm":"[FRAME] circleatroke2 - Null / ? circleatroke2 - Null / ? circleatroke2 - Stroke","fr":60,"id":"lgdm1qe5a8umik1bb6b","layers":[{"ty":3,"ddd":0,"ind":6694,"hd":false,"nm":"circleatroke2 - Null","ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":300,"bm":0,"sr":1},{"ty":3,"ddd":0,"ind":72800,"hd":false,"nm":"? circleatroke2 - Null","parent":6694,"ks":{"a":{"a":0,"k":[22,22]},"o":{"a":0,"k":100},"p":{"a":0,"k":[23,23]},"r":{"a":1,"k":[{"t":0,"s":[0],"o":{"x":[0],"y":[0]},"i":{"x":[1],"y":[1]}},{"t":300,"s":[360]}]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":300,"bm":0,"sr":1},{"ddd":0,"ind":68357,"hd":false,"nm":"? circleatroke2 - Stroke","parent":72800,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"st":0,"ip":0,"op":300,"bm":0,"sr":1,"ty":4,"shapes":[{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[44,22],[42.9242,28.798],[39.798,34.9316],[34.9316,39.798],[28.798,42.9242],[22,44],[15.202,42.9242],[9.0684,39.798],[4.202,34.9316],[1.0758,28.798],[0,22],[1.0758,15.202],[4.202,9.0684],[9.0684,4.202],[15.202,1.0758],[22,0],[28.798,1.0758],[34.9316,4.202],[39.798,9.0684],[42.9242,15.202],[44,22],[44,22]],"i":[[0,0],[0.7128,-2.1956],[1.3574,-1.8678],[1.8678,-1.3574],[2.1956,-0.7128],[2.3078,0],[2.1956,0.7128],[1.8678,1.3574],[1.3574,1.8678],[0.7128,2.1956],[0,2.3078],[-0.7128,2.1956],[-1.3574,1.8678],[-1.8678,1.3574],[-2.1956,0.7128],[-2.3078,0],[-2.1956,-0.7128],[-1.8678,-1.3574],[-1.3574,-1.8678],[-0.7128,-2.1956],[0,-2.3078],[0,0]],"o":[[0,2.3078000000000003],[-0.7128000000000014,2.195599999999999],[-1.3573999999999984,1.8678000000000026],[-1.8678000000000026,1.3573999999999984],[-2.195599999999999,0.7128000000000014],[-2.3078000000000003,0],[-2.1956000000000007,-0.7128000000000014],[-1.8678,-1.3573999999999984],[-1.3574000000000002,-1.8678000000000026],[-0.7128,-2.195599999999999],[0,-2.3078000000000003],[0.7128000000000001,-2.1956000000000007],[1.3574000000000002,-1.8678],[1.867799999999999,-1.3574000000000002],[2.1956000000000007,-0.7128],[2.3078000000000003,0],[2.195599999999999,0.7128000000000001],[1.8678000000000026,1.3574000000000002],[1.3573999999999984,1.867799999999999],[0.7128000000000014,2.1956000000000007],[0,0],[0,0]]}}},{"ty":"st","o":{"a":0,"k":100},"w":{"a":0,"k":2},"c":{"a":0,"k":[0.3333333333333333,0.8156862745098039,0.8784313725490196,1]},"ml":4,"lc":1,"lj":1,"nm":"Stroke","hd":false,"d":[{"n":"o","nm":"Offset","v":{"a":0,"k":0}},{"n":"d","nm":"Dash","v":{"a":0,"k":3}},{"n":"g","nm":"Gap","v":{"a":0,"k":3}}]},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]},{"ty":"gr","nm":"Group","hd":false,"np":3,"it":[{"ty":"rc","nm":"Rectangle","hd":false,"p":{"a":0,"k":[22,22]},"s":{"a":0,"k":[48,48]},"r":{"a":0,"k":0}},{"ty":"fl","o":{"a":0,"k":0},"c":{"a":0,"k":[0,1,0,1]},"nm":"Fill","hd":false,"r":1},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]}]}]}],"layers":[{"ddd":0,"ty":0,"nm":"circleatroke2","refId":"lgdm1qe5a8umik1bb6b","sr":1,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"ao":0,"w":46,"h":46,"ip":0,"op":300,"st":0,"hd":false}],"meta":{"a":"","d":"","tc":"","g":"Aninix"}}',
					},
					params: { loopMode: 'loop' },
					autoPlay: true,
				},
			);
		}
	}

	module.exports = { ProgressBarNumber };
});
