/*
 ------------------------------------
 LIST CATEGORIES
 ------------------------------------
 */
angular.module('tournamentBoardModule.categories', [])
    .controller('assignGroupsController', function($scope, $http, $mdDialog, $mdMedia, Tournament, templateDirectory) {
        $scope.$watch(function () { return Tournament.getCategories(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.categories = newValue;
        });
        $scope.onDragUnassignedTeam=function(teamid){
            var index = $scope.unassigned.teams.findIndex(function (team) {
                return team.id == teamid;
            });
            var team = $scope.unassigned.teams.splice(index, 1);
            $scope.team = team[0];
            $scope.group = null;
            $scope.draggingAllowed = true;
        };
        $scope.onDragTeam=function(groupid, teamid){
            var group = $scope.groups.find(function (group) {
                return group.group.id == groupid;
            });
            var index = group.teams.findIndex(function (team) {
                return team.id == teamid;
            });
            var team = group.teams.splice(index, 1);
            $scope.team = team[0];
            $scope.group = group.group;
            $scope.draggingAllowed = true;
        };
        $scope.onDropUnassigned=function(){
            if ($scope.draggingAllowed) {
                $scope.draggingAllowed = false;
                $scope.unassigned.teams.push($scope.team);
                if ($scope.group) {
                    $http.get(Routing.generate('_rest_assign_del', { 'groupid': $scope.group.id, 'teamid': $scope.team.id })).then(
                        function(data) {
                            getGroups();
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                        });
                }
            }
        };
        $scope.onDropGroup=function(groupid){
            if ($scope.draggingAllowed) {
                $scope.draggingAllowed = false;
                var group = $scope.groups.find(function (group) {
                    return group.group.id == groupid;
                });
                group.teams.push($scope.team);
                if ($scope.group) {
                    $http.get(Routing.generate('_rest_assign_del', { 'groupid': $scope.group.id, 'teamid': $scope.team.id })).then(
                        function(data) {
                            $http.get(Routing.generate('_rest_assign_add', { 'groupid': groupid, 'teamid': $scope.team.id })).then(
                                function(data) {
                                    getGroups();
                                },
                                function (response) {
                                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                                });
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                        });
                }
                else {
                    $http.get(Routing.generate('_rest_assign_add', { 'groupid': groupid, 'teamid': $scope.team.id })).then(
                        function(data) {
                            getGroups();
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                        });
                }
            }
        };
        $scope.onDropBasket=function(){
            if ($scope.group == null) {
                $http.get(Routing.generate('_rest_enroll_del', {'teamid': $scope.team.id})).then(
                    function (data) {
                        getGroups();
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                    });
            }
            else {
                var group = $scope.groups.find(function (group) {
                    return group.group.id == $scope.group.id;
                });
                group.teams.push($scope.team);
            }
        };
        $scope.addCategory = function(ev) {
            var dlgScope = $scope.$new();
            dlgScope.category_object = {
                'name': '',
                'gender': { 'id': 'F' },
                'classification': { 'id': 'U' },
                'age': 18,
                'trophys': 4,
                'topteams': 0,
                'strategy': 2,
                'matchtime': 60
            };
            $mdDialog.show({
                    controller: DialogController,
                    scope: dlgScope,
                    templateUrl: templateDirectory+'editcategorydlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        }).then(
                function(category_object) {
                    $http.post(Routing.generate('rest_category_create', { 'tournamentid': $scope.tournament.id }), {
                        'name': dlgScope.category_object.name,
                        'gender': dlgScope.category_object.gender.id,
                        'classification': dlgScope.category_object.classification.id,
                        'age': dlgScope.category_object.age,
                        'trophys': dlgScope.category_object.trophys,
                        'topteams': dlgScope.category_object.topteams,
                        'strategy': dlgScope.category_object.strategy,
                        'matchtime': dlgScope.category_object.matchtime
                    })
                        .then(
                            function (data) {
                                var newid = data.data.id;
                                $http.get(Routing.generate('_rest_list_categories', { 'tournamentid': $scope.tournament.id })).then(
                                    function(data) {
                                        $scope.categories = data.data;
                                        $scope.selectCategory(newid);
                                        Tournament.setCategories($scope.categories);
                                    },
                                    function (response) {
                                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTCATEGORIES.CAPTION'));
                                    });
                            },
                            function (response) {
                                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.CATEGORY.TITLE.ADD'));
                            });
                });
        };
        $scope.updateCategory = function(ev) {
            var dlgScope = $scope.$new();
            dlgScope.category_object = {
                'name': $scope.category.name,
                'gender': { 'id': $scope.category.gender },
                'classification': { 'id': $scope.category.classification },
                'age': $scope.category.age,
                'trophys': $scope.category.trophys,
                'topteams': $scope.category.topteams,
                'strategy': $scope.category.strategy,
                'matchtime': $scope.category.matchtime
            };
            $mdDialog.show({
                    controller: DialogController,
                    scope: dlgScope,
                    templateUrl: templateDirectory+'editcategorydlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        }).then(
                function(category_object) {
                    $http.post(Routing.generate('rest_category_update', { 'categoryid': $scope.category.id }), {
                        'name': dlgScope.category_object.name,
                        'gender': dlgScope.category_object.gender.id,
                        'classification': dlgScope.category_object.classification.id,
                        'age': dlgScope.category_object.age,
                        'trophys': dlgScope.category_object.trophys,
                        'topteams': dlgScope.category_object.topteams,
                        'strategy': dlgScope.category_object.strategy,
                        'matchtime': dlgScope.category_object.matchtime
                    })
                        .then(
                            function () {
                                $http.get(Routing.generate('_rest_list_categories', { 'tournamentid': $scope.tournament.id })).then(
                                    function(data) {
                                        $scope.categories = data.data;
                                        $scope.selectCategory(dlgScope.category.id);
                                        Tournament.setCategories($scope.categories);
                                    },
                                    function (response) {
                                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTCATEGORIES.CAPTION'));
                                    });
                            },
                            function (response) {
                                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.CATEGORY.TITLE.ADD'));
                            });
                });
        };
        $scope.delCategory = function(ev) {
            $http.delete(Routing.generate('rest_category_delete', {'categoryid': $scope.category.id })).then(
                function () {
                    $http.get(Routing.generate('_rest_list_categories', { 'tournamentid': $scope.tournament.id })).then(
                        function(data) {
                            $scope.categories = data.data;
                            $scope.selectedCategory = 0;
                            Tournament.setCategories($scope.categories);
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTCATEGORIES.CAPTION'));
                        });
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.GROUP.TITLE.DEL'));
                });
        };
        $scope.categoryOptions = function(ev) {
            var dlgScope = $scope.$new();
            dlgScope.category_object = {
                'name': $scope.category.name,
                'gender': $scope.category.gender,
                'classification': $scope.category.classification,
                'age': $scope.category.age,
                'trophys': $scope.category.trophys,
                'topteams': $scope.category.topteams,
                'strategy': $scope.category.strategy,
                'matchtime': $scope.category.matchtime
            };
            $mdDialog.show({
                    controller: DialogController,
                    scope: dlgScope,
                    templateUrl: templateDirectory+'editcatoptionsdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        })
            .then(function(category_object) {
                $http.post(Routing.generate('rest_category_update', {'categoryid': $scope.selectedCategory }), category_object).then(
                    function () {
                        var category = $scope.categories.find(function (category) {
                            return category.id == $scope.selectedCategory;
                        });
                        category.trophys = category_object.trophys;
                        category.topteams = category_object.topteams;
                        category.strategy = category_object.strategy;
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.OPTIONS'));
                    });
            });
        };
        $scope.addGroup = function(ev) {
            var dlgScope = $scope.$new();
            dlgScope.group_object = {
                'name': '',
                'classification': 0
            };
            $mdDialog.show({
                    controller: DialogController,
                    scope: dlgScope,
                    templateUrl: templateDirectory+'editgroupdlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        })
            .then(function(group_object) {
                $http.post(Routing.generate('rest_group_create', {'categoryid': $scope.category.id }), group_object).then(
                    function () {
                        getGroups();
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.GROUP.TITLE.ADD'));
                    });
            });
        };
        $scope.deleteGroup = function (groupid) {
            $http.delete(Routing.generate('rest_group_delete', {'groupid': groupid })).then(
                function (data) {
                    getGroups();
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.GROUP.TITLE.DEL'));
                });
        };
        $scope.assignVacant = function(groupid){
            $http.get(Routing.generate('_rest_assign_vacant', { 'groupid': groupid })).then(
                function(data) {
                    getGroups();
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.OPTIONS'));
                });
        };
        $scope.dataReady = true;
        $scope.selectedCategory = 0;
        $scope.selectCategory = function (id) {
            $scope.selectedCategory = id;
            $scope.category = $scope.categories.find(function (category) {
                return category.id == id;
            });
            getGroups();
        };
        $scope.groups = [];
        $scope.unassigned = [];
        function getGroups() {
            $scope.dataReady = false;
            $http.get(Routing.generate('_rest_list_groups_with_teams', {'categoryid': $scope.selectedCategory })).then(
                function (data) {
                    $scope.groups = data.data.groups;
                    $scope.unassigned = data.data.unassigned;
                    $scope.dataReady = true;
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.QMATCHPLANNING.GROUPS'));
                    $scope.groups = [];
                    $scope.unassigned = [];
                    $scope.dataReady = true;
                });
        }
    });