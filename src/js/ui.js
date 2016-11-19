var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['vote.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI;

    var UIManager = function(){
        if ((Brick.env.user.id | 0) === 0){
            return;
        }
        var instance = this;
        NS.initApp({
            initCallback: function(){
                instance.init();
            }
        });
    };
    UIManager.prototype = {
        init: function(){
            var Voting = NS.appInstance.get('Voting');

            Y.Node.all('.aw-urating.voting').each(function(node){
                var data = node.getData('modelData');

                try {
                    data = Y.JSON.parse(data);
                } catch (e) {
                    return;
                }

                if (!data || !Y.Lang.isObject(data)){
                    return;
                }

                var voting = new Voting(Y.merge({
                    appInstance: NS.appInstance
                }, data));

                new NS.VotingWidget({
                    srcNode: node,
                    voting: voting
                });
            }, this);
        }
    };

    NS.UIManager = UIManager;
    new NS.UIManager();
};