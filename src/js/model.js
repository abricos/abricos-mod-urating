var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

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