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

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config',
    });

    NS.OwnerConfig = Y.Base.create('ownerConfig', SYS.AppModel, [], {
        structureName: 'OwnerConfig',
    });

    NS.OwnerConfigList = Y.Base.create('ownerConfigList', SYS.AppModelList, [], {
        appItem: NS.OwnerConfig,
    });
};