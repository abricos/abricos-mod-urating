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

    NS.VotingWidget = Y.Base.create('votingWidget', SYS.AppWidget, [], {
        buildTData: function(){
            return this._buildVotingReplace();
        },
        _buildVotingReplace: function(){
            var tp = this.template,
                voting = this.get('voting'),
                vote = voting.get('vote'),
                replace = {
                    status: 'ro',
                    scoreStatus: '',
                    bup: tp.replace('guestUp'),
                    bval: tp.replace('guestVal'),
                    bdown: tp.replace('guestDown'),
                };

            if (voting.get('isShowResult')){
                var score = voting.get('score'),
                    sScore = score > 0 ? '+' : '';

                sScore += score;

                replace.bval = tp.replace('scoreVal', {
                    voting: sScore,
                    voteCount: voting.get('voteCount'),
                    voteUpCount: voting.get('voteUpCount'),
                    voteDownCount: voting.get('voteDownCount'),
                });
                replace.bup = tp.replace('scoreUp');
                replace.bdown = tp.replace('scoreDown');
                replace.scoreStatus = vote.get('vote') > 0 ? 'up' :
                    (vote.get('vote') < 0 ? 'down' : '');
            }

            if (voting.get('isVoting')){
                replace.status = 'w';
                replace.bup = tp.replace('up');
                replace.bdown = tp.replace('down');

                if (!voting.get('isShowResult')){
                    replace.bval = tp.replace('val', {
                        voteCount: voting.get('voteCount')
                    });
                }

            }
            return replace;
        },
        _renderVoting: function(){
            var tp = this.template,
                node = this.get('boundingBox'),
                replace = this._buildVotingReplace(),
                html = tp.replace('widget', replace);

            node.setHTML(html);
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
            var voting = this.get('voting'),
                vote = {
                    module: voting.get('module'),
                    type: voting.get('type'),
                    ownerid: voting.get('ownerid'),
                    action: action
                };

            this.get('appInstance').toVote(vote, function(err, result){
                if (result && result.toVote){
                    var voting = result.toVote.get('voting');
                    this.set('voting', voting);
                    this._renderVoting();
                }
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {
                value: 'widget,wrap,guestUp,guestVal,guestDown,' +
                'scoreUp,scoreVal,scoreDown,' +
                'up,val,down'
            },
            voting: {value: null}
        }
    });
};