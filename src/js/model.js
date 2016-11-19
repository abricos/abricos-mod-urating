var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Voting = Y.Base.create('voting', SYS.AppModel, [], {
        structureName: 'Voting',
    }, {
        ATTRS: {
            config: {
                getter: function(){
                    if (this._ownerConfig){
                        return this._ownerConfig;
                    }

                    var appConfig = this.appInstance.get('config'),
                        ownerConfigList = appConfig.get('ownerList'),
                        module = this.get('module'),
                        type = this.get('type');

                    this._ownerConfig = ownerConfigList.getByOwner(module, type);
                    return this._ownerConfig;
                }
            }
        }
    });

    NS.VotingList = Y.Base.create('votingList', SYS.AppModelList, [], {
        appItem: NS.Voting,
    });

    NS.Vote = Y.Base.create('vote', SYS.AppModel, [], {
        structureName: 'Vote',
    });

    NS.VoteList = Y.Base.create('voteList', SYS.AppModelList, [], {
        appItem: NS.Vote,
    });

    NS.ToVote = Y.Base.create('toVote', SYS.AppResponse, [], {
        structureName: 'ToVote',
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config',
    });

    NS.OwnerConfig = Y.Base.create('ownerConfig', SYS.AppModel, [], {
        structureName: 'OwnerConfig',
    });

    NS.OwnerConfigList = Y.Base.create('ownerConfigList', SYS.AppModelList, [], {
        appItem: NS.OwnerConfig,
        getByOwner: function(module, type){
            var ret = null;
            this.each(function(ownerConfig){
                if (ownerConfig.get('module') === module
                    && ownerConfig.get('type') === type){
                    ret = ownerConfig;
                }
            }, this);
            return ret;
        }
    });
};