 liveAppCtrl
.controller('streamCtrl', ['$scope', '$http', '$rootScope', '$window', 'socketFactory', '$location', '$sce',
	function ($scope, $http, $rootScope, $window, socketFactory, $location, $sce) {
			
		$scope = $rootScope;

		function getBrowser() {

            // Opera 8.0+
            var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

            // Firefox 1.0+
            var isFirefox = typeof InstallTrigger !== 'undefined';

            // Safari 3.0+ "[object HTMLElementConstructor]" 
            var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || safari.pushNotification);

            // Internet Explorer 6-11
            var isIE = /*@cc_on!@*/false || !!document.documentMode;

            // Edge 20+
            var isEdge = !isIE && !!window.StyleMedia;

            // Chrome 1+
            var isChrome = (!!window.chrome && !!window.chrome.webstore) || navigator.userAgent.indexOf("Chrome") !== -1;

            // Blink engine detection
            var isBlink = (isChrome || isOpera) && !!window.CSS;

            var b_n = '';

            switch(true) {

                case isFirefox :

                        b_n = "Firefox";

                        break;
                case isChrome :

                        b_n = "Chrome";

                        break;

                case isSafari :

                        b_n = "Safari";

                        break;
                case isOpera :

                        b_n = "Opera";

                        break;

                case isIE :

                        b_n = "IE";

                        break;

                case isEdge : 

                        b_n = "Edge";

                        break;

                case isBlink : 

                        b_n = "Blink";

                        break;

                default :

                        b_n = "Unknown";

                        break;

            }

            return b_n;

        }

        var mobile_type = "";

        function getMobileOperatingSystem() {

		  var userAgent = navigator.userAgent || navigator.vendor || window.opera;

		  if( userAgent.match( /iPad/i ) || userAgent.match( /iPhone/i ) || userAgent.match( /iPod/i ) )
		  {
		    mobile_type =  'ios';

		  }
		  else if( userAgent.match( /Android/i ) )
		  {

		    mobile_type =  'andriod';
		  }
		  else
		  {
		    mobile_type =  'unknown'; 
		  }

		  return mobile_type;
		
		}

        var browser = getBrowser();

        var m_type = getMobileOperatingSystem();

		$scope.user_id =  $scope.videoDetails.user_id;


		window.enableAdapter = false; // enable adapter.js

		// $("#room-id").val('1auji7mmo5k4916tnu4t');


	    $scope.socket_url = socket_url;

	   // alert($scope.socket_url);

	    $scope.connectionNow= null;

		var connection = new RTCMultiConnection();

		// by default, socket.io server is assumed to be deployed on your own URL
		// connection.socketURL = '/';
		connection.socketURL = $scope.socket_url;

		// comment-out below line if you do not have your own socket.io server
		// connection.socketURL = 'https://rtcmulticonnection.herokuapp.com:443/';

		connection.socketMessageEvent = 'video-broadcast-demo';

		$scope.connectionNow = connection;

		connection.session = {
		    audio: true,
		    video: true,
		    oneway: true
		};

		connection.sdpConstraints.mandatory = {
		    OfferToReceiveAudio: false,
		    OfferToReceiveVideo: false
		};

		connection.videosContainer = document.getElementById('videos-container');

		var append_already = 0;
		
		connection.onstream = function(event) {

			$("#loader_btn").hide();

		    event.mediaElement.removeAttribute('src');
		    event.mediaElement.removeAttribute('srcObject');

		    var video = document.createElement('video');
		    video.controls = true;
		    if(event.type === 'local') {
		        video.muted = true;
		    }
		    video.srcObject = event.stream;

		    var width = parseInt(connection.videosContainer.clientWidth / 2) - 20;
		    var mediaElement = getHTMLMediaElement(video, {
		        title: event.userid,
		        buttons: ['full-screen'],
		        width: width,
		        showOnMouseEnter: false
		    });

		    if (append_already == 0) { 

      			connection.videosContainer.appendChild(mediaElement);

      			//if (browser == 'Safari' || m_type =='ios') {

      				append_already = 1;

      			//}

    		}

		    setTimeout(function() {
		        mediaElement.media.play();
		    }, 5000);

		    mediaElement.id = event.streamid;


		};

		connection.onstreamended = function(event) {
		    var mediaElement = document.getElementById(event.streamid);
		    if (mediaElement) {
		        mediaElement.parentNode.removeChild(mediaElement);
		    }

		    setTimeout(() => {

		    	window.location.reload(true);

		    }, 2000)
		    
		};

		function disableInputButtons() {
		    document.getElementById('open-or-join-room').disabled = true;
		    document.getElementById('open-room').disabled = true;
		    document.getElementById('join-room').disabled = true;
		    document.getElementById('room-id').disabled = true;
		}

		// ......................................................
		// ......................Handling Room-ID................
		// ......................................................


		/*function showRoomURL(roomid) {
		    var roomHashURL = '#' + roomid;
		    var roomQueryStringURL = '?roomid=' + roomid;

		    var html = '<h2>Unique URL for your room:</h2><br>';

		    html += 'Hash URL: <a href="' + roomHashURL + '" target="_blank">' + roomHashURL + '</a>';
		    html += '<br>';
		    html += 'QueryString URL: <a href="' + roomQueryStringURL + '" target="_blank">' + roomQueryStringURL + '</a>';

		    var roomURLsDiv = document.getElementById('room-urls');
		    roomURLsDiv.innerHTML = html;

		    roomURLsDiv.style.display = 'block';
		}*/

		(function() {
		    var params = {},
		        r = /([^&=]+)=?([^&]*)/g;

		    function d(s) {
		        return decodeURIComponent(s.replace(/\+/g, ' '));
		    }
		    var match, search = window.location.search;
		    while (match = r.exec(search.substring(1)))
		        params[d(match[1])] = d(match[2]);
		    window.params = params;
		})();

		var roomid = '';

		roomid = $scope.videoDetails.unique_id;


		/*var roomid = params.roomid;
		if (!roomid && hashString.length) {
		    roomid = hashString;
		}*/

		if (roomid && roomid.length) {

		    localStorage.setItem(connection.socketMessageEvent, roomid);

		    // auto-join-room
		    (function reCheckRoomPresence() {
		        connection.checkPresence(roomid, function(isRoomExist) {
		            if (isRoomExist) {
		                connection.sdpConstraints.mandatory = {
		                    OfferToReceiveAudio: true,
		                    OfferToReceiveVideo: true
		                };
		                connection.join(roomid);
		                return;
		            }

		            setTimeout(reCheckRoomPresence, 5000);
		        });
		    })();

		   // disableInputButtons();
		}



	}
]);

