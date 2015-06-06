<input id="msg" />
<input type="button" id="btn" value="send" />

<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script>
$(document).ready(function(){

	var conn = new WebSocket('ws://localhost:8080');

	conn.onopen = function(e) {
		console.log(e);
	};

	conn.onmessage = function(e) {
		console.log( "[SERVER]: " + e.data );
	};

	conn.onclose = function(e) {
		console.log(e);
	}

	$("#btn").click(function(){
		var msg = $("#msg").val();
		if( msg.length ) {
			conn.send( msg );
		}
	});

	function write(msg) {
		console.log(msg);
	}

	$("#name").click(function(){
		window.location = $(this).data('redirect');
	});

});
</script>