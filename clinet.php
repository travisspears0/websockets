<input id="msg" />
<input type="button" id="btn" value="send" />

<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script>
$(document).ready(function(){

	var conn = new WebSocket('ws://127.0.0.1:1234');
	var connected = false;
	$("#btn").attr('disabled',true);
	$("#msg").val("");
	$("#msg").focus();

	conn.onopen = function(e) {
		console.log(e);
		$("#btn").attr('disabled',false);
		connected = true;
	};

	conn.onmessage = function(e) {
		console.log(e.data);
	};

	conn.onclose = function(e) {
		console.log(e);
		$("#btn").attr('disabled',true);
		connected = false;
	}

	$("#btn").click(function(){
		var msg = $("#msg").val();
		if( msg.length || true ) {
			conn.send( msg );
			console.log( "Sending: " + msg );
		}
	});

	$("#msg").keydown(function(e){
		var code = e.keyCode || e.which;
		if( code === 13 && connected ) {
			$("#btn").click();
		}
	});

	function write(msg) {
		console.log(msg);
	}

});
</script>