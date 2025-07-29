/**
 * GEO Optimizer Admin JavaScript
 * 
 * Handles all admin interface interactions and AJAX requests
 */

jQuery(document).ready(function($) {
    
    // Initialize dashboard
    if ($('.geo-optimizer-dashboard').length) {
        initDashboard();
    }
    
    // Initialize content analysis
    if ($('.geo-content-analysis').length) {
        initContentAnalysis();
    }
    
    // Initialize tools page
    if ($('.geo-tools-grid').length) {
        initTools();
    }
    
    // Post editor meta box interactions
    initPostEditor();
    
    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Generate llms.txt button
        $('#generate-llms-txt').on('click', function() {
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text(geoOptimizer.strings.analyzing).prop('disabled', true);
            
            $.post(geoOptimizer.ajaxUrl, {
                action: 'geo_generate_llms_txt',
                nonce: geoOptimizer.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotice('success', 'llms.txt file generated successfully!');
                    // Update llms.txt status
                    $('.geo-status-inactive').removeClass('geo-status-inactive')
                        .addClass('geo-status-active').text('Active');
                } else {
                    showNotice('error', 'Failed to generate llms.txt file.');
                }
            })
            .fail(function() {
                showNotice('error', geoOptimizer.strings.error);
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Analyze all content button
        $('#analyze-all-content').on('click', function() {
            const $button = $(this);
            const originalText = $button.text();
            
            if (!confirm('This will analyze all your published content. This may take a while. Continue?')) {
                return;
            }
            
            $button.text('Analyzing...').prop('disabled', true);
            
            // Get all posts and analyze them
            analyzeAllPosts()
                .then(function(results) {
                    showNotice('success', `Analysis complete! Analyzed ${results.length} posts.`);
                    // Refresh dashboard stats
                    location.reload();
                })
                .catch(function(error) {
                    showNotice('error', 'Analysis failed: ' + error);
                })
                .finally(function() {
                    $button.text(originalText).prop('disabled', false);
                });
        });
        
        // Export data button
        $('#export-data').on('click', function() {
            // Create and download analytics export
            const data = gatherAnalyticsData();
            downloadJSON(data, 'geo-optimizer-analytics.json');
        });
    }
    
    /**
     * Initialize content analysis page
     */
    function initContentAnalysis() {
        loadContentTable();
        
        // Bulk analyze button
        $('#geo-bulk-analyze').on('click', function() {
            const selectedPosts = $('.geo-content-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedPosts.length === 0) {
                showNotice('warning', 'Please select posts to analyze.');
                return;
            }
            
            bulkAnalyzePosts(selectedPosts);
        });
        
        // Filter changes
        $('#geo-post-type-filter, #geo-score-filter').on('change', function() {
            loadContentTable();
        });
    }
    
    /**
     * Initialize tools page functionality
     */
    function initTools() {
        // llms.txt generator tool
        $('#generate-llms-tool').on('click', function() {
            generateLLMSTxt();
        });
        
        $('#preview-llms-tool').on('click', function() {
            previewLLMSTxt();
        });
        
        // Content audit tool
        $('#audit-content-tool').on('click', function() {
            startContentAudit();
        });
        
        // Schema validator tool
        $('#validate-schema-tool').on('click', function() {
            validateSchema();
        });
        
        // Citation monitor tool
        $('#check-citations-tool').on('click', function() {
            checkCitations();
        });
        
        // Import/Export tools
        $('#export-settings-tool').on('click', function() {
            exportSettings();
        });
        
        $('#import-settings-tool').on('click', function() {
            $('#import-file').click();
        });
        
        $('#import-file').on('change', function() {
            importSettings(this.files[0]);
        });
        
        // System info tool
        $('#system-info-tool').on('click', function() {
            showSystemInfo();
        });
        
        $('#run-diagnostics-tool').on('click', function() {
            runDiagnostics();
        });
    }
    
    /**
     * Initialize post editor functionality
     */
    function initPostEditor() {
        // Analyze content button in meta box
        $('#geo-analyze-content').on('click', function() {
            const postId = $('#post_ID').val();
            if (!postId) {
                showNotice('error', 'Please save the post first.');
                return;
            }
            
            analyzePost(postId);
        });
        
        // Auto-optimize button
        $('#geo-optimize-content').on('click', function() {
            const postId = $('#post_ID').val();
            if (!postId) {
                showNotice('error', 'Please save the post first.');
                return;
            }
            
            optimizePost(postId);
        });
        
        // Admin bar analyze current page
        $('.geo-analyze-current-page').on('click', function(e) {
            e.preventDefault();
            const postId = $('body').attr('class').match(/postid-(\d+)/);
            if (postId) {
                analyzePost(postId[1]);
            }
        });
    }
    
    /**
     * Analyze a single post
     */
    function analyzePost(postId) {
        const $resultsContainer = $('#geo-optimizer-analysis-results');
        const $button = $('#geo-analyze-content');
        
        $button.text(geoOptimizer.strings.analyzing).prop('disabled', true);
        $resultsContainer.html('<div class="geo-loading">Analyzing content...</div>');
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_analyze_content',
            post_id: postId,
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                displayAnalysisResults(response.data, $resultsContainer);
                showNotice('success', 'Content analysis complete!');
            } else {
                showNotice('error', 'Analysis failed: ' + (response.data || 'Unknown error'));
            }
        })
        .fail(function() {
            showNotice('error', geoOptimizer.strings.error);
        })
        .always(function() {
            $button.text('Analyze Content').prop('disabled', false);
        });
    }
    
    /**
     * Optimize a single post
     */
    function optimizePost(postId) {
        const $button = $('#geo-optimize-content');
        
        $button.text(geoOptimizer.strings.optimizing).prop('disabled', true);
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_optimize_post',
            post_id: postId,
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('success', 'Post optimized successfully!');
                // Re-analyze to show new score
                setTimeout(() => analyzePost(postId), 1000);
            } else {
                showNotice('error', 'Optimization failed: ' + (response.data || 'Unknown error'));
            }
        })
        .fail(function() {
            showNotice('error', geoOptimizer.strings.error);
        })
        .always(function() {
            $button.text('Auto-Optimize').prop('disabled', false);
        });
    }
    
    /**
     * Display analysis results
     */
    function displayAnalysisResults(analysis, $container) {
        const scoreClass = getScoreClass(analysis.score.total);
        
        let html = `
            <div class="geo-score-display">
                <div class="geo-score-circle geo-score-${scoreClass}">
                    <span class="geo-score-number">${analysis.score.total}</span>
                    <span class="geo-score-label">GEO Score</span>
                </div>
            </div>
            
            <div class="geo-analysis-details">
                <h4>Analysis Details</h4>
                <ul>
                    <li><strong>Word Count:</strong> ${analysis.word_count}</li>
                    <li><strong>Readability Score:</strong> ${analysis.readability}</li>
                    <li><strong>Citation Potential:</strong> ${analysis.citations_potential}%</li>
                </ul>
            </div>
        `;
        
        if (analysis.suggestions && analysis.suggestions.length > 0) {
            html += '<div class="geo-suggestions"><h4>Optimization Suggestions:</h4><ul>';
            analysis.suggestions.forEach(function(suggestion) {
                html += `<li>${suggestion}</li>`;
            });
            html += '</ul></div>';
        }
        
        if (analysis.keywords && Object.keys(analysis.keywords).length > 0) {
            html += '<div class="geo-keywords"><h4>Top Keywords:</h4><div class="geo-keyword-cloud">';
            Object.entries(analysis.keywords).forEach(function([keyword, count]) {
                html += `<span class="geo-keyword-tag">${keyword} (${count})</span>`;
            });
            html += '</div></div>';
        }
        
        $container.html(html);
    }
    
    /**
     * Analyze all posts (with progress)
     */
    function analyzeAllPosts() {
        return new Promise(function(resolve, reject) {
            // Get list of all posts
            $.post(geoOptimizer.ajaxUrl, {
                action: 'geo_get_all_posts',
                nonce: geoOptimizer.nonce
            })
            .done(function(response) {
                if (response.success && response.data.length > 0) {
                    const posts = response.data;
                    const results = [];
                    let current = 0;
                    
                    // Create progress indicator
                    const $progress = $(`
                        <div class="geo-progress-container">
                            <div class="geo-progress-bar">
                                <div class="geo-progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="geo-progress-text">Analyzing post 0 of ${posts.length}</div>
                        </div>
                    `);
                    
                    $('body').append($progress);
                    
                    function analyzeNext() {
                        if (current >= posts.length) {
                            $progress.remove();
                            resolve(results);
                            return;
                        }
                        
                        const post = posts[current];
                        const percent = Math.round((current / posts.length) * 100);
                        
                        $progress.find('.geo-progress-fill').css('width', percent + '%');
                        $progress.find('.geo-progress-text').text(`Analyzing "${post.title}" (${current + 1} of ${posts.length})`);
                        
                        $.post(geoOptimizer.ajaxUrl, {
                            action: 'geo_analyze_content',
                            post_id: post.id,
                            nonce: geoOptimizer.nonce
                        })
                        .done(function(response) {
                            if (response.success) {
                                results.push({
                                    post_id: post.id,
                                    title: post.title,
                                    analysis: response.data
                                });
                            }
                        })
                        .always(function() {
                            current++;
                            setTimeout(analyzeNext, 500); // Small delay to prevent overwhelming server
                        });
                    }
                    
                    analyzeNext();
                } else {
                    reject('No posts found to analyze');
                }
            })
            .fail(function() {
                reject('Failed to get post list');
            });
        });
    }
    
    /**
     * Load content table for analysis page
     */
    function loadContentTable() {
        const $container = $('#geo-content-table-container');
        const postType = $('#geo-post-type-filter').val();
        const scoreFilter = $('#geo-score-filter').val();
        
        $container.html('<div class="geo-loading">Loading content...</div>');
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_get_content_list',
            post_type: postType,
            score_filter: scoreFilter,
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                displayContentTable(response.data, $container);
            } else {
                $container.html('<p>Failed to load content.</p>');
            }
        })
        .fail(function() {
            $container.html('<p>Error loading content.</p>');
        });
    }
    
    /**
     * Display content table
     */
    function displayContentTable(content, $container) {
        let html = `
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column"><input type="checkbox" id="geo-select-all"></th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>GEO Score</th>
                        <th>Word Count</th>
                        <th>Last Analyzed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        if (content.length === 0) {
            html += '<tr><td colspan="7">No content found.</td></tr>';
        } else {
            content.forEach(function(item) {
                const scoreClass = getScoreClass(item.geo_score);
                const analyzedDate = item.last_analyzed ? 
                    new Date(item.last_analyzed).toLocaleDateString() : 'Never';
                
                html += `
                    <tr>
                        <td><input type="checkbox" class="geo-content-checkbox" value="${item.id}"></td>
                        <td><a href="${item.edit_url}">${item.title}</a></td>
                        <td>${item.post_type}</td>
                        <td><span class="geo-score-badge geo-score-${scoreClass}">${item.geo_score || 'N/A'}</span></td>
                        <td>${item.word_count || 'N/A'}</td>
                        <td>${analyzedDate}</td>
                        <td>
                            <button class="button button-small geo-analyze-single" data-post-id="${item.id}">Analyze</button>
                            <button class="button button-small geo-optimize-single" data-post-id="${item.id}">Optimize</button>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += '</tbody></table>';
        $container.html(html);
        
        // Bind events for new elements
        bindContentTableEvents();
    }
    
    /**
     * Bind events for content table
     */
    function bindContentTableEvents() {
        // Select all checkbox
        $('#geo-select-all').on('change', function() {
            $('.geo-content-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Individual analyze buttons
        $('.geo-analyze-single').on('click', function() {
            const postId = $(this).data('post-id');
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Analyzing...').prop('disabled', true);
            
            $.post(geoOptimizer.ajaxUrl, {
                action: 'geo_analyze_content',
                post_id: postId,
                nonce: geoOptimizer.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // Update the row with new data
                    const $row = $button.closest('tr');
                    const score = response.data.score.total;
                    const scoreClass = getScoreClass(score);
                    
                    $row.find('.geo-score-badge')
                        .removeClass('geo-score-poor geo-score-fair geo-score-good geo-score-excellent')
                        .addClass('geo-score-' + scoreClass)
                        .text(score);
                    
                    $row.find('td:nth-child(6)').text(new Date().toLocaleDateString());
                    
                    showNotice('success', 'Analysis complete!');
                } else {
                    showNotice('error', 'Analysis failed');
                }
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
        
        // Individual optimize buttons
        $('.geo-optimize-single').on('click', function() {
            const postId = $(this).data('post-id');
            const $button = $(this);
            const originalText = $button.text();
            
            $button.text('Optimizing...').prop('disabled', true);
            
            $.post(geoOptimizer.ajaxUrl, {
                action: 'geo_optimize_post',
                post_id: postId,
                nonce: geoOptimizer.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotice('success', 'Post optimized successfully!');
                    // Re-analyze to get updated score
                    setTimeout(() => {
                        $button.siblings('.geo-analyze-single').click();
                    }, 1000);
                } else {
                    showNotice('error', 'Optimization failed');
                }
            })
            .always(function() {
                $button.text(originalText).prop('disabled', false);
            });
        });
    }
    
    /**
     * Tools page functions
     */
    function generateLLMSTxt() {
        const $button = $('#generate-llms-tool');
        const $result = $('#llms-preview');
        
        $button.text('Generating...').prop('disabled', true);
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_generate_llms_txt',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                $result.html(`
                    <div class="geo-tool-success">
                        <h4>llms.txt Generated Successfully!</h4>
                        <p>Your llms.txt file is now available at: <a href="${window.location.origin}/llms.txt" target="_blank">${window.location.origin}/llms.txt</a></p>
                        <button class="button" onclick="jQuery('#llms-preview-content').toggle()">Show/Hide Content</button>
                        <pre id="llms-preview-content" style="display:none; max-height: 300px; overflow-y: auto;">${response.data.content}</pre>
                    </div>
                `).show();
                showNotice('success', 'llms.txt file generated successfully!');
            } else {
                showNotice('error', 'Failed to generate llms.txt');
            }
        })
        .always(function() {
            $button.text('Generate llms.txt').prop('disabled', false);
        });
    }
    
    function previewLLMSTxt() {
        const $result = $('#llms-preview');
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_preview_llms_txt',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                $result.html(`
                    <div class="geo-tool-info">
                        <h4>llms.txt Preview</h4>
                        <pre style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd;">${response.data.content}</pre>
                    </div>
                `).show();
            } else {
                showNotice('error', 'Failed to preview llms.txt');
            }
        });
    }
    
    function startContentAudit() {
        const $button = $('#audit-content-tool');
        const $result = $('#audit-progress');
        
        $button.text('Starting Audit...').prop('disabled', true);
        $result.html('<div class="geo-loading">Preparing content audit...</div>').show();
        
        // Start comprehensive content audit
        analyzeAllPosts()
            .then(function(results) {
                displayAuditResults(results, $result);
                $button.text('Start Audit').prop('disabled', false);
            })
            .catch(function(error) {
                $result.html(`<div class="geo-tool-error">Audit failed: ${error}</div>`);
                $button.text('Start Audit').prop('disabled', false);
            });
    }
    
    function displayAuditResults(results, $container) {
        const totalPosts = results.length;
        const scores = results.map(r => r.analysis.score.total);
        const avgScore = Math.round(scores.reduce((a, b) => a + b, 0) / scores.length);
        
        const excellentCount = scores.filter(s => s >= 80).length;
        const goodCount = scores.filter(s => s >= 60 && s < 80).length;
        const fairCount = scores.filter(s => s >= 40 && s < 60).length;
        const poorCount = scores.filter(s => s < 40).length;
        
        let html = `
            <div class="geo-audit-summary">
                <h4>Content Audit Results</h4>
                <div class="geo-audit-stats">
                    <div class="geo-audit-stat">
                        <span class="geo-audit-number">${totalPosts}</span>
                        <span class="geo-audit-label">Posts Analyzed</span>
                    </div>
                    <div class="geo-audit-stat">
                        <span class="geo-audit-number">${avgScore}</span>
                        <span class="geo-audit-label">Average Score</span>
                    </div>
                    <div class="geo-audit-stat">
                        <span class="geo-audit-number">${excellentCount}</span>
                        <span class="geo-audit-label">Excellent (80+)</span>
                    </div>
                    <div class="geo-audit-stat">
                        <span class="geo-audit-number">${poorCount}</span>
                        <span class="geo-audit-label">Need Work (&lt;40)</span>
                    </div>
                </div>
                
                <div class="geo-audit-recommendations">
                    <h5>Key Recommendations:</h5>
                    <ul>
        `;
        
        if (poorCount > 0) {
            html += `<li>Focus on improving ${poorCount} posts with poor GEO scores</li>`;
        }
        if (avgScore < 60) {
            html += '<li>Overall content needs more business information and authority signals</li>';
        }
        if (results.some(r => r.analysis.word_count < 300)) {
            html += '<li>Several posts are too short - aim for 300+ words</li>';
        }
        
        html += `
                    </ul>
                </div>
                
                <button class="button button-primary" onclick="jQuery('#audit-detailed-results').toggle()">
                    Show/Hide Detailed Results
                </button>
            </div>
            
            <div id="audit-detailed-results" style="display:none; margin-top: 20px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>GEO Score</th>
                            <th>Word Count</th>
                            <th>Top Issues</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        results.forEach(function(result) {
            const scoreClass = getScoreClass(result.analysis.score.total);
            const topIssues = result.analysis.suggestions.slice(0, 2).join(', ');
            
            html += `
                <tr>
                    <td>${result.title}</td>
                    <td><span class="geo-score-badge geo-score-${scoreClass}">${result.analysis.score.total}</span></td>
                    <td>${result.analysis.word_count}</td>
                    <td>${topIssues}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        $container.html(html);
    }
    
    function validateSchema() {
        const $result = $('#schema-results');
        
        $result.html('<div class="geo-loading">Validating schema markup...</div>').show();
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_validate_schema',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                const validation = response.data;
                let html = `
                    <div class="geo-schema-validation">
                        <h4>Schema Validation Results</h4>
                        <div class="geo-validation-status geo-validation-${validation.valid ? 'success' : 'error'}">
                            ${validation.valid ? '✓ Schema is valid' : '✗ Schema has issues'}
                        </div>
                `;
                
                if (validation.errors && validation.errors.length > 0) {
                    html += '<div class="geo-validation-errors"><h5>Errors:</h5><ul>';
                    validation.errors.forEach(function(error) {
                        html += `<li>${error}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                if (validation.warnings && validation.warnings.length > 0) {
                    html += '<div class="geo-validation-warnings"><h5>Warnings:</h5><ul>';
                    validation.warnings.forEach(function(warning) {
                        html += `<li>${warning}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                html += '</div>';
                $result.html(html);
            } else {
                $result.html('<div class="geo-tool-error">Schema validation failed</div>');
            }
        });
    }
    
    function checkCitations() {
        const $result = $('#citation-results');
        
        $result.html('<div class="geo-loading">Checking for citations...</div>').show();
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_check_citations',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                const citations = response.data;
                let html = `
                    <div class="geo-citation-check">
                        <h4>Citation Check Results</h4>
                        <p>Found ${citations.total} potential citations across ${citations.sources.length} sources.</p>
                `;
                
                if (citations.sources.length > 0) {
                    html += '<div class="geo-citation-sources"><h5>Sources:</h5><ul>';
                    citations.sources.forEach(function(source) {
                        html += `<li><strong>${source.name}:</strong> ${source.count} citations</li>`;
                    });
                    html += '</ul></div>';
                }
                
                html += '</div>';
                $result.html(html);
            } else {
                $result.html('<div class="geo-tool-error">Citation check failed</div>');
            }
        });
    }
    
    function exportSettings() {
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_export_settings',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                downloadJSON(response.data, 'geo-optimizer-settings.json');
                showNotice('success', 'Settings exported successfully!');
            } else {
                showNotice('error', 'Export failed');
            }
        });
    }
    
    function importSettings(file) {
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                $.post(geoOptimizer.ajaxUrl, {
                    action: 'geo_import_settings',
                    settings: JSON.stringify(settings),
                    nonce: geoOptimizer.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        showNotice('success', 'Settings imported successfully! Page will reload.');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showNotice('error', 'Import failed: ' + response.data);
                    }
                });
            } catch (error) {
                showNotice('error', 'Invalid settings file');
            }
        };
        reader.readAsText(file);
    }
    
    function showSystemInfo() {
        const $result = $('#system-info-results');
        
        $result.html('<div class="geo-loading">Gathering system information...</div>').show();
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_system_info',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                const info = response.data;
                let html = `
                    <div class="geo-system-info">
                        <h4>System Information</h4>
                        <table class="form-table">
                            <tr><th>WordPress Version:</th><td>${info.wp_version}</td></tr>
                            <tr><th>PHP Version:</th><td>${info.php_version}</td></tr>
                            <tr><th>Plugin Version:</th><td>${info.plugin_version}</td></tr>
                            <tr><th>Database Version:</th><td>${info.db_version}</td></tr>
                            <tr><th>Memory Limit:</th><td>${info.memory_limit}</td></tr>
                            <tr><th>Max Execution Time:</th><td>${info.max_execution_time}s</td></tr>
                        </table>
                        
                        <h5>Plugin Status</h5>
                        <ul>
                            <li>llms.txt: ${info.llms_exists ? '✓ Active' : '✗ Not generated'}</li>
                            <li>Database Tables: ${info.tables_exist ? '✓ Created' : '✗ Missing'}</li>
                            <li>Business Info: ${info.business_configured ? '✓ Configured' : '✗ Incomplete'}</li>
                        </ul>
                    </div>
                `;
                $result.html(html);
            } else {
                $result.html('<div class="geo-tool-error">Failed to get system info</div>');
            }
        });
    }
    
    function runDiagnostics() {
        const $result = $('#system-info-results');
        
        $result.html('<div class="geo-loading">Running diagnostics...</div>').show();
        
        $.post(geoOptimizer.ajaxUrl, {
            action: 'geo_run_diagnostics',
            nonce: geoOptimizer.nonce
        })
        .done(function(response) {
            if (response.success) {
                const diagnostics = response.data;
                let html = `
                    <div class="geo-diagnostics">
                        <h4>Diagnostic Results</h4>
                        <div class="geo-diagnostic-overall geo-diagnostic-${diagnostics.overall_status}">
                            Overall Status: ${diagnostics.overall_status.toUpperCase()}
                        </div>
                        
                        <div class="geo-diagnostic-tests">
                `;
                
                diagnostics.tests.forEach(function(test) {
                    html += `
                        <div class="geo-diagnostic-test geo-test-${test.status}">
                            <strong>${test.name}:</strong> ${test.status.toUpperCase()}
                            ${test.message ? `<br><small>${test.message}</small>` : ''}
                        </div>
                    `;
                });
                
                html += '</div></div>';
                $result.html(html);
            } else {
                $result.html('<div class="geo-tool-error">Diagnostics failed</div>');
            }
        });
    }
    
    /**
     * Utility functions
     */
    function getScoreClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 60) return 'good';
        if (score >= 40) return 'fair';
        return 'poor';
    }
    
    function showNotice(type, message) {
        const $notice = $(`
            <div class="notice notice-${type} is-dismissible geo-admin-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').first().after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut();
        }, 5000);
        
        // Manual dismiss
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut();
        });
    }
    
    function downloadJSON(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function gatherAnalyticsData() {
        // This would gather comprehensive analytics data
        return {
            export_date: new Date().toISOString(),
            site_url: window.location.origin,
            plugin_version: '1.0.0',
            analytics: {
                // Analytics data would be populated here
            }
        };
    }
    
    // Initialize tooltips if available
    if (typeof $.fn.tooltip === 'function') {
        $('.geo-tooltip').tooltip();
    }
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    function initializeCharts() {
        // Initialize performance chart on analytics page
        const $chartCanvas = $('#geo-performance-chart');
        if ($chartCanvas.length) {
            // Sample data - would be replaced with real analytics data
            const ctx = $chartCanvas[0].getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Average GEO Score',
                        data: [65, 68, 72, 75, 78, 80, 82],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }
});