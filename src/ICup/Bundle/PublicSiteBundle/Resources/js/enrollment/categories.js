/*
 ------------------------------------
 STEP 3 - CATEGORY ENROLLMENT
 ------------------------------------
 */
angular.module('enrollmentModule.categories', [])
    .config(function($routeProvider) {
        $routeProvider.
        when('/store', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/productlist.htm',
            controller: 'storeController'
        }).
        when('/products/:productSku', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/product.htm',
            controller: 'storeController'
        }).
        when('/cart', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/shoppingCart.htm',
            controller: 'cartController'
        }).
        when('/checkout', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/shoppingCheckout.htm',
            controller: 'checkoutController'
        }).
        when('/charge', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/shoppingCharge.htm',
            controller: 'chargeController'
        }).
        when('/final', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/shoppingConfirmation.htm',
            controller: 'confirmationController'
        }).
        when('/incomp', {
            templateUrl: '/icup/web/bundles/icuppublicsite/templates/incompatibleCart.htm',
            controller: 'storeController'
        }).
        otherwise({
            redirectTo: '/store'
        });
    })
    .controller('enrollmentController', function($scope, $http, $filter, Tournament, Store, imgDirectory, countryByLocale) {
        if (!storageAvailable('sessionStorage')) {
            window.alert("SessionStorage is not available. Can not process online booking.");
            window.location = Routing.generate('_rest_get_category_metrics');
        }
        $scope.imgDir = imgDirectory;

        $scope.store = Store.store;
        $scope.cart = Store.cart;

        $scope.tournament = Tournament.getTournament();
        $scope.$watch(function () { return Tournament.getTournament(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) $scope.tournament = newValue;
        });
        $scope.categories = Tournament.getCategories();
        $scope.$watch(function () { return Tournament.getCategories(); }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.categories = newValue;
                $scope.categoryMetrics = [];
                for (idx in $scope.categories) {
                    $http.get(Routing.generate('_rest_get_category_metrics', { 'categoryid': $scope.categories[idx].id, 'date': $filter('date')($scope.cart.orderdate, 'yyyy-MM-dd') })).then(
                        function(data) {
                            $scope.categoryMetrics.push(data.data);
                            if ($scope.categories.length == $scope.categoryMetrics.length) {
                                $scope.store.setProducts($scope.categories, $scope.categoryMetrics);
                            }
                        });
                }
            }
        });

        $scope.club = sessionStorage.getItem('club');
        $scope.country = { value: JSON.parse(sessionStorage.getItem('country')) };
        $scope.manager = sessionStorage.getItem('manager');
        $scope.m_mobile = sessionStorage.getItem('m_mobile');
        $scope.m_email = sessionStorage.getItem('m_email');

        $http.get(Routing.generate('_rest_list_countries')).then(
            function(data) {
                $scope.countries = [];
                for (var key in data.data) {
                    var country = { id: key, name: data.data[key] };
                    $scope.countries.push(country);
                    if ($scope.country.value == null && countryByLocale == key) {
                        $scope.country.value = country;
                    }
                }
            });
    })
    .controller('storeController', function($scope, $routeParams) {
        // use routing to pick the selected product
        if ($routeParams.productSku != null) {
            $scope.product = $scope.store.getProduct($routeParams.productSku);
        }
    })
    .controller('cartController', function($scope) {
        $scope.$watch(function () { return $scope.club; }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.$parent.club = newValue;
                sessionStorage.setItem('club', $scope.club);
            }
        });
        $scope.$watch(function () { return $scope.country.value; }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.$parent.country.value = newValue;
                sessionStorage.setItem('country', JSON.stringify($scope.country.value));
            }
        });
        $scope.$watch(function () { return $scope.manager; }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.$parent.manager = newValue;
                sessionStorage.setItem('manager', $scope.manager);
            }
        });
        $scope.$watch(function () { return $scope.m_mobile; }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.$parent.m_mobile = newValue;
                sessionStorage.setItem('m_mobile', $scope.m_mobile);
            }
        });
        $scope.$watch(function () { return $scope.m_email; }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                $scope.$parent.m_email = newValue;
                sessionStorage.setItem('m_email', $scope.m_email);
            }
        });
    })
    .controller('checkoutController', function($scope, $location) {
        var handler = StripeCheckout.configure({
            key: 'pk_test_6pRNASCoBOKtIshFeQd4XMUh',
            image: $scope.imgDir+'IWC/logo.png',
            locale: 'auto',
            token: function(token, arg) {
                sessionStorage.setItem('stripe_token', JSON.stringify(token));
                window.location = "#/charge";
            }
        });

        $scope.stripe = function() {
            handler.open({
                name: 'INTERAMNIA WORLD CUP',
                email: $scope.m_email,
                description: 'Holdtilmelding',
                zipCode: true,
                currency: $scope.cart.currency,
                amount: $scope.cart.getTotalPrice()*100
            });
        };

        // Close Checkout on page navigation:
        window.addEventListener('popstate', function() {
            handler.close();
        });
    })
    .controller('chargeController', function($scope, $http, $mdDialog, $filter) {
        $scope.stripetoken = JSON.parse(sessionStorage.getItem('stripe_token'));
        if (undefined == $scope.stripetoken) {
            window.location = "#/";
            return;
        }
        var enrolled_teams = [];
        angular.forEach($scope.cart.items, function (item) {
            var category = $scope.categories.find(function (category) {
                return category.name == item.sku;
            });
            enrolled_teams.push({ id: category.id, quantity: item.quantity });
        });
        $http.post(Routing.generate('rest_enroll_team_checkout', { tournamentid: $scope.tournament.id }), {
            token: $scope.stripetoken.id,
            tx_timestamp: $filter('date')($scope.cart.orderdate, 'yyyy-MM-dd'),
            enrolled: enrolled_teams,
            club: { name: $scope.club, country: $scope.country.value.id },
            manager: { name: $scope.manager, email: $scope.m_email, mobile: $scope.m_mobile }
        })
        .then(
            function(data) {
                window.location = "#/final";
                return;
            },
            function (response) {
                ShowError($mdDialog, response.data.errors, Translator.trans('FORM.LISTSITES.CAPTION'));
            });
    })
    .controller('confirmationController', function($scope) {
    })
    .config(function($mdThemingProvider) {
        $mdThemingProvider.theme('green').backgroundPalette('green');
        $mdThemingProvider.theme('dark-grey').backgroundPalette('grey').dark();
        $mdThemingProvider.theme('dark-orange').backgroundPalette('orange').dark();
        $mdThemingProvider.theme('dark-purple').backgroundPalette('deep-purple').dark();
        $mdThemingProvider.theme('dark-blue').backgroundPalette('blue').dark();
    });
