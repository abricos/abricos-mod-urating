var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.UIVotingWidget = Y.Base.create('uiVotingWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            console.log(arguments);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            useExistingWidget: {value: true},
        }
    });

    var UIManager = function(){
        var instance = this;
        NS.initApp({
            initCallback: function(){
                instance.init();
            }
        });
    };
    UIManager.prototype = {
        init: function(){
            Y.Node.all('.aw-urating.voting').each(function(node){
                new NS.UIVotingWidget({
                    srcNode: node,
                    ownerModule: node.getData('module'),
                    ownerType: node.getData('type'),
                    ownerid: node.getData('id') | 0
                });
            }, this);
        }
    };

    NS.UIManager = UIManager;
    new NS.UIManager();
};