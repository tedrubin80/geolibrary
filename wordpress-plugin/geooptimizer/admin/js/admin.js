(function ($) {
    'use strict';

    var lastResultText = '';

    function showResult(title, content) {
        var el = document.getElementById('geo-results');
        var copyButton = document.getElementById('geo-copy-results');

        if (!el) {
            return;
        }

        lastResultText = content || '';
        el.textContent = title + '\n\n' + lastResultText;

        if (copyButton) {
            copyButton.hidden = lastResultText === '';
        }
    }

    function setLoading(button, isLoading, loadingLabel) {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.dataset.originalText = button.textContent;
            button.textContent = loadingLabel;
            button.disabled = true;
            button.classList.add('is-busy');
            return;
        }

        button.textContent = button.dataset.originalText || button.textContent;
        button.disabled = false;
        button.classList.remove('is-busy');
    }

    function post(action, button, loadingLabel, onSuccess) {
        setLoading(button, true, loadingLabel);

        $.post(geoOptimizerAdmin.ajaxUrl, {
            action: action,
            nonce: geoOptimizerAdmin.nonce
        })
            .done(function (response) {
                if (!response || !response.success) {
                    showResult(
                        'Error',
                        (response && response.data && response.data.message) || 'Request failed.'
                    );
                    return;
                }

                onSuccess(response.data);
            })
            .fail(function () {
                showResult('Error', 'Request failed.');
            })
            .always(function () {
                setLoading(button, false, loadingLabel);
            });
    }

    $('#geo-generate-llms-txt').on('click', function () {
        var button = this;

        post(
            'geo_generate_llms_txt',
            button,
            geoOptimizerAdmin.strings.generating,
            function (data) {
                showResult('llms.txt Generated', data.content || '');
            }
        );
    });

    $('#geo-analyze-content').on('click', function () {
        var button = this;

        post(
            'geo_analyze_content',
            button,
            geoOptimizerAdmin.strings.analyzing,
            function (data) {
                showResult('Content Analysis', JSON.stringify(data, null, 2));
            }
        );
    });

    $('#geo-copy-results').on('click', function () {
        if (!lastResultText) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(lastResultText)
                .then(function () {
                    showResult('Copied', lastResultText);
                })
                .catch(function () {
                    showResult('Error', geoOptimizerAdmin.strings.copyFailed);
                });
            return;
        }

        showResult('Error', geoOptimizerAdmin.strings.copyFailed);
    });
}(jQuery));
