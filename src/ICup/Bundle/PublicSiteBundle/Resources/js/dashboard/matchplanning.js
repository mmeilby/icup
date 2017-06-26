/*
 ------------------------------------
 MATCH PLANNING
 ------------------------------------
 */
angular.module('tournamentBoardModule.planning', [])
    .controller('matchPlanningController', function($scope, $http, $mdDialog, $mdMedia, $filter, $mdBottomSheet, $mdToast, $document, Tournament, templateDirectory) {
        $scope.matches = [];
        $scope.unassigned_categories = [];
        $scope.matches_planned = false;
        $scope.planning_level = -1;
        $scope.search_object = {
            'team': { 'id': 0 },
            'category': { 'id': 0 },
            'group': { 'id': 0 },
            'venue': { 'id': 0 }
        };
        $scope.$watch(function () { return Tournament.getCategories(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.categories = newValue;
                GetMatchesPlanned();
            }
        });
        // limit number of matches shown
        $scope.limit = 10;
        $scope.tournament = Tournament.getTournament();
        $http.get(Routing.generate('_rest_get_match_calendar', { 'tournamentid': $scope.tournament.id })).then(function(data) {
            $scope.minDate = new Date(data.data.start);
            $scope.maxDate = new Date(data.data.end);
        });
        // watch the change of category and update the groups accordingly
        $scope.$watch(
            function () {
                return $scope.search_object.category.id;
            },
            function (id, previous_id) {
                if (id) {
                    $http.get(Routing.generate('_rest_list_groups', { 'categoryid': id })).then(
                        function(data) {
                            $scope.groups = data.data;
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                        });
                }
            });
        $scope.matchFilter = function (match) {
            if ($scope.search_object.team.id) {
                if (match.home.name != $scope.search_object.team.name && match.away.name != $scope.search_object.team.name) return false;
            }
            if ($scope.search_object.category.id) {
                if (match.category.id != $scope.search_object.category.id) return false;
            }
            if ($scope.search_object.group.id) {
                if (match.group.id != $scope.search_object.group.id) return false;
            }
            if ($scope.search_object.venue.id) {
                if (match.venue.id != $scope.search_object.venue.id) return false;
            }
            if ($scope.search_object.date) {
                if (match.date.raw != $filter('date')($scope.search_object.date, 'yyyyMMdd')) return false;
            }
            return true;
        };
        GetMatchesPlanned();
        $scope.planMatches = function (ev) {
            var dlgScope = $scope.$new();
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'planmatchdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        })
            .then(function() {});
            planTournament(0, dlgScope);
        };
        $scope.savePlan = function () {
            $http.get(Routing.generate('_rest_match_planning_save_plan', { 'tournamentid': $scope.tournament.id })).then(
                function(data) {
                    $mdToast.show(
                        $mdToast.simple()
                            .textContent(Translator.trans('FORM.MATCHPLANNING.SAVE.DONE'))
                            .position('top right')
                            .hideDelay(5000)
                            .parent($document[0].querySelector('#toastAnchor'))

                    );
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.MATCHPLANNING.SAVE.TITLE'));
                });
        };
        $scope.resetPlan = function () {
            $http.get(Routing.generate('_rest_match_planning_reset_plan', { 'tournamentid': $scope.tournament.id })).then(
                function(data) {
                    $scope.unassigned_categories = [];
                    $scope.matches_planned = false;
                    $scope.planning_level = -1;
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.MATCHPLANNING.RESET.TITLE'));
                });
        };
        $scope.uploadPlan = function(ev) {
            var dlgScope = $scope.$new();
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'matchimportdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        })
            .then(function () {
                //create form data object
                var r = new FileReader();
                r.onload = function(e){
                    var fd = new FormData();
                    fd.append('file', e.target.result);
                    $http.post(Routing.generate('_rest_import_match_schedule', {'tournamentid': $scope.tournament.id}), fd, {
                        transformRequest: angular.identity,
                        headers: {'Content-Type': undefined}
                    }).then(
                        function (response) {
                            $mdToast.show(
                                $mdToast.simple()
                                    .textContent(Translator.trans('FORM.MATCHPLANNING.MATCHIMPORT.DONE', { 'matches': response.data.status.count, 'file': dlgScope.uploadFile.name }, 'admin'))
                                    .position('top right')
                                    .hideDelay(10000)
                                    .parent($document[0].querySelector('#toastAnchor'))

                            );
                            GetMatchesPlanned();
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.MATCHPLANNING.MATCHIMPORT.TITLE'));
                        });
                };
                if (dlgScope.uploadFile.type == 'text/plain') {
                    r.readAsText(dlgScope.uploadFile);
                }
                else {
                    ShowError($mdDialog, [Translator.trans('FORM.MATCHPLANNING.MATCHIMPORT.INVALIDFILE')], Translator.trans('FORM.MATCHPLANNING.MATCHIMPORT.TITLE'));
                }
            });
        };
        $scope.editMatch = function (match, ev) {
            this.msover = false;
            var dlgScope = $scope.$new();
            dlgScope.match = match;
            dlgScope.destination_object = { 'date': new Date(match.date.js), 'time': match.time.text, 'venue': match.venue, 'timeslot': match.timeslot };
            dlgScope.matchFilter = function (match) {
                if (dlgScope.destination_object.timeslot.id) {
                    if (match.timeslot.id != dlgScope.destination_object.timeslot.id) return false;
                }
                if (dlgScope.destination_object.venue.id) {
                    if (match.venue.id != dlgScope.destination_object.venue.id) return false;
                }
                if (dlgScope.destination_object.date) {
                    if (match.date.raw != $filter('date')(dlgScope.destination_object.date, 'yyyyMMdd')) return false;
                }
                return true;
            };
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'editplanmatchdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        })
            .then(function() {
                var matchData = {
                    'timeslot': dlgScope.destination_object.timeslot.id,
                    'venue': dlgScope.destination_object.venue.id,
                    'date': $filter('date')(dlgScope.destination_object.date, 'yyyyMMdd'),
                    'matchtime': dlgScope.destination_object.time
                };
                $http.post(Routing.generate('_rest_match_planning_update_match', {'matchtype': dlgScope.match.uid.substr(0,1), 'matchid': dlgScope.match.uid.substr(1) }), matchData).then(
                    function (data) {
                        $mdToast.show(
                            $mdToast.simple()
                                .textContent("Match updated")
                                .position('top right')
                                .hideDelay(3000)
                                .parent($document[0].querySelector('#toastAnchor'))

                        );
                        GetMatchesPlanned();
                        $mdToast.hide();
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.CLUB.TITLE.CHG'));
                    });
            });
        };
        function GetMatchesPlanned() {
            $http.get(Routing.generate('_rest_match_planning_get_plan', { 'tournamentid': $scope.tournament.id })).then(
                function(data) {
                    $scope.unassigned_categories = data.data.unassigned_by_category;
                    $scope.matches_planned = data.data.matches.length > 0;
                    $scope.matches = data.data.matches;
                    var teams = {};
                    angular.forEach($scope.matches, function (match) {
                        if (match.uid < 'Q') {
                            if (match.home.id) {
                                teams[match.home.name] = match.home;
                            }
                            if (match.away.id) {
                                teams[match.away.name] = match.away;
                            }
                        }
                    });
                    $scope.teams = [];
                    angular.forEach(teams, function (team) {
                        $scope.teams.push(team);
                    });
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                });
        }
        function planTournament(level, dlgScope) {
            dlgScope.planning_level = level;
            $http.get(Routing.generate('_rest_match_planning_plan', { 'tournamentid': $scope.tournament.id, 'level': level })).then(
                function(result) {
                    if (result.data.done) {
                        dlgScope.planning_level = result.data.level;
                        $scope.unassigned_categories = result.data.unassigned_by_category;
                        $scope.matches_planned = true;
                        $scope.planning_level = result.data.level;
                        dlgScope.planning_status = Translator.trans('FORM.MATCHPLANNING.PLANNING.DONE');
                        GetMatchesPlanned();
                    }
                    else {
                        planTournament(result.data.level, dlgScope);
                    }
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.MATCHPLANNING.PLANNING.ERROR'));
                });
        }
    });
