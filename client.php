<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>socket client</title>
	</head>
	<body ng-app="app">

		<div ng-controller="controller">
			<input id="msg" ng-model="currentMessage" auto-focus ng-keydown="keydown($event)" />
			<input type="button" id="btn" value="send" ng-click="send()" />
			<hr>
			<div id="messages">
				<p ng-repeat="msg in messages">
					[{{ msg.date }}]<strong>{{ msg.author }}</strong>: {{ msg.message }}
				</p>
			</div>

			<div id="users">
				Users Online:<br>
				<ul>
					<li ng-repeat="user in usersOnline">[{{ user.id }}]{{ user.name }}</li>
				</ul>
			</div>

		</div>

		<div id="scripts">
			<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js"></script>
			<script>

				(function(){

					var app = angular.module('app',[]);
					
					app.controller('controller',function($scope){

						var conn = new WebSocket('ws://127.0.0.1:1234');
						$scope.connected = false;

						$scope.messages = [];
						$scope.maxMsg = 5;

						$scope.usersOnline = [];

						$scope.currentMessage = '';

						$scope.send = function() {
							conn.send( $scope.currentMessage );
							$scope.currentMessage = "";
						}

						$scope.keydown = function($event){
							var code = $event.keyCode || $event.which;
							if( code === 13 && $scope.connected ) {
								$scope.send();
							}
						};

						conn.onopen = function(e) {
							console.log(e);
							$scope.connected = true;
							$scope.writeMessage("connected!");
						};

						conn.onmessage = function(e) {
							console.log(e.data);
							$scope.writeMessage(e.data);
						};

						conn.onclose = function(e) {
							console.log(e);
							$scope.connected = false;
							$scope.writeMessage("disconnected!");
							$scope.usersOnline = [];
							//$scope.messages = [];
							$scope.$apply();
						}

						$scope.writeMessage = function(msg) {
							var message,date,author,type;
							try {
								message = JSON.parse(msg);
							console.log( message );
								date = message["date"];
								author = message["author"];
								type = message["type"];
								message = message["message"];
							} catch(e) {
								message = msg;
								var d = new Date();
								date = new Date(d.getTime()-d.getTimezoneOffset()*60*1000).toJSON().replace("T"," ").split(".")[0];
								author = 'CLIENT';
								type = 'MESSAGE';
							}
							switch( type ) {
								case 'MESSAGE': {
									msg = {message:message,date:date,author:author,type:type};
									$scope.messages.push(msg);
									if( $scope.messages.length > $scope.maxMsg ) {
										$scope.messages.shift();
									}
									break;
								}
								case 'USER_CONNECTED': {
									var id,name;
									message = JSON.parse(message);
									id = message['id'];
									name = message['name'];
									$scope.usersOnline[$scope.usersOnline.length] = {id:id,name:name};
									break;
								}
								case 'USER_DISCONNECTED': {
									var id;
									message = JSON.parse(message);
									id = message['id'];
									for( var i in $scope.usersOnline ) {
										if( $scope.usersOnline[i]['id'] === id ) {
											$scope.usersOnline.splice(i,1);
										}
									}
									break;
								}
								case 'LIST_OF_USERS': {
									message = JSON.parse(message);
									for( var i in message ) {
										var id = message[i]['id'];
										var name = message[i]['name'];
										$scope.usersOnline[$scope.usersOnline.length] = {id:id,name:name};
									}
									break;
								}
							}
							$scope.$apply();
						}
					});

					app.directive('autoFocus', function($timeout) {
					    return {
					        restrict: 'AC',
					        link: function(_scope, _element) {
					            $timeout(function(){
					                _element[0].focus();
					            }, 0);
					        }
					    };
					});
	
				})();

			</script>
		</div>

	</body>
</html>