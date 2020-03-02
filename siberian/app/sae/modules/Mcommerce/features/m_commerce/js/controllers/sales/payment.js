/*global
 App, angular, BASE_PATH
 */
angular
    .module('starter')
    .controller('MCommerceSalesPaymentViewController', function (Loader, $scope, $state, $stateParams, $translate,
                                                                 McommerceCart, McommerceSalesPayment, Dialog) {
    $scope.page_title = $translate.instant('Payment', 'm_commerce');

    McommerceCart.value_id = $stateParams.value_id;
    McommerceSalesPayment.value_id = $stateParams.value_id;
    $scope.value_id = $stateParams.value_id;

    $scope.loadContent = function () {
        Loader.show();

        McommerceCart
        .find()
        .then(function (data) {
            $scope.cart = data.cart;

            McommerceSalesPayment
            .findPaymentMethods()
            .then(function (resultMethods) {
                $scope.paymentMethods = resultMethods.paymentMethods;

                $scope.paymentMethodId = resultMethods.paymentMethods
                    .reduce(function (paymentMethodId, paymentMethod) {
                        return ($scope.cart.paymentMethodId === paymentMethod.id) ?
                            paymentMethod.id : paymentMethodId;
                    }, null);

                // Only if we have at least one product!
                // Free purchase we can skip the payment method selection!
                if ($scope.paymentMethods.length === 1 &&
                    $scope.paymentMethods[0].code === 'free' &&
                    $scope.cart.lines.length > 0) {
                    $scope.cart.paymentMethodId = $scope.paymentMethods[0].id;
                    $scope.updatePaymentInfos();
                }
            }).then(function () {
                $scope.is_loading = false;
                Loader.hide();
            });
        }, function () {
            $scope.is_loading = false;
            Loader.hide();
        });
    };

    /**
     * Test 123
     */
    $scope.updatePaymentInfos = function () {
        if (!$scope.is_loading) {
            $scope.is_loading = true;
            Loader.show();

            var postParameters = {
                'payment_method_id': $scope.cart.paymentMethodId
            };

            McommerceSalesPayment.updatePaymentInfos(postParameters)
                .then(function (data) {
                    $scope.goToConfirmationPage();
                }, function (data) {
                    if (data && angular.isDefined(data.message)) {
                        Dialog.alert('', data.message, 'OK');
                    }
                }).then(function () {
                    $scope.is_loading = false;
                    Loader.hide();
                });
        }
    };

    /**
     *
     */
    $scope.goToConfirmationPage = function () {
        if ($scope.cart.paymentMethodId) {
            $state.go('mcommerce-sales-confirmation', {
                value_id: $stateParams.value_id
            });
        } else {
            Dialog.alert('', 'Please choose a payment method.', 'OK');
        }
    };

    $scope.right_button = {
        action: $scope.updatePaymentInfos,
        label: $translate.instant('Next', 'm_commerce')
    };

    $scope.loadContent();
});
