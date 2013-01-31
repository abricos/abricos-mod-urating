/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'widget', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang;
	
	var UID = Brick.env.user.id;
	
	var buildTemplate = this.buildTemplate;
	
	var VotingWidget = function(container, cfg){
		cfg = L.merge({
			'modname': '',
			'elementType': '',
			'elementId': 0,
			'value': null,
			'vote': null,
			'readOnly': false,
			'hideButtons': false
		}, cfg || {});
		VotingWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, cfg);
	};
	YAHOO.extend(VotingWidget, Brick.mod.widget.Widget, {
		init: function(cfg){
			this.cfg = cfg;
			this._clickBlocked = false;
			
			// Результат голосов
			this.value = cfg['value'];
			
			// Голос текущего пользователя:
			//	null - не голосовал, 1 - ЗА -1 - ПРОТИВ, 0 - воздержался
			this.vote = !L.isNull(cfg['vote']) ? cfg['vote']*1 : null;
			
			this.readOnly = cfg['readOnly'];
			
			this.hideButtons = cfg['hideButtons'];
		},
		onLoad: function(cfg){
		},
		onClick: function(el, tp){
			if (this._clickBlocked){ return; }
			switch(el.id){
			case tp['bup']: this.voteUp(); return true;
			case tp['bvalue']: this.voteRefrain(); return true;
			case tp['bdown']: this.voteUp(); return true;
			}
		},
		voteUp: function(){ this.ajax('up'); },
		voteDown: function(){ this.ajax('down'); },
		voteRefrain: function(){ this.ajax('refrain'); },
		ajax: function(vote){
			if (UID == 0 || this.readOnly || !L.isNull(this.vote)){ 
				return; 
			}

			this._clickBlocked = true;
			var __self = this, cfg = this.cfg;
			
			Brick.ajax('{C#MODNAME}', {
				'data': {
					'do': 'elementvoting',
					'module': cfg['modname'],
					'eltype': cfg['elementType'],
					'elid': cfg['elementId'],
					'vote': vote
				},
				'event': function(request){
					__self._onLoadAjaxData(request.data);
				}
			});
		},
		_onLoadAjaxData: function(d){
			this._clickBlocked = false;
		},
		render: function(){
			
			var vote = this.vote, value = this.value;
			
			this.elSetHTML({
				'bvalue': L.isNull(value) ? '—' : value
			});
			
			if (this.hideButtons){
				this.elHide('bup,bdown');
			}
			
			if (UID > 0 && L.isNull(vote) && !this.readOnly){
				Dom.replaceClass(this.gel('status'), 'ro', 'w');
			}else{
				Dom.replaceClass(this.gel('status'), 'w', 'ro');
			}
			
			var elStaVal = this.gel('statval');
			
			Dom.removeClass(elStaVal, 'up');
			Dom.removeClass(elStaVal, 'down');
			Brick.console(vote);
			if (!L.isNull(vote)){
				
				switch(vote){
				case -1:
					Dom.addClass(elStaVal, 'down');
					break;
				case 1:
					Dom.addClass(elStaVal, 'up');
					break;
				}
				
			}
		}
	});
	NS.VotingWidget = VotingWidget;
	
};