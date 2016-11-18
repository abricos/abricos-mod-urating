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

    var UID = Brick.env.user.id | 0;

    NS.UIVotingWidget = Y.Base.create('uiVotingWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
        },
        voteUp: function(){
            this.toVote('up');
        },
        voteAbstain: function(){
            this.toVote('abstain');
        },
        voteDown: function(){
            this.toVote('down');
        },
        toVote: function(action){
            var vote = {
                module: this.get('ownerModule'),
                type: this.get('ownerType'),
                ownerid: this.get('ownerid'),
                action: action
            };
            this.get('appInstance').toVote(vote, function(){

            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            useExistingWidget: {value: true},
            ownerModule: {},
            ownerType: {},
            ownerid: {}
        }
    });

    var UIManager = function(){
        if (UID === 0){
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
            Y.Node.all('.aw-urating.voting').each(function(node){
                var ownerModule = node.getData('module'),
                    ownerType = node.getData('type'),
                    ownerid = node.getData('id') | 0;

                if (!ownerModule || !ownerType || !ownerid){
                    return;
                }

                new NS.UIVotingWidget({
                    srcNode: node,
                    ownerModule: ownerModule,
                    ownerType: ownerType,
                    ownerid: ownerid
                });
            }, this);
        }
    };

    NS.UIManager = UIManager;
    new NS.UIManager();
};