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
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var buildTemplate = this.buildTemplate;
	
	var VotingWidget = function(container, cfg){
		cfg = L.merge({
			'modname': '',
			'elementType': '',
			'elementId': 0
		}, cfg || {});
		VotingWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, cfg);
	};
	YAHOO.extend(VotingWidget, Brick.mod.widget.Widget, {
		onLoad: function(cfg){
			this.cfg = cfg;
			this._clickBlocked = false;
		},
		onClick: function(el, tp){
			if (this._clickBlocked){ return; }
			switch(el.id){
			case tp['bup']: this.voteUp(); return true;
			case tp['brefrain']: this.voteRefrain(); return true;
			case tp['bdown']: this.voteUp(); return true;
			}
		},
		voteUp: function(){
			this.ajax('up');
		},
		voteDown: function(){
			this.ajax('down');
		},
		voteRefrain: function(){
			this.ajax('refrain');
		},
		ajax: function(vote){

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
		_onLoadAjaxData: function(){
			this._clickBlocked = false;
		}
	});
	NS.VotingWidget = VotingWidget;
	
};