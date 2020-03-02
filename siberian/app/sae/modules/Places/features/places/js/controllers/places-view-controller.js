/**
 * @version 4.18.5
 */
angular
.module('starter')
.controller('PlacesViewController', function ($filter, $scope, $rootScope, $state, $stateParams, $translate,
                                              $location, Places, SocialSharing, Url) {
    angular.extend($scope, {
        is_loading: true,
        value_id: $stateParams.value_id,
        social_sharing_active: false,
        use_pull_to_refresh: true,
        pull_to_refresh: false,
        card_design: false
    });

    $scope.blankImage = "./features/places/assets/templates/l1/img/blank-700-440.png";

    Places.setValueId($stateParams.value_id);

    $scope.loadContent = function () {
        Places.getPlace($stateParams.page_id)
            .then(function (data) {
                $scope.social_sharing_active = (data.social_sharing_active && $rootScope.isNativeApp);
                $scope.blocks = data.blocks;

                $scope.blockChunks = $filter('chunk')(angular.copy($scope.blocks),
                    Math.ceil($scope.blocks.length / 2));

                $scope.place = data.page;
                $scope.page_title = data.page_title;
            }).then(function () {
                $scope.is_loading = false;
            });
    };

    $scope.share = function () {
        var file;
        var address = "";
        var link = undefined;
        angular.forEach($scope.blocks, function (block) {
            if (block.gallery) {
                if (block.gallery.length > 0 && file === null) {
                    file = block.gallery[0].url;
                }
            }
            if (block.type === "address") {
                address = block.address;
                if (block.website !== "" && block.show_website) {
                    link = block.website;
                }
            }
        });

        var message = "Check this place!\n" + $scope.place.title + "\n" + address;

        SocialSharing.share(undefined, message, undefined, link, file);
    };

    $scope.onShowMap = function (block) {
        if ($rootScope.isNotAvailableOffline()) {
            return;
        }

        var params = {};

        if (block.latitude && block.longitude) {
            params.latitude = block.latitude;
            params.longitude = block.longitude;
        } else if (block.address) {
            params.address = encodeURI(block.address);
        }

        params.title = block.label;
        params.value_id = $scope.value_id;

        $location.path(Url.get('map/mobile_view/index', params));
    };

    $scope.loadContent();

});