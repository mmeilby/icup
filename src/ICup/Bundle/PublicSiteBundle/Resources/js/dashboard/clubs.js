/*
 ------------------------------------
 LIST CLUBS
 ------------------------------------
 */
angular.module('tournamentBoardModule.clubs', [])
    .controller('listClubsController', function($scope, $http, $mdDialog, $mdMedia, $mdToast, Tournament, templateDirectory) {
        $scope.updateClub = function (club, ev) {
            $scope.club = club;
            var dlgScope = $scope.$new();
            dlgScope.club_object = {
                'name': $scope.club.name,
                'country': $scope.club.country_code
            };
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'editclubdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        }).then(
                function(club_object) {
                    $http.post(Routing.generate('rest_club_update', {'clubid': $scope.club.id }), club_object).then(
                        function (data) {
                            $scope.club.name = club_object.name;
                            $mdToast.show(
                                $mdToast.simple()
                                    .textContent(Translator.trans('FORM.CLUB.UPDATED'))
                                    .position('top right')
                                    .hideDelay(3000)
                            );
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.CLUB.TITLE.CHG'));
                        });
                });
        };
        $scope.updateEnrollment = function (club, ev) {
            $scope.club = club;
            var dlgScope = $scope.$new();
            $http.get(Routing.generate('_rest_list_enrolled_teams', { 'tournamentid': $scope.tournament.id, 'clubid': club.id })).then(
                function(data) {
                    dlgScope.categories = data.data;
                });
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'editenrolleddlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
            });
        };
        $scope.addEnrollment = function (category, club) {
            $scope.club = club;
            $http.get(Routing.generate('_rest_club_enroll_add', { 'categoryid': category.id, 'clubid': club.id })).then(
                function() {
                    category.enrolled++;
                    club.enrolled++;
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTENROLLED.TITLE', {}, 'club'));
                });
        };
        $scope.removeEnrollment = function (category, club) {
            $scope.club = club;
            $http.get(Routing.generate('_rest_club_enroll_del', { 'categoryid': category.id, 'clubid': club.id })).then(
                function() {
                    category.enrolled--;
                    club.enrolled--;
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTENROLLED.TITLE', {}, 'club'));
                });
        };
        $scope.dataReady = false;
        $scope.countries = [];
        $scope.tournament = Tournament.getTournament();
        $http.get(Routing.generate('_rest_list_clubs', { 'tournamentid': $scope.tournament.id })).then(function(data) {
            angular.forEach(data.data, function (club) {
                var country = $scope.countries.find(function (country) {
                    return country.country_code == club.country_code;
                });
                if (country) {
                    country.clubs.push(club);
                }
                else {
                    $scope.countries.push({
                        'name': club.country,
                        'country_code': club.country_code,
                        'flag': club.flag,
                        'clubs': [club]
                    });
                }
            });
            $scope.dataReady = true;
        });
        $scope.team_categories = [];
        $scope.$watch(function () { return Tournament.getCategories(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $http.get(Routing.generate('_rest_list_enrolled_categories', { 'tournamentid': $scope.tournament.id })).then(
                    function(data) {
                        $scope.team_categories = data.data;
                    });
            }
        });
    });
