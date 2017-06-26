angular.module('translationModule.filter', [])
    .filter("translate", function(translate_domain) {
        return function(key, domain, vars){
            return Translator.trans(key, vars ? vars : {}, domain ? domain : (translate_domain ? translate_domain : 'admin'));
        }
    });
