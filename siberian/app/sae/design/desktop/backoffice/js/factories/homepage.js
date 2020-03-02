App.factory('Backoffice', function ($http, Url) {
    var factory = {};

    factory.loadData = function () {
        return $http({
            method: 'GET',
            url: Url.get('backoffice/index/load'),
            cache: false,
            responseType: 'json'
        });
    };

    factory.loadExternalServices = function () {
        return $http({
            method: 'GET',
            url: Url.get('backoffice/index/load-services'),
            cache: false,
            responseType: 'json'
        });
    };

    factory.loadMessages = function () {
        return $http({
            method: 'GET',
            url: Url.get('backoffice/index/load-messages'),
            cache: false,
            responseType: 'json'
        });
    };

    factory.clearCache = function (cache_type) {
        return $http({
            method: 'GET',
            url: Url.get('backoffice/index/clearcache', { type: cache_type }),
            cache: false,
            responseType: 'json'
        });
    };

    factory.find = function () {
        return $http({
            method: 'GET',
            url: Url.get('backoffice/index/find'),
            cache: false,
            responseType: 'json'
        });
    };

    factory.sslWarning = function () {
        return $http({
            method: 'POST',
            url: Url.get('backoffice/advanced_configuration/sslwarning'),
            cache: false,
            responseType: 'json'
        });
    };

    return factory;
});
