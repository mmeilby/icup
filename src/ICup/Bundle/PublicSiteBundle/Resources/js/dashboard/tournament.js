/*
 ------------------------------------
 TOURNAMENT BOARD
 ------------------------------------
 */
angular.module('tournamentBoardModule.tournament', [])
    .controller('tournamentBoardController', function($scope, $http, $mdDialog, $mdMedia, $mdToast, Tournament, flagsDirectory, templateDirectory) {
        $scope.flagsDir = flagsDirectory;
        $scope.genders = [ { 'id': 'F', 'name': Translator.trans('FORM.CATEGORY.SEX.FEMALE') }, { 'id': 'M', 'name': Translator.trans('FORM.CATEGORY.SEX.MALE') } ];
        $scope.classifications = [ { 'id': 'U', 'name': Translator.trans('FORM.CATEGORY.CLASS.UNDER') }, { 'id': 'O', 'name': Translator.trans('FORM.CATEGORY.CLASS.OVER') } ];

        $scope.$watch(function () { return Tournament.getTournament(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.tournament = newValue;
        });
        $scope.$watch(function () { return Tournament.getCategories(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.categories = newValue;
        });
        $scope.$watch(function () { return Tournament.getVenues(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.venues = newValue;
        });
        $scope.$watch(function () { return Tournament.getSites(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.sites = newValue;
        });
        $scope.$watch(function () { return Tournament.getTimeslots(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.timeslots = newValue;
        });

        $scope.chgOptions = function (ev) {
            var dlgScope = $scope.$new();
            dlgScope.options_object = dlgScope.tournament.option;
            dlgScope.doMoveUp = function (idx, ev) {
                if (idx > 0) {
                    var obj = dlgScope.options_object.order.splice(idx, 1);
                    dlgScope.options_object.order.splice(idx-1, 0, obj[0]);
                }
            };
            dlgScope.doMoveDown = function (idx, ev) {
                var obj = dlgScope.options_object.order.splice(idx, 1);
                dlgScope.options_object.order.splice(idx+1, 0, obj[0]);
            };
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'edittmntoptdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
            }).then(
                function(options_object) {
                    $http.post(Routing.generate('_rest_tournament_options_update', {'tournamentid': dlgScope.tournament.id }), options_object).then(
                        function (data) {
                            dlgScope.tournament.option = dlgScope.options_object;
                            Tournament.setTournament(dlgScope.tournament);
                            $mdToast.show(
                                $mdToast.simple()
                                    .textContent(Translator.trans('FORM.TOURNAMENTOPTIONS.TITLE.UPDATED'))
                                    .position('top right')
                                    .hideDelay(3000)
                            );
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.TOURNAMENTOPTIONS.TITLE.CHG'));
                        });
                }
            );
        };
    });
