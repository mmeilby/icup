angular.module('tournamentBoardModule.filter', [])
    .filter("translate", function() {
        return function(key, domain, vars){
            return Translator.trans(key, vars ? vars : {}, domain ? domain : 'admin');
        }
    });
