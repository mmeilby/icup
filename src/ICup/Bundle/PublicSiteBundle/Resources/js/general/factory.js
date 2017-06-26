/*
 ------------------------------------
 TOURNAMENT DATA MODEL FACTORY
 ------------------------------------
 */
angular.module('dataSourceModule.factory', [])
    .factory('Tournament', function ($http, $mdDialog, tournamentid) {
        var tournament = { 'id': tournamentid };
        var categories = [], sites = [], venues = [], timeslots = [], events = [];
        $http.get(Routing.generate('_rest_get_tournament', { 'tournamentid': tournamentid })).then(
            function(data) {
                tournament = data.data.tournament;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.TOURNAMENTOPTIONS.TITLE.CHG'));
            });
        $http.get(Routing.generate('_rest_list_categories', { 'tournamentid': tournamentid })).then(
            function(data) {
                categories = data.data;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTCATEGORIES.CAPTION'));
            });
        $http.get(Routing.generate('_rest_list_sites', { 'tournamentid': tournamentid })).then(
            function(data) {
                sites = data.data;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
            });
        $http.get(Routing.generate('_rest_list_playgrounds', { 'tournamentid': tournamentid })).then(
            function(data) {
                venues = data.data;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTPLAYGROUNDS.CAPTION'));
            });
        $http.get(Routing.generate('_rest_list_timeslots', { 'tournamentid': tournamentid })).then(
            function(data) {
                timeslots = data.data;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTTIMESLOTS.CAPTION'));
            });
        return {
            getTournament: function () { return tournament; },
            getCategories: function () { return categories; },
            setCategories: function (cat) { categories = cat; },
            getSites: function () { return sites; },
            setSites: function (s) { sites = s; },
            getVenues: function () { return venues; },
            setVenues: function (v) { venues = v; },
            getTimeslots: function () { return timeslots; },
            setTimeslots: function (t) { timeslots = t; },
            getEvents: function () { return events; },
            setEvents: function (e) { events = e; }
        };
    });
