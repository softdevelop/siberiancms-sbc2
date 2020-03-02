/**
 * Privacy policy controller.
 */
angular.module('starter').controller('PrivacyPolicyController', function ($scope, $stateParams, Application) {
    angular.extend($scope, {
        value_id: $stateParams.value_id,
        page_title: Application.privacyPolicy.title,
        privacy_policy: Application.privacyPolicy.text,
        privacy_policy_gdpr: Application.privacyPolicy.gdpr,
        card_design: false,
        gdpr: {
            isEnabled: Application.gdpr.isEnabled
        }
    });
});
