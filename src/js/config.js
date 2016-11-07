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

    NS.ConfigWidget = Y.Base.create('configWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            appInstance.config(function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                this.renderConfig();
            }, this);
        },
        renderConfig: function(){
            var config = this.get('appInstance').get('config'),
                ownerList = config.get('ownerList');

            if (!ownerList){
                return;
            }

            var tp = this.template,
                lst = "";

            ownerList.each(function(owner){
                lst += tp.replace('row', {
                    id: owner.get('id'),
                    module: owner.get('module'),
                    type: owner.get('type'),
                    votingPeriod: owner.get('votingPeriod'),
                });
            });
            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });

            this.appURLUpdate();
            this.appTriggerUpdate();
        },
        save: function(){
            this.set('waiting', true);
            var tp = this.template,
                appInstance = this.get('appInstance'),
                config = appInstance.get('config'),
                sd = {
                    owners: []
                };

            config.get('ownerList').each(function(owner){
                var ownerid = owner.get('id'),
                    votingPeriod = tp.getValue('row.votingPeriod-' + owner.get('id')) | 0;

                owner.set('votingPeriod', votingPeriod);

                sd.owners[sd.owners.length] = {
                    ownerid: ownerid,
                    votingPeriod: votingPeriod
                };
            }, this);

            appInstance.configSave(sd, function(err, result){
                this.set('waiting', false);
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,list,row'}
        },
    });
};