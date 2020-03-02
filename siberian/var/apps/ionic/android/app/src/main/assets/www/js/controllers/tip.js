/*global
 App, angular, BASE_PATH
 */

angular.module("starter").controller("TipController", function($scope, $stateParams, Tip) {

    angular.extend($scope, {
        value_id        : $stateParams.value_id,
        bill            : {},
        format          : null,
        card_design     : false
    });

    Tip.setValueId($stateParams.value_id);

    $scope.loadContent = function() {
        Tip.findAll()
            .then(function(data) {
                $scope.page_title = data.page_title;
                $scope.format     = data.format;
            });
    };

    $scope.calculate = function() {

        if($scope.bill.amount && $scope.bill.percent) {

            var global = ($scope.bill.amount * ($scope.bill.percent / 100));
            $scope.global_tip = global.toFixed(2);

            if($scope.bill.number < 0) {
                $scope.bill.number = null;
            }

            if($scope.bill.number) {
                var result = ($scope.global_tip / $scope.bill.number);
                $scope.each_tip = result.toFixed(2);
                $scope.each_tip = $scope.format.replace(",00","").replace(".00","").replace("1", $scope.each_tip);
            }

            $scope.global_tip = $scope.format.replace(",00","").replace(".00","").replace("1",$scope.global_tip);
        }

    };

    $scope.loadContent();
});