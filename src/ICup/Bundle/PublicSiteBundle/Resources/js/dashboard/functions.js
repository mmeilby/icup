function DialogController($scope, $mdDialog) {
    $scope.submit = function(param) {
        $mdDialog.hide(param);
    };
    $scope.cancel = function() {
        $mdDialog.cancel();
    };
}

