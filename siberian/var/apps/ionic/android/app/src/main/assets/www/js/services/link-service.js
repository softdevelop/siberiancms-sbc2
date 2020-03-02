/**
 * LinkService
 *
 * @author Xtraball SAS
 * @version 4.18.3
 */
angular
.module('starter')
.service('LinkService', function ($rootScope, $translate, $window, SB, Dialog) {
    return {
        openLink: function (url, options, external_browser) {
            var supportOptions = [
                'location',
                'hidden',
                'beforeload',
                'clearcache',
                'clearsessioncache',
                'closebuttoncaption',
                'closebuttoncolor',
                'footer',
                'footercolor',
                'hardwareback',
                'hidenavigationbuttons',
                'hideurlbar',
                'navigationbuttoncolor',
                'toolbarcolor',
                'lefttoright',
                'zoom',
                'mediaPlaybackRequiresUserAction',
                'shouldPauseOnSuspend',
                'useWideViewPort',
                'cleardata',
                'disallowoverscroll',
                'toolbar',
                'toolbartranslucent',
                'lefttoright',
                'enableViewportScale',
                'allowInlineMediaPlayback',
                'keyboardDisplayRequiresUserAction',
                'suppressesIncrementalRendering',
                'presentationstyle',
                'transitionstyle',
                'toolbarposition',
                'hidespinner',
            ];
            var target = '_blank';
            var inAppBrowserOptions = [];
            var _external_browser = (external_browser === undefined) ? false : external_browser;
            var _deviceOptions = {};
            try {
                switch (DEVICE_TYPE) {
                    case SB.DEVICE.TYPE_ANDROID:
                        _deviceOptions = options['android'];
                        break;
                    case SB.DEVICE.TYPE_IOS:
                        _deviceOptions = options['ios'];
                        break;
                }
            } catch (e) {
                _deviceOptions = {};
            }
            var _options = angular.extend({}, {
                'toolbarcolor': $window.colors.header.backgroundColorHex,
                'location': 'no',
                'toolbar': 'yes',
                'zoom': 'no',
                'enableViewPortScale': 'yes',
                'closebuttoncaption': $translate.instant('Done'),
                'transitionstyle': 'crossdissolve'
            }, _deviceOptions);

            // Prevent opening new windows in the overview!
            if (isOverview && _external_browser) {
                return Dialog.alert('Overview', 'External browser is not available in the overview.', 'OK', 2350);
            }

            // HTML5 forced on Browser devices
            if (DEVICE_TYPE === SB.DEVICE.TYPE_BROWSER) {
                if (_external_browser ||
                    /.*\.pdf($|\?)/.test(url)) {
                    target = '_system';
                }
                // Enforce inAppBrowser fallback with location!
                return cordova.InAppBrowser.open(url, target, 'location=yes');
            }

            // External browser
            if (_external_browser || /.*\.pdf($|\?)/.test(url)) {
                return cordova.plugins.browsertab.openUrl(url, {});
            }

            // Enforcing target '_self' for Android tel: links!
            if (/^(tel:).*/.test(url) &&
                (DEVICE_TYPE === SB.DEVICE.TYPE_ANDROID)) {
                target = '_self';
            }

            for (var key in _options) {
                // Push only allowed options!
                if (supportOptions.indexOf(key) > -1) {
                    var value = _options[key];
                    inAppBrowserOptions.push(key + '=' + value);
                }
            }
            var finalOptions = inAppBrowserOptions.join(',');

            return cordova.InAppBrowser.open(url, target, finalOptions);
        }
    };
});
