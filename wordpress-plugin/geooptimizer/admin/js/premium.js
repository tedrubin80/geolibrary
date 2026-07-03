(function ($) {
    'use strict';

    function post(action, data, onSuccess, output) {
        $.post(geoOptimizerPremium.ajaxUrl, Object.assign({
            action: action,
            nonce: geoOptimizerPremium.nonce
        }, data))
            .done(function (response) {
                if (!response || !response.success) {
                    output.text((response && response.data && response.data.message) || 'Request failed.');
                    return;
                }
                onSuccess(response.data);
            })
            .fail(function () {
                output.text('Request failed.');
            });
    }

    $('#geo-load-dashboard').on('click', function () {
        var output = $('#geo-dashboard-output');
        post('geo_premium_dashboard', {
            identifier: geoOptimizerPremium.identifier
        }, function (data) {
            output.text(JSON.stringify(data, null, 2));
        }, output);
    });

    $('#geo-run-bulk').on('click', function () {
        var output = $('#geo-bulk-output');
        post('geo_bulk_analyze', {
            items: $('#geo-bulk-items').val()
        }, function (data) {
            output.text(JSON.stringify(data, null, 2));
        }, output);
    });

    $('#geo-run-compare').on('click', function () {
        var output = $('#geo-compare-output');
        post('geo_compare_competitors', {
            primary_content: $('#geo-primary-content').val(),
            competitors: $('#geo-competitors-json').val()
        }, function (data) {
            output.text(JSON.stringify(data, null, 2));
        }, output);
    });
}(jQuery));
