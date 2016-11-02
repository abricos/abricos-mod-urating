var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    var UIManager = function(){
        this.init();
    };
    UIManager.prototype = {
        init: function(){
            Y.Node.all('.aw-urating.voting').each(function(node){
                console.log(node);
            }, this);
        }
    };

    NS.UIManager = UIManager;

    new NS.UIManager();
};