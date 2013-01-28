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
	
	var VoteWidget = function(container){
		VoteWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(VoteWidget, Brick.mod.widget.Widget, {
		onLoad: function(){
			
		}
	});
	NS.VoteWidget = VoteWidget;
	
};