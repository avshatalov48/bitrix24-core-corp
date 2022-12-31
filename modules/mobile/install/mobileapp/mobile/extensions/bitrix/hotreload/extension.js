(function() {


	let startHotReload = function(id, host)
	{
		let socket = new WebSocket(host);
		socket.id = id;
		socket.onopen = (function () {
			console.log("connected");
			setTimeout(()=>{
				dialogs.showSnackbar({
					title:"connected to socket",
					id:"reloading",
					backgroundColor:"#20ea43",
					textColor:"#181818",
					hideOnTap:true,
					autoHide:true}, ()=>{});
			}, 1000)
			this.send(JSON.stringify({id: this.id, command: "register"}))
			if(window.component)
				this.send(JSON.stringify({id: this.id, command: "watch", path: component.path}))
		}).bind(socket);

		let reloadTimeout = null
		socket.onmessage = mess => {
			try
			{
				// console.log("Socket message: "+mess.data);
				let obj = JSON.parse(mess.data)

				if (obj.command) {
					if(obj.command === "change") {
						dialogs.showSnackbar({
							title:"reloading...",
							id:"reloading",
							backgroundColor:"#AA333333",
							textColor:"#ffffff",
							hideOnTap:true,
							autoHide:true}, ()=>{});

						clearTimeout(reloadTimeout)
						reloadTimeout = setTimeout(()=>reload(), 1000)
					}
					else
						socket.oncommand(obj.command, obj.data)
				}
			}
			catch (e)
			{

			}

		};
		socket.sendToClient = (function (id, message) {
			socket.send(JSON.stringify({to: id, from: this.id, body: message, command: "message"}))
		}).bind(socket)

		return socket;
	};


	window.startHotReload = startHotReload
	console.log("hotreload");
})();
