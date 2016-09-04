function DialogController($scope, $mdDialog) {
    $scope.submit = function(param) {
        $mdDialog.hide(param);
    };
    $scope.cancel = function() {
        $mdDialog.cancel();
    };
}
function ShowError($mdDialog, errors, title) {
    var content = [];
    angular.forEach(errors, function(error, key) {
        var errorTxt = Translator.trans('ERROR.'+error+'.TITLE', {}, 'errors');
        if (errorTxt == 'ERROR.'+error+'.TITLE') {
            errorTxt = error;
        }
        this.push(errorTxt);
    }, content);
    $mdDialog.show($mdDialog.alert().clickOutsideToClose(true).title(title).textContent(content.join(' ')).ok(Translator.trans('FORM.CLOSE')));
}
