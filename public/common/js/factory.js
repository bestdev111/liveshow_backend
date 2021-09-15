angular.module('liveApp')

.factory('commonHelper', function($location) {
	return {
		stringRepeat: function(num, replace) {
			return new Array(num + 1).join(replace);
		},
		externalLinks:function(text){
		return String(text).replace(/href=/gm, "class=\"ex-link\" href=");
		
		},
		localStorageIsEnabled: function() {
			var uid = new Date(),
							result;

			try {
				localStorage.setItem("uid", uid);
				result = localStorage.getItem("uid") === uid;
				localStorage.removeItem("uid");
				return result && localStorage;
			} catch (e) {
			}
		},
		readJsonFromController: function(file) {
			var request = new XMLHttpRequest();
			request.open('GET', file, false);
			request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			request.send(null);
			try {
				return JSON.parse(request.responseText);
			} catch (e) {
				return '';
			}
		},
		getBadWords: function(input) {
			if (input) {
				var badwords = [];
				for (var i = 0; i < swearwords.length; i++) {
					var swear = new RegExp(swearwords[i], 'g');
					if (input.match(swear)) {
						badwords.push(swearwords[i]);
					}
				}
				return badwords;
			}
		},
		replaceBadWords: function(input) {
			if (this.localStorageIsEnabled()) {
				if (localStorage.getItem('localSwears') === null) {
					// stringify the array so that it can be stored in local storage
					localStorage.setItem('localSwears', JSON.stringify(readJsonFromController(swearWordPath)));
				}
				swearwords = JSON.parse(localStorage.getItem('localSwears'));
			} else {
				swearwords = this.readJsonFromController(swearWordPath);
			}
			if (swearwords === null) {
				return input;
			}
			if (input) {
				for (var i = 0; i < swearwords.length; i++) {
					var swear =  new RegExp('\\b' + swearwords[i] + '\\b', 'gi');
					if (input.match(swear)) {
						var replacement = this.stringRepeat(swearwords[i].length, "*");
						input = input.replace(swear, replacement);
					}
				}
				return input;
			} else {
				return input;
			}
		},
		obToquery: function(obj, prefix) {
			var str = [];
			for (var p in obj) {
				var k = prefix ? prefix + "[" + p + "]" : p,
								v = obj[k];
				str.push(angular.isObject(v) ? this.obToquery(v, k) : (k) + "=" + encodeURIComponent(v));
			}
			return str.join("&");
		},
		isExpired: function(object) {
			if (!object.expiresOn) {
				return false;
			}
			if (new Date(object.expiresOn).getTime() < new Date().getTime() && object.expiresOn) {
				return true;
			}
			return false;
		},
		scrollTo: function(element, to, duration) {
			if (duration < 0)
				return;
			var difference = to - element.scrollTop;
			var perTick = difference / duration * 10;

			setTimeout(function() {
				element.scrollTop = element.scrollTop + perTick;
				if (element.scrollTop == to)
					return;
				scrollTo(element, to, duration - 10);
			}, 10);
		},
		removeLastSpace: function(str) {
			return str.replace(/\s+$/, '');
		},
		numberToAlpha: function(data) {
			var string = '';
			switch (data) {
				case '0':
					string = 'A';
					break;
				case '1':
					string = 'B';
					break;
				case '2':
					string = 'C';
					break;
				case '3':
					string = 'D';
					break;
				case '4':
					string = 'F';
					break;
			}
			return string;
		},
		secondsToDateTime: function(second, type) {
			var string = '';

			var date = this.coverMilisecondToTime(second * 1000, 'minute');
			string = date.seconds + ' second' + date.secondsS;
			if (date.minutes > 0) {
				string = date.minutes + ' min' + date.minutesS + ' ' + string;
			}
			return string;
			// return;
		},
		coverMilisecondToTime: function(millis, type, options) {
			var seconds = 0;
			var minutes = 0;
			var hours = 0;
			var days = 0;
			var months = 0;
			var years = 0;
			if (type === 'day') {
				seconds = Math.round((millis / 1000) % 60);
				minutes = Math.floor(((millis / (60000)) % 60));
				hours = Math.floor(((millis / (3600000)) % 24));
				days = Math.floor(((millis / (3600000)) / 24));
				months = 0;
				years = 0;
			} else if (type === 'second') {
				seconds = Math.floor(millis / 1000);
				minutes = 0;
				hours = 0;
				days = 0;
				months = 0;
				years = 0;
			} else if (type === 'minute') {
				if (options && options.fixed) {
					seconds = (millis / 1000).toFixed(options.fixed);
				} else {
					seconds = Math.round((millis / 1000) % 60);
				}
				minutes = Math.floor(millis / 60000);
				hours = 0;
				days = 0;
				months = 0;
				years = 0;
			} else if (type === 'hour') {
				seconds = Math.round((millis / 1000) % 60);
				minutes = Math.floor(((millis / (60000)) % 60));
				hours = Math.floor(millis / 3600000);
				days = 0;
				months = 0;
				years = 0;
			} else if (type === 'month') {
				seconds = Math.round((millis / 1000) % 60);
				minutes = Math.floor(((millis / (60000)) % 60));
				hours = Math.floor(((millis / (3600000)) % 24));
				days = Math.floor(((millis / (3600000)) / 24) % 30);
				months = Math.floor(((millis / (3600000)) / 24) / 30);
				years = 0;
			} else if (type === 'year') {
				seconds = Math.round((millis / 1000) % 60);
				minutes = Math.floor(((millis / (60000)) % 60));
				hours = Math.floor(((millis / (3600000)) % 24));
				days = Math.floor(((millis / (3600000)) / 24) % 30);
				months = Math.floor(((millis / (3600000)) / 24 / 30) % 12);
				years = Math.floor((millis / (3600000)) / 24 / 365);
			}
			var secondsS = (seconds < 2) ? '' : 's';
			var minutesS = (minutes < 2) ? '' : 's';
			var hoursS = (hours < 2) ? '' : 's';
			var daysS = (days < 2) ? '' : 's';
			var monthsS = (months < 2) ? '' : 's';
			var yearsS = (years < 2) ? '' : 's';
			return {
				seconds: seconds,
				secondsS: secondsS,
				minutes: minutes,
				minutesS: minutesS,
				hours: hours,
				hoursS: hoursS,
				days: days,
				daysS: daysS,
				months: months,
				monthsS: monthsS,
				years: years,
				yearsS: yearsS
			};


		}
	};
}
);