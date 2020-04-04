/**
 * Usage:
 * var rec = new BX.CrmRecorder({element: BX('elementId')});
 * rec.start();
 * rec.stop();
 *
 * Events:
 * 'unsupported': Browser is not supported
 * 'deviceFailure':
 * 'deviceListReady':
 * 'recordStarted':
 * 'recordPaused':
 * 'recordUnpaused':
 * 'recordFinished':
 * 'recordFailure':
  */

(function()
{
	var micSvg ='<svg xmlns="http://www.w3.org/2000/svg" width="13" height="19" viewBox="0 0 13 19"><path fill="#FFFFFF" fill-rule="evenodd" d="M251.355405,23.7267983 C253.038066,23.7266465 254.402051,22.3680746 254.402051,20.6922437 L254.402051,15.6218277 C254.402124,14.817039 254.081172,14.0451875 253.509807,13.4760902 C252.938442,12.9069929 252.163474,12.5872731 251.355405,12.5872731 C250.547289,12.5872002 249.77225,12.9068878 249.200826,13.475993 C248.629401,14.0450982 248.308411,14.8169914 248.308484,15.6218277 L248.308484,20.6925185 C248.308484,21.4973071 248.629507,22.2691297 249.200923,22.8381755 C249.77234,23.4072212 250.547337,23.7268712 251.355405,23.7267983 L251.355405,23.7267983 Z M245.107036,19.1004903 L245.107036,20.5644527 C245.105683,23.5323492 247.209461,26.0879428 250.131891,26.668464 L250.131891,28.7741294 C249.324768,29.0140467 248.414444,29.758807 248.414444,30.6404273 L254.295263,30.6404273 C254.295263,29.7585322 253.384663,29.0140467 252.577816,28.7741294 L252.577816,26.668464 C255.501098,26.0890873 257.60578,23.5329576 257.603775,20.5644527 L257.603775,19.1004903 C257.603775,18.2065032 256.876123,18.1111409 255.978492,18.1111409 L255.978492,20.5636282 C255.978492,23.1065253 253.908666,25.1679507 251.355405,25.1679507 C248.802145,25.1679507 246.732319,23.1065253 246.732319,20.5636282 L246.732319,18.1111409 C245.834688,18.1111409 245.107036,18.2065032 245.107036,19.1004903 Z" transform="translate(-245 -12)"/></svg>';
	var configMicrophone = 'bx-crm-recorder-default-microphone';

	var stopMediaStream = function(mediaStream)
	{
		if(!(mediaStream instanceof MediaStream))
			return;

		if (typeof mediaStream.getTracks === 'undefined')
		{
			// Support for legacy browsers
			mediaStream.stop();
		}
		else
		{
			mediaStream.getTracks().forEach(function(track)
			{
				track.stop();
			});
		}
	};

	var events = {
		unsupported: 'unsupported',
		deviceReady: 'deviceReady',
		deviceListReady: 'deviceListReady',
		deviceFailure: 'deviceFailure',
		stateChanged: 'stateChanged'
	};

	var states = {
		idle: 'idle',
		failure: 'failure',
		recording: 'recording',
		paused: 'paused'
	};

	var lastFrameDate = (new Date()).getTime();

	BX.CrmRecorder = function(config)
	{
		var self = this;
		this.elements = {
			main: config.element,
			container: null,
			canvas: null
		};
		
		this.callbacks = {
			stop: nop
		};

		this.state = states.idle;
		this.microphones = {};
		this.defaultMicrophone = this.__getDefaultMicrophone();
		this.actualDeviceList = false;
		this.mediaStream = null;
		this.recorder = null;
		this.record = null;

		// webaudio objects
		this.audioContext = null;
		this.analyserNode = null;
		this.mediaStreamNode = null;

		this.frequencyData = null;

		this.canvasContext = null;
		this.mic = {
			image: new Image('data:image/svg+xml,' + micSvg),
			loaded: false
		};

		this.mic.image.onload = function()
		{
			self.mic.loaded = true;
		};
		this.mic.image.src = URL.createObjectURL(new Blob([micSvg], {type: 'image/svg+xml'}));
		this.init();
	};
	BX.CrmRecorder.prototype.start = function()
	{
		if(!BX.CrmRecorder.isSupported())
		{
			BX.onCustomEvent(this, events.unsupported, []);
			return false;
		}

		var self = this;
		var microphonesCount = 0;
		navigator.mediaDevices.enumerateDevices().then(function(devices)
		{
			devices.forEach(function(device)
			{
				if(device.kind != 'audioinput')
					return;

				self.microphones[device.deviceId] = device.label;
				microphonesCount++;
			});
			if(microphonesCount == 0)
			{
				BX.onCustomEvent(self, events.deviceFailure, {});
			}
			else
			{
				self.getMediaStream();
			}
		});
	};
	BX.CrmRecorder.prototype.pause = function()
	{
		if(this.recorder && this.state === states.recording)
		{
			this.recorder.pause();
			this.__setState(states.paused);
		}
	};
	BX.CrmRecorder.prototype.resume = function ()
	{
		if(this.recorder && this.state === states.paused)
		{
			this.recorder.resume();
			this.__setState(states.recording);
		}
	};
	BX.CrmRecorder.prototype.stop = function(callback)
	{
		if(!this.recorder)
			return false;

		this.recorder.stop();
		stopMediaStream(this.mediaStream);

		if(BX.type.isFunction(callback))
			this.callbacks.stop = callback;
		else 
			this.callbacks.stop = nop;
	};
	BX.CrmRecorder.prototype.init = function()
	{
		this.__createLayout();
		this.__bindEvents();

		//console.log(this.elements.canvas.clientWidth, ' x ', this.elements.canvas.clientHeight);
		//this.elements.canvas.width = this.elements.canvas.clientWidth;
		//this.elements.canvas.height = this.elements.canvas.clientHeight;
		this.elements.canvas.width = 502;
		this.elements.canvas.height = 43;

		this.canvasContext = this.elements.canvas.getContext('2d');
		this.canvasContext.imageSmoothingEnabled = false;
		this.canvasContext.mozImageSmoothingEnabled = false;
		this.canvasContext.webkitImageSmoothingEnabled = false;
	};
	BX.CrmRecorder.prototype.getMediaStream = function()
	{
		var self = this;

		navigator.mediaDevices.getUserMedia(self.__getConstraints()).then(function(stream)
		{
			self.mediaStream = stream;
			if(self.recorder)
			{
				self.recorder.replaceStream(self.mediaStream);
			}
			else
			{
				self.recorder = new BX.Recorder(stream);
				BX.addCustomEvent(self.recorder, 'stop', self.__onRecorderStopped.bind(self));
				self.recorder.start();
			}

			self.__attachAnalyser();
			self.__visualize();
			self.__updateDeviceList();
			BX.onCustomEvent(self, events.deviceReady, [self]);
		}).catch(function(error)
		{
			BX.onCustomEvent(self, events.deviceFailure, [error]);
		});
	};
	BX.CrmRecorder.prototype.changeMicrophone = function(microphoneId)
	{
		this.defaultMicrophone = microphoneId;
		this.__setDefaultMicrophone(microphoneId);
		this.__detachAnalyser();
		stopMediaStream(this.mediaStream);
		this.mediaStream = null;
		this.getMediaStream();
	};
	BX.CrmRecorder.prototype.dispose = function()
	{
		if(this.analyserNode)
		{
			this.analyserNode.disconnect();
			this.analyserNode = null;
		}

		if(this.mediaStreamNode)
		{
			this.mediaStreamNode.disconnect();
			this.analyserNode = null;
		}

		if(this.audioContext)
		{
			this.audioContext.close();
			this.audioContext = null;
		}

		if(this.mediaStream)
		{
			stopMediaStream(this.mediaStream);
			this.mediaStream = null;
		}
		if(this.recorder)
		{
			this.recorder.dispose();
			this.recorder = null;
		}
	};
	BX.CrmRecorder.prototype.__setState = function(newState)
	{
		var event = {
			oldState: this.state,
			newState: newState
		};
		this.state = newState;
		BX.onCustomEvent(this, events.stateChanged, [event]);
	};
	BX.CrmRecorder.prototype.__createLayout = function()
	{
		this.elements.container = BX.create('div', {props: {className: 'crm-recorder-analyser-container'}, children: [
			this.elements.canvas = BX.create('canvas', {props: {className: 'crm-recorder-analyser-canvas'}})
		]});
		this.elements.main.appendChild(this.elements.container);
	};
	BX.CrmRecorder.prototype.__bindEvents = function()
	{

	};
	BX.CrmRecorder.prototype.__visualize = function()
	{
		if(!this.analyserNode)
			return;

		window.requestAnimationFrame(this.__visualize.bind(this));

		var now = (new Date()).getTime();

		if(now - lastFrameDate < 50)
		{
			return;
		}

		lastFrameDate = now;

		this.analyserNode.getByteFrequencyData(this.frequencyData);
		//this.analyserNode.getFloatFrequencyData(this.frequencyData);

		var width = this.elements.canvas.width;
		var height = this.elements.canvas.height;
		var frequencyPoints = this.analyserNode.frequencyBinCount;

		this.canvasContext.clearRect(0, 0, width, height);
		this.canvasContext.beginPath();

		var barWidth = 2;
		var barHeight;
		var x = 0;

		var middlePoint = Math.ceil(width / 2);

		if(this.mic.loaded)
		{
			this.canvasContext.drawImage(this.mic.image, middlePoint - 5, 13);
		}

		this.canvasContext.fillStyle = '#afb2b7';
		for(var i = 0; i < frequencyPoints; i++)
		{
			barHeight = Math.round(this.frequencyData[i] * height / 256);
			//barHeight = Math.round(this.frequencyData[i] + 80);
			if(barHeight < 3)
				barHeight = 3;

			x = middlePoint + 17 + (barWidth + 2) * i;
			this.canvasContext.fillRect(x, (height - barHeight) / 2 , barWidth, barHeight);
			x = middlePoint - 17 - (barWidth + 2) * i;
			this.canvasContext.fillRect(x, (height - barHeight) / 2 , barWidth, barHeight);
		}
		this.canvasContext.closePath();
	};
	BX.CrmRecorder.prototype.__getDefaultMicrophone = function()
	{
		return localStorage.getItem(configMicrophone) || '';
	};
	BX.CrmRecorder.prototype.__setDefaultMicrophone = function(microphoneId)
	{
		localStorage.setItem(configMicrophone, microphoneId);
	};
	BX.CrmRecorder.prototype.__getConstraints = function()
	{
		var result = {
			audio: {},
			video: false
		};
		
		if(this.defaultMicrophone != '')
		{
			if(BX.browser.IsChrome())
			{
				result.audio.mandatory = {sourceId: this.defaultMicrophone}
			}
			else
			{
				result.audio.deviceId = {exact: this.defaultMicrophone}
			}
		}
		return result;
	};
	BX.CrmRecorder.prototype.__attachAnalyser = function()
	{
		if(!this.mediaStream)
			return false;

		if(!this.audioContext)
			this.audioContext = new (window.AudioContext || window.webkitAudioContext);

		if(!this.analyserNode)
		{
			this.analyserNode = this.audioContext.createAnalyser();
			this.analyserNode.fftSize = 128;
			this.analyserNode.minDecibels = -80;
			this.analyserNode.maxDecibels = -10;
		}

		if(!this.mediaStreamNode)
		{
			this.mediaStreamNode = this.audioContext.createMediaStreamSource(this.mediaStream);
			this.mediaStreamNode.connect(this.analyserNode);
		}

		this.frequencyData = new Uint8Array(this.analyserNode.frequencyBinCount);
		//this.frequencyData = new Float32Array(this.analyserNode.frequencyBinCount);
	};
	BX.CrmRecorder.prototype.__detachAnalyser = function()
	{
		if(this.mediaStreamNode)
		{
			this.mediaStreamNode.disconnect();
			this.mediaStreamNode = null;
		}
	};
	BX.CrmRecorder.prototype.__onRecorderStopped = function(record)
	{
		this.record = record;
		this.callbacks.stop(record);
	};
	BX.CrmRecorder.prototype.__updateDeviceList = function()
	{
		var self = this;

		if(this.actualDeviceList)
			return;

		navigator.mediaDevices.enumerateDevices().then(function(devices)
		{
			devices.forEach(function(device)
			{
				if (device.kind != 'audioinput')
					return;

				self.microphones[device.deviceId] = device.label;
			});
		});
		BX.onCustomEvent(this, events.deviceListReady, [this.microphones]);
		this.actualDeviceList = true;
	};
	BX.CrmRecorder.isSupported = function()
	{
		return (
			BX.Recorder.isSupported()
			&& typeof(window.Promise) !== 'undefined'
			&& typeof(window.localStorage) !== 'undefined'
		);
	};

	var nop = function(){};
})();

