(function ($) {
    'use strict';

    function showResult(title, content) {
        var el = document.getElementById('geo-results');
        if (!el) {
            return;
        }
        el.textContent = title + '\n\n' + content;
    }

    function post(action, onSuccess) {
        $.post(geoOptimizerAdmin.ajaxUrl, {
            action: action,
            nonce: geoOptimizerAdmin.nonce
        })
            .done(function (response) {
                if (!response || !response.success) {
                    showResult('Error', (response && response.data && response.data.message) || 'Request failed.');
                    return;
                }
                onSuccess(response.data);
            })
            .fail(function () {
                showResult('Error', 'Request failed.');
            });
    }

    $('#geo-generate-llms-txt').on('click', function () {
        post('geo_generate_llms_txt', function (data) {
            showResult('llms.txt Generated', data.content || '');
        });
    });

    $('#geo-analyze-content').on('click', function () {
        post('geo_analyze_content', function (data) {
            showResult('Content Analysis', JSON.stringify(data, null, 2));
        });
    });
}(jQuery));
