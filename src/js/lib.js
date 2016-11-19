var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            this.config(function(){
                NS.roles.load(function(){
                    this.initCallbackFire();
                }, this);
            }, this);
        }
    }, [], {
        APPS: {},
        ATTRS: {
            isLoadAppStructure: {value: true},
            Voting: {value: NS.Voting},
            VotingList: {value: NS.VotingList},
            Vote: {value: NS.Vote},
            VoteList: {value: NS.VoteList},
            ToVote: {value: NS.ToVote},
            Config: {value: NS.Config},
            OwnerConfig: {value: NS.OwnerConfig},
            OwnerConfigList: {value: NS.OwnerConfigList},
        },
        REQS: {
            toVote: {
                args: ['data'],
                type: 'response:ToVote'
            },
            config: {
                attribute: true,
                type: 'model:Config'
            },
            configSave: {
                args: ['data']
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            config: function(){
                return this.getURL('ws') + 'config/ConfigWidget/';
            }
        }
    });
};