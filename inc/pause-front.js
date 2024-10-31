(function(window) {
	var PauseAdBlock = function(options) {
		this._options = {
			checkOnLoad:		false,
			resetOnEnd:			false,
			loopCheckTime:		50,
			loopMaxNumber:		5,
			badClass:			'pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links',
			badStyle:			'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;',
			debug:				false
		};
		this._var = {
			version:			'1.0.0',
			bad:				null,
			checking:			false,
			loop:				null,
			loopNumber:			0,
			event:				{ detected: [], notDetected: [] }
		};
		if(options !== undefined) {
			this.setOption(options);
		}
		var self = this;
		var eventCallback = function() {
			setTimeout(function() {
				if(self._options.checkOnLoad === true) {
					if(self._options.debug === true) {
						self._log('onload->eventCallback', 'A check loading is launched');
					}
					if(self._var.bad === null) {
						self._creatbad();
					}
					setTimeout(function() {
						self.check();
					}, 1);
				}
			}, 1);
		};
		if(window.addEventListener !== undefined) {
			window.addEventListener('load', eventCallback, false);
		} else {
			window.attachEvent('onload', eventCallback);
		}
	};
	PauseAdBlock.prototype._options = null;
	PauseAdBlock.prototype._var = null;
	PauseAdBlock.prototype._bad = null;
	
	PauseAdBlock.prototype._log = function(method, message) {
		console.log('[PauseAdBlock]['+method+'] '+message);
	};
	
	PauseAdBlock.prototype.setOption = function(options, value) {
		if(value !== undefined) {
			var key = options;
			options = {};
			options[key] = value;
		}
		for(var option in options) {
			this._options[option] = options[option];
			if(this._options.debug === true) {
				this._log('setOption', 'The option "'+option+'" he was assigned to "'+options[option]+'"');
			}
		}
		return this;
	};
	
	PauseAdBlock.prototype._creatbad = function() {
		var bad = document.createElement('div');
			bad.setAttribute('class', this._options.badClass);
			bad.setAttribute('style', this._options.badStyle);
		this._var.bad = window.document.body.appendChild(bad);
		
		this._var.bad.offsetParent;
		this._var.bad.offsetHeight;
		this._var.bad.offsetLeft;
		this._var.bad.offsetTop;
		this._var.bad.offsetWidth;
		this._var.bad.clientHeight;
		this._var.bad.clientWidth;
		
		if(this._options.debug === true) {
			this._log('_creatbad', 'bad has been created');
		}
	};
	PauseAdBlock.prototype._destroybad = function() {
		window.document.body.removeChild(this._var.bad);
		this._var.bad = null;
		
		if(this._options.debug === true) {
			this._log('_destroybad', 'bad has been removed');
		}
	};
	
	PauseAdBlock.prototype.check = function(loop) {
		if(loop === undefined) {
			loop = true;
		}
		
		if(this._options.debug === true) {
			this._log('check', 'An audit was requested '+(loop===true?'with a':'without')+' loop');
		}
		
		if(this._var.checking === true) {
			if(this._options.debug === true) {
				this._log('check', 'A check was canceled because there is already an ongoing');
			}
			return false;
		}
		this._var.checking = true;
		
		if(this._var.bad === null) {
			this._creatbad();
		}
		
		var self = this;
		this._var.loopNumber = 0;
		if(loop === true) {
			this._var.loop = setInterval(function() {
				self._checkbad(loop);
			}, this._options.loopCheckTime);
		}
		setTimeout(function() {
			self._checkbad(loop);
		}, 1);
		if(this._options.debug === true) {
			this._log('check', 'A check is in progress ...');
		}
		
		return true;
	};
	PauseAdBlock.prototype._checkbad = function(loop) {
		var detected = false;
		
		if(this._var.bad === null) {
			this._creatbad();
		}
		
		if(window.document.body.getAttribute('abp') !== null
		|| this._var.bad.offsetParent === null
		|| this._var.bad.offsetHeight == 0
		|| this._var.bad.offsetLeft == 0
		|| this._var.bad.offsetTop == 0
		|| this._var.bad.offsetWidth == 0
		|| this._var.bad.clientHeight == 0
		|| this._var.bad.clientWidth == 0) {
			detected = true;
		}
		if(window.getComputedStyle !== undefined) {
			var badTemp = window.getComputedStyle(this._var.bad, null);
			if(badTemp.getPropertyValue('display') == 'none'
			|| badTemp.getPropertyValue('visibility') == 'hidden') {
				detected = true;
			}
		}
		
		if(this._options.debug === true) {
			this._log('_checkbad', 'A check ('+(this._var.loopNumber+1)+'/'+this._options.loopMaxNumber+' ~'+(1+this._var.loopNumber*this._options.loopCheckTime)+'ms) was conducted and detection is '+(detected===true?'positive':'negative'));
		}
		
		if(loop === true) {
			this._var.loopNumber++;
			if(this._var.loopNumber >= this._options.loopMaxNumber) {
				this._stopLoop();
			}
		}
		
		if(detected === true) {
			this._stopLoop();
			this._destroybad();
			this.emitEvent(true);
			if(loop === true) {
				this._var.checking = false;
			}
		} else if(this._var.loop === null || loop === false) {
			this._destroybad();
			this.emitEvent(false);
			if(loop === true) {
				this._var.checking = false;
			}
		}
	};
	PauseAdBlock.prototype._stopLoop = function(detected) {
		clearInterval(this._var.loop);
		this._var.loop = null;
		this._var.loopNumber = 0;
		
		if(this._options.debug === true) {
			this._log('_stopLoop', 'A loop has been stopped');
		}
	};
	
	PauseAdBlock.prototype.emitEvent = function(detected) {
		if(this._options.debug === true) {
			this._log('emitEvent', 'An event with a '+(detected===true?'positive':'negative')+' detection was called');
		}
		
		var fns = this._var.event[(detected===true?'detected':'notDetected')];
		for(var i in fns) {
			if(this._options.debug === true) {
				this._log('emitEvent', 'Call function '+(parseInt(i)+1)+'/'+fns.length);
			}
			if(fns.hasOwnProperty(i)) {
				fns[i]();
			}
		}
		if(this._options.resetOnEnd === true) {
			this.clearEvent();
		}
		return this;
	};
	PauseAdBlock.prototype.clearEvent = function() {
		this._var.event.detected = [];
		this._var.event.notDetected = [];
		
		if(this._options.debug === true) {
			this._log('clearEvent', 'The event list has been cleared');
		}
	};
	
	PauseAdBlock.prototype.on = function(detected, fn) {
		this._var.event[(detected===true?'detected':'notDetected')].push(fn);
		if(this._options.debug === true) {
			this._log('on', 'A type of event "'+(detected===true?'detected':'notDetected')+'" was added');
		}
		
		return this;
	};
	PauseAdBlock.prototype.onDetected = function(fn) {
		return this.on(true, fn);
	};
	PauseAdBlock.prototype.onNotDetected = function(fn) {
		return this.on(false, fn);
	};
	
	window.PauseAdBlock = PauseAdBlock;
	
	if(window.pauseAdBlock === undefined) {
		window.pauseAdBlock = new PauseAdBlock({
			checkOnLoad: true,
			resetOnEnd: true
		});
	}
})(window);
