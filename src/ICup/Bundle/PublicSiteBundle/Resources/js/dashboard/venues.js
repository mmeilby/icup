/*
 ------------------------------------
 LIST VENUES
 ------------------------------------
 */
angular.module('tournamentBoardModule.venues', [])
    .controller('venuesController', function($scope, $http, $mdDialog, $mdMedia, $mdToast, $document, Tournament, uiGmapGoogleMapApi, $filter, templateDirectory) {
        $scope.map = {
            zoom: 18,
            options: {
                streetViewControl: false,
                mapTypeControl: true,
                scaleControl: false,
                rotateControl: false,
                zoomControl: true
            },
            showTraficLayer:false
        };
        $scope.marker = {
            options: {
                draggable: false,
                labelAnchor: "30 60",
                labelClass: "bold"
            },
            events: {}
        };
        $scope.isOffline = true;
        uiGmapGoogleMapApi.then(function(maps) {
            $scope.googleVersion = maps.version;
            maps.visualRefresh = true;
            $scope.map.options.mapTypeId = google.maps.MapTypeId.SATELLITE;
            $scope.isOffline = false;
        });

        $scope.navigatorConfig = {
            selectMode: "day",
            showMonths: 3,
            skipMonths: 1,
            locale: 'da-dk',    // de-de en-gb es-es fr-fr it-it pl-pl
            weekStarts: 1,
            onTimeRangeSelected: function(args) {
                $scope.calendarConfig.startDate = args.day;
            }
        };

        $scope.calendarConfig = {
            cssClassPrefix: "calendar_icup",
            businessBeginsHour: 8,
            businessEndsHour: 24,
            columnMarginRight: 0,
            days: 3,
            locale: 'da-dk',    // de-de en-gb es-es fr-fr it-it pl-pl
            headerDateFormat: "ddd d. MMM",
            onTimeRangeSelected: function (args) {
                var dlgScope = $scope.$new();
                dlgScope.pattr_object = {
                    start: new DayPilot.Date(args.start.value),
                    end: new DayPilot.Date(args.end.value),
                    categories: [],
                    timeslot: { id: 0 },
                    finals: false,
                    classification: 0,
                    op: 'add'
                };
                pattrDialog(dlgScope, function() {
                    $http.post(Routing.generate('rest_pattr_create', {
                        playgroundid: $scope.selectedVenue,
                        timeslotid: dlgScope.pattr_object.timeslot.id,
                        categories: dlgScope.pattr_object.categories
                    }), {
                        date: dlgScope.pattr_object.start.toString('yyyy-MM-dd'),
                        start: dlgScope.pattr_object.start.toString('HH:mm'),
                        end: dlgScope.pattr_object.end.toString('HH:mm'),
                        finals: dlgScope.pattr_object.finals,
                        classification: dlgScope.pattr_object.classification
                    })
                        .then(
                            function (data) {
                                var catlist = [];
                                angular.forEach(dlgScope.pattr_object.categories, function(category) {
                                    this.push(category.name);
                                }, catlist);
                                var e = {
                                    start: dlgScope.pattr_object.start,
                                    end: dlgScope.pattr_object.end,
                                    id: data.data.id,
                                    text: (dlgScope.pattr_object.finals ? '<span class="fa fa-trophy text-warning"></span><br />' : '<span class="fa fa-gavel text-info"></span><br />')+
                                    dlgScope.pattr_object.timeslot.name+
                                    (catlist.length > 0 ? '<br />'+catlist.join(',') : '')+
                                    (dlgScope.pattr_object.classification > 0 ? '<br />'+Translator.trans('GROUPCLASS.'+dlgScope.pattr_object.classification, {}, 'tournament') : '')
                                };
                                $scope.events.push(e);
                                $mdToast.show(
                                    $mdToast.simple()
                                        .textContent(Translator.trans('FORM.PLAYGROUNDATTR.UPDATED'))
                                        .position('top right')
                                        .hideDelay(3000)
                                        .parent($document[0].querySelector('#dp'))
                                );
                            },
                            function (response) {
                                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUNDATTR.TITLE.ADD'));
                            });
                });
                $scope.dp.clearSelection();
            },
            onEventClicked: function (args) {
                var pattrid = args.e.id();
                var pattr = $scope.pattrs.find(function (pattr) {
                    return pattr.id == pattrid;
                });
                var dlgScope = $scope.$new();
                dlgScope.pattr_object = {
                    start: new DayPilot.Date(args.e.start()),
                    end: new DayPilot.Date(args.e.end()),
                    categories: pattr.categories,
                    timeslot: pattr.timeslot,
                    finals: pattr.finals,
                    classification: pattr.classification ? pattr.classification : 0,
                    pattr: pattr,
                    op: 'chg'
                };
                pattrDialog(dlgScope, function() {
                    if (dlgScope.pattr_object.remove) {
                        $http.delete(Routing.generate('rest_pattr_delete', {'pattrid': dlgScope.pattr_object.pattr.id })).then(
                            function () {
                                var newevents = [];
                                var pattrid = dlgScope.pattr_object.pattr.id;
                                angular.forEach($scope.events, function(event) {
                                    if (event.id != pattrid) {
                                        this.push(event);
                                    }
                                }, newevents);
                                $scope.events = newevents;
                                $mdToast.show(
                                    $mdToast.simple()
                                        .textContent(Translator.trans('FORM.PLAYGROUNDATTR.DELETED'))
                                        .position('top right')
                                        .hideDelay(3000)
                                        .parent($document[0].querySelector('#dp'))
                                );
                            },
                            function (response) {
                                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUNDATTR.TITLE.DEL'));
                            });
                    }
                    else {
                        $http.post(Routing.generate('rest_pattr_update', {
                            pattrid: dlgScope.pattr_object.pattr.id,
                            timeslotid: dlgScope.pattr_object.timeslot.id,
                            categories: dlgScope.pattr_object.categories
                        }), {
                            date: dlgScope.pattr_object.start.toString('yyyy-MM-dd'),
                            start: dlgScope.pattr_object.start.toString('HH:mm'),
                            end: dlgScope.pattr_object.end.toString('HH:mm'),
                            finals: dlgScope.pattr_object.finals,
                            classification: dlgScope.pattr_object.classification
                        })
                            .then(
                                function () {
                                    var catlist = [];
                                    angular.forEach(dlgScope.pattr_object.categories, function(category) {
                                        this.push(category.name);
                                    }, catlist);
                                    var e = args.e;
                                    e.text(
                                        (dlgScope.pattr_object.finals ? '<span class="fa fa-trophy text-warning"></span><br />' : '<span class="fa fa-gavel text-info"></span><br />')+
                                        dlgScope.pattr_object.timeslot.name+
                                        (catlist.length > 0 ? '<br />'+catlist.join(',') : '')+
                                        (dlgScope.pattr_object.classification > 0 ? '<br />'+Translator.trans('GROUPCLASS.'+dlgScope.pattr_object.classification, {}, 'tournament') : '')
                                    );
                                    $mdToast.show(
                                        $mdToast.simple()
                                            .textContent(Translator.trans('FORM.PLAYGROUNDATTR.UPDATED'))
                                            .position('top right')
                                            .hideDelay(3000)
                                            .parent($document[0].querySelector('#dp'))
                                    );
                                },
                                function (response) {
                                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUNDATTR.TITLE.CHG'));
                                });

                    }
                });
            },
            onEventResized: function (args) {
                var pattrid = args.e.id();
                var pattr = $scope.pattrs.find(function (pattr) {
                    return pattr.id == pattrid;
                });
                changePattr(pattr, new DayPilot.Date(args.newStart.value), new DayPilot.Date(args.newEnd.value));
            },
            onEventMoved: function (args) {
                var pattrid = args.e.id();
                var pattr = $scope.pattrs.find(function (pattr) {
                    return pattr.id == pattrid;
                });
                changePattr(pattr, new DayPilot.Date(args.newStart.value), new DayPilot.Date(args.newEnd.value));
            }
            };
        function changePattr(pattr, newStart, newEnd) {
            $http.post(Routing.generate('rest_pattr_update', {
                pattrid: pattr.id,
                timeslotid: pattr.timeslot.id,
                categories: pattr.categories
            }), {
                date: newStart.toString('yyyy-MM-dd'),
                start: newStart.toString('HH:mm'),
                end: newEnd.toString('HH:mm'),
                finals: pattr.finals,
                classification: pattr.classification
            })
                .then(
                    function () {
                        $mdToast.show(
                            $mdToast.simple()
                                .textContent(Translator.trans('FORM.PLAYGROUNDATTR.UPDATED'))
                                .position('top right')
                                .hideDelay(3000)
                                .parent($document[0].querySelector('#dp'))
                        );
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUNDATTR.TITLE.CHG'));
                    });
        }
        $scope.events = [];

        $scope.tournament = Tournament.getTournament();
        $http.get(Routing.generate('_rest_get_match_calendar', { 'tournamentid': $scope.tournament.id })).then(function(data) {
            $scope.calendarConfig.startDate = $filter('date')(new Date(data.data.start), 'yyyy-MM-dd');
            $scope.navi.select($filter('date')(new Date(data.data.start), 'yyyy-MM-dd'));
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
        $scope.selectVenue = function (id) {
            $scope.selectedVenue = id;
            $scope.venue = $scope.venues.find(function (venue) {
                return venue.id == id;
            });
            if (angular.isDefined($scope.venue)) {
                var separator = $scope.venue.location.indexOf(',');
                var lat = $scope.venue.location.substr(0, separator),
                    lng = $scope.venue.location.substr(separator+1);
                $scope.map.center = { latitude: lat, longitude: lng };
                $scope.marker.point = { latitude: lat, longitude: lng };
                $scope.marker.options.labelContent = Translator.trans('FORM.PLAYGROUND.NO')+': ' + $scope.venue.no;
                $scope.marker.options.title = $scope.venue.name;
                $http.get(Routing.generate('_rest_list_pattrs', { 'playgroundid': $scope.venue.id })).then(
                    function(data) {
                        $scope.pattrs = data.data;
                        $scope.events = [];
                        angular.forEach(data.data, function(pattr, key) {
                            var catlist = [];
                            angular.forEach(pattr.categories, function(category) {
                                this.push(category.name);
                            }, catlist);
                            this.push({
                                start: pattr.date.ts+"T"+pattr.start.ts+":00",
                                end: pattr.date.ts+"T"+pattr.end.ts+":00",
                                id: pattr.id,
                                text: (pattr.finals ? '<span class="fa fa-trophy text-warning"></span><br />' : '<span class="fa fa-gavel text-info"></span><br />')+
                                pattr.timeslot.name+
                                (catlist.length > 0 ? '<br />'+catlist.join(',') : '')+
                                (pattr.classification > 0 ? '<br />'+Translator.trans('GROUPCLASS.'+pattr.classification, {}, 'tournament') : '')
                            });
                        }, $scope.events);
                    },
                    function (response) {
                        ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
                    });
            }
        };
        $scope.addVenue = function(ev) {
            var dlgScope = $scope.$new();
            dlgScope.venue_object = {
                name: '',
                site: { 'id': 0 },
                no: '',
                location: false,
                lat: 0,
                lng: 0,
                weight: 0,
                op: 'add'
            };
            venueDialog(dlgScope, ev, function(venue_object) {
                $http.post(Routing.generate('rest_playground_create', { 'siteid': dlgScope.venue_object.site.id }), {
                    'name': dlgScope.venue_object.name,
                    'no': dlgScope.venue_object.no,
                    'location': dlgScope.venue_object.location ?
                    dlgScope.venue_object.lat+','+ dlgScope.venue_object.lng :
                        '',
                    'weight': dlgScope.venue_object.weight
                })
                    .then(
                        function (data) {
                            var newid = data.data.id;
                            $http.get(Routing.generate('_rest_list_playgrounds', { 'tournamentid': $scope.tournament.id })).then(
                                function(data) {
                                    $scope.venues = data.data;
                                    $scope.selectVenue(newid);
                                    Tournament.setVenues($scope.venues);
                                },
                                function (response) {
                                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
                                });
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUND.TITLE.ADD'));
                        });
            });
        };
        $scope.updateVenue = function(ev) {
            var dlgScope = $scope.$new();
            var separator = $scope.venue.location.indexOf(',');
            var lat = 42, lng = 13;
            if (separator >= 0) {
                lat = $scope.venue.location.substr(0, separator);
                lng = $scope.venue.location.substr(separator+1);
            }
            dlgScope.venue_object = {
                name: $scope.venue.name,
                site: $scope.venue.site,
                no: $scope.venue.no,
                location: separator >= 0,
                lat: lat,
                lng: lng,
                weight: $scope.venue.weight,
                op: 'chg'
            };
            venueDialog(dlgScope, ev, function(venue_object) {
                $http.post(Routing.generate('rest_playground_update', { 'playgroundid': $scope.venue.id }), {
                    'name': dlgScope.venue_object.name,
                    'no': dlgScope.venue_object.no,
                    'location': dlgScope.venue_object.location ?
                    dlgScope.venue_object.lat+','+ dlgScope.venue_object.lng :
                        '',
                    'weight': dlgScope.venue_object.weight
                })
                    .then(
                        function () {
                            $http.get(Routing.generate('_rest_list_playgrounds', { 'tournamentid': $scope.tournament.id })).then(
                                function(data) {
                                    $scope.venues = data.data;
                                    $scope.selectVenue(dlgScope.venue.id);
                                    Tournament.setVenues($scope.venues);
                                },
                                function (response) {
                                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
                                });
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUND.TITLE.ADD'));
                        });
            });
        };
        $scope.delVenue = function(ev) {
            $http.delete(Routing.generate('rest_playground_delete', {'playgroundid': $scope.venue.id })).then(
                function () {
                    $http.get(Routing.generate('_rest_list_playgrounds', { 'tournamentid': $scope.tournament.id })).then(
                        function(data) {
                            $scope.venues = data.data;
                            $scope.selectVenue(0);
                            Tournament.setVenues($scope.venues);
                        },
                        function (response) {
                            ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
                        });
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUND.TITLE.DEL'));
                });
        };
        $scope.addSite = function(site) {
            $http.post(Routing.generate('rest_site_create', { tournamentid: $scope.tournament.id }), { name: site.name }).then(
                function (data) {
                    site.id = data.data.id;
                    $scope.listSites(site);
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.PLAYGROUND.TITLE.ADD'));
                    $scope.listSites({ id: 0 });
                });
        };
        $scope.delSite = function(site) {
            $http.delete(Routing.generate('rest_site_delete', { siteid: site.id })).then(
                function () {
                    $scope.listSites({ id: 0 });
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.SITE.TITLE.DEL'));
                    $scope.listSites({ id: 0 });
                });
        };
        $scope.selectSite = function(selectedSite) {
            $scope.selectedSite = selectedSite.id;
            $scope.site = $scope.sites.find(function (site) {
                return site.id == selectedSite.id;
            });
            $scope.selectVenue(0);
        };
        $scope.transformSite = function(sitename) {
            return { id: 0, name: sitename };
        };
        $scope.siteFilter = function (venue) {
            if ($scope.selectedSite) {
                if (venue.site.id != $scope.selectedSite) return false;
            }
            return true;
        };
        $scope.listSites = function(site) {
            $scope.selectVenue(0);
            $http.get(Routing.generate('_rest_list_sites', { tournamentid: $scope.tournament.id })).then(
                function(data) {
                    $scope.sites = data.data;
                    Tournament.setSites($scope.sites);
                    $scope.selectSite(site);
                },
                function (response) {
                    ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
                    $scope.selectSite({ id: 0 });
                });
        };
        function venueDialog(dlgScope, ev, submitfn) {
            var lat = 42, lng = 13;
            if (dlgScope.venue_object.location) {
                lat = dlgScope.venue_object.lat;
                lng = dlgScope.venue_object.lng;
            }
            dlgScope.map = {
                center: { latitude: lat, longitude: lng },
                zoom: 15,
                options: {
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    streetViewControl: false,
                    mapTypeControl: true,
                    scaleControl: false,
                    rotateControl: false,
                    zoomControl: true
                }
            };
            dlgScope.marker = {
                point: { latitude: lat, longitude: lng },
                options: {
                    draggable: true,
                    labelContent: Translator.trans('FORM.PLAYGROUND.NO')+': ' + dlgScope.venue_object.no,
                    title: dlgScope.venue_object.name,
                    labelAnchor: "30 60",
                    labelClass: "bold"
                },
                events: {
                    dragend:
                        function (marker, eventName, args) {
                            dlgScope.venue_object.lat = marker.getPosition().lat();
                            dlgScope.venue_object.lng = marker.getPosition().lng();
                        }
                }
            };
            $mdDialog.show({
                controller: DialogController,
                scope: dlgScope,
                templateUrl: templateDirectory+'editvenuedlg.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        }).then(submitfn);
        }
        function pattrDialog(dlgScope, submitfn) {
            $mdDialog.show({
                controller: function ($scope, $mdDialog) {
                    $scope.submit = function(pattr_object) {
                        pattr_object.categories = [];
                        angular.forEach(this.categoryList, function(category) {
                            if (category.selected) {
                                this.push(category);
                            }
                        }, pattr_object.categories);
                        pattr_object.remove = false;
                        $mdDialog.hide(pattr_object);
                    };
                    $scope.remove = function(pattr_object) {
                        pattr_object.remove = true;
                        $mdDialog.hide(pattr_object);
                    };
                    $scope.cancel = function() {
                        $mdDialog.cancel();
                    };
                },
                scope: dlgScope,
                templateUrl: templateDirectory+'editpattrdlg.html',
                parent: angular.element(document.body),
                clickOutsideToClose:true,
                fullscreen: $mdMedia('sm') || $mdMedia('xs')
        }).then(submitfn);
        }
    })
    .controller('CategoryCtrl', function($scope) {
        $scope.$parent.categoryList = [];
        angular.forEach($scope.$parent.categories, function (category) {
            var cat = $scope.$parent.pattr_object.categories.find(function (ctg) {
                return ctg.id == category.id;
            });
            this.push({
                id: category.id,
                name: category.name,
                classification_translated: category.classification_translated,
                selected: cat != null
            });
        }, $scope.$parent.categoryList);
    });
