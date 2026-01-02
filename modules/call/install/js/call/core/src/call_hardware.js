import { HardwareManager } from '../../lib/hardware/src/hardware.js'

const lsKey = {
	enableMicAutoParameters: 'bx-im-settings-enable-mic-auto-parameters',
	preferHd: 'bx-im-settings-camera-prefer-hd',
	enableMirroring: 'bx-im-settings-camera-enable-mirroring',
};

const CallEvents = {
	onChangeMirroringVideo: 'onChangeMirroringVideo',
	onChangeMicrophoneMuted: 'onChangeMicrophoneMuted',
	onChangeCameraOn: 'onChangeCameraOn',
};

class CallHardwareManager extends HardwareManager
{
	constructor()
	{
		super();

		this._isCameraOn = false;
		this._isMicrophoneMuted = false;
		this.Events = Object.assign(this.Events, CallEvents);
	}

	get enableMicAutoParameters(): boolean
	{
		return localStorage ? (localStorage.getItem(lsKey.enableMicAutoParameters) !== 'N') : true;
	}

	set enableMicAutoParameters(enableMicAutoParameters: boolean)
	{
		if (localStorage)
		{
			localStorage.setItem(lsKey.enableMicAutoParameters, enableMicAutoParameters ? 'Y' : 'N')
		}
	}

	get preferHdQuality(): boolean
	{
		return localStorage ? (localStorage.getItem(lsKey.preferHd) !== 'N') : true;
	}

	set preferHdQuality(preferHdQuality: boolean)
	{
		if (localStorage)
		{
			localStorage.setItem(lsKey.preferHd, preferHdQuality ? 'Y' : 'N')
		}
	}

	get enableMirroring(): boolean
	{
		return localStorage ? (localStorage.getItem(lsKey.enableMirroring) !== 'N') : true;
	}

	set enableMirroring(enableMirroring: boolean)
	{
		if (this.enableMirroring !== enableMirroring)
		{
			this.emit(this.Events.onChangeMirroringVideo, {enableMirroring: enableMirroring});

			if (DesktopApi.isDesktop())
			{
				DesktopApi.emit(this.Events.onChangeMirroringVideo, [enableMirroring]);
			}
			if (localStorage)
			{
				localStorage.setItem(lsKey.enableMirroring, enableMirroring ? 'Y' : 'N');
			}
		}
	}

	get isCameraOn()
	{
		return this._isCameraOn;
	}

	set isCameraOn(isCameraOn)
	{
		if (this._isCameraOn !== isCameraOn)
		{
			this._isCameraOn = isCameraOn;
			this.emit(this.Events.onChangeCameraOn, {isCameraOn: this._isCameraOn});
		}
	}
	
	/* 
	
	the setter 'isCameraOn' is duplicated by that function  
	to emit additional params in 'onChangeCameraOn' event
	
	for task-565624 off all participants mic/cam/screenshare
	
	*/
	
	setIsCameraOn(options) 
	{
		if (this._isCameraOn !== options.isCameraOn)
		{
			this._isCameraOn = options.isCameraOn;
			this.emit(this.Events.onChangeCameraOn, options);
		}
	}

	get isMicrophoneMuted()
	{
		return this._isMicrophoneMuted;
	}

	set isMicrophoneMuted(isMicrophoneMuted)
	{
		if (this._isMicrophoneMuted !== isMicrophoneMuted)
		{
			this._isMicrophoneMuted = isMicrophoneMuted;
			this.emit(this.Events.onChangeMicrophoneMuted, {isMicrophoneMuted: this._isMicrophoneMuted});
		}
	}
	
	/* 
	
	the setter 'isMicrophoneMuted' is duplicated by that function  
	to emit additional params in 'onChangeMicrophoneMuted' event
	
	for task-565624 off all participants mic/cam/screenshare
	
	*/
	
	setIsMicrophoneMuted(options) 
	{
		if (this._isMicrophoneMuted !== options.isMicrophoneMuted)
		{
			this._isMicrophoneMuted = options.isMicrophoneMuted;
			this.emit(this.Events.onChangeMicrophoneMuted, options);
		}
	}
}

export const Hardware = new CallHardwareManager();
