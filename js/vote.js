/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: 'widget', files: ['notice.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang;
	
	var UID = Brick.env.user.id;
	
	var buildTemplate = this.buildTemplate,
		LNG = this.language;
	
	var VotingWidget = function(container, cfg){
		cfg = L.merge({
			'modname': '',
			'elementType': '',
			'elementId': 0,
			'value': null,
			'vote': null,
			'readOnly': false,
			'hideButtons': false,
			'onVotingError': null,
			'errorlang': null
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
		onClick: function(el, tp){
			if (this._clickBlocked){ return; }
			switch(el.id){
			case tp['bup']: this.voteUp(); return true;
			case tp['bvalue']: this.voteRefrain(); return true;
			case tp['bdown']: this.voteDown(); return true;
			}
		},
		voteUp: function(){ this.ajax('up'); },
		voteDown: function(){ this.ajax('down'); },
		voteRefrain: function(){ this.ajax('refrain'); },
		ajax: function(act){
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
					'act': act
				},
				'event': function(request){
					__self._onLoadData(request.data);
				}
			});
		},
		_onLoadData: function(d){
			var cfg = this.cfg;
			this._clickBlocked = false;
			
			if (L.isNull(d)){ return; }
			if (d['error'] != 0){
				if (L.isFunction(cfg['onVotingError'])){
					cfg['onVotingError'](d['error'], d['merror']);
				}else if (L.isString(cfg['errorlang'])){
					
					var s = 'ERROR';
					
					if (d['merror'] > 0){
						s = LNG.get(cfg['errorlang']+'.m.'+d['merror'], cfg['modname']);
					}else if (d['error'] > 0){
						s = LNG.get(cfg['errorlang']+'.'+d['merror'], cfg['modname']);
					}else{
						return;
					}
					Brick.mod.widget.notice.show(s);
				}
				return; 
			}
			
			var di = d['info'];
			this.value = di['val'];
			this.vote = di['vote'];
			
			this.render();
			
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
			
			var elStVal = this.gel('statval');
			
			Dom.removeClass(elStVal, 'up');
			Dom.removeClass(elStVal, 'down');
			
			if (!L.isNull(vote)){
				if (vote == -1){
					Dom.addClass(elStVal, 'down');
				}else if (vote == 1){
					Dom.addClass(elStVal, 'up');					
				}
			}
		}
	});
	NS.VotingWidget = VotingWidget;
	
	NS.API.WidgetListByServ = function(cfg){
		
		var list = cfg['list'];
		if (!L.isArray(list)){ return; }
		
		Brick.ff(cfg['modname'], 'lib', function(){ // подгрузить компонет lib для фраз ошибок
			for (var i=0;i<list.length;i++){
				
				var v = list[i];
				var el = Dom.get(v['jsid']);
				if (L.isNull(el)){ continue; }
	
				new VotingWidget(el, {
					'modname': cfg['modname'],
					'elementType': cfg['eltype'],
					'elementId': v['id'],
					'value': v['vl'],
					'vote': v['vt'],
					'errorlang': cfg['errorlang']
				});
			}
		});
		
	};
	
};