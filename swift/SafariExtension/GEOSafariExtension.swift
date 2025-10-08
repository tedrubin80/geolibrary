// GEO Safari Extension for Consumer Protection
// Based on official Apple Developer Documentation:
// - Safari App Extensions: https://developer.apple.com/documentation/safariservices/safari_app_extensions
// - Safari Web Extensions: https://developer.apple.com/documentation/safariservices/safari_web_extensions
// - Content Blocking: https://developer.apple.com/documentation/safariservices/creating_a_content_blocker

import Foundation
import SafariServices
import WebKit

/// Safari extension that protects consumers from trackers and influence bots
/// Uses GEO analysis patterns to detect and block malicious content
@available(macOS 10.14, iOS 15.0, *)
public class GEOSafariExtension: NSObject, NSExtensionRequestHandling {

    private let geoOptimizer = GEOOptimizer()
    private var trackerDetectionEnabled = true
    private var influenceBotDetectionEnabled = true
    private var contentBlockingEnabled = true

    // MARK: - Extension Request Handling

    public func beginRequest(with context: NSExtensionContext) {
        let item = context.inputItems[0] as! NSExtensionItem
        let message = item.userInfo?[SFExtensionMessageKey]

        guard let messageDictionary = message as? [String: Any] else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -1))
            return
        }

        handleMessage(messageDictionary, context: context)
    }

    private func handleMessage(_ message: [String: Any], context: NSExtensionContext) {
        guard let action = message["action"] as? String else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -1))
            return
        }

        switch action {
        case "analyzeContent":
            analyzePageContent(message, context: context)
        case "detectTrackers":
            detectTrackers(message, context: context)
        case "checkInfluenceBots":
            checkInfluenceBots(message, context: context)
        case "getProtectionStatus":
            getProtectionStatus(context: context)
        default:
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -1))
        }
    }

    // MARK: - Content Analysis

    private func analyzePageContent(_ message: [String: Any], context: NSExtensionContext) {
        guard let content = message["content"] as? String else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -2))
            return
        }

        let contentData = ContentData(
            text: content,
            hasLLMSTxt: false,
            hasSchema: message["hasSchema"] as? Bool ?? false,
            schemaTypes: message["schemaTypes"] as? [String],
            isVerifiedBusiness: false,
            hasReviews: false,
            averageRating: 0,
            externalCitations: nil,
            isMobileFriendly: true,
            pageSpeedScore: nil,
            usesHTTPS: message["isHTTPS"] as? Bool ?? false,
            hasSitemap: false,
            lastUpdated: nil,
            hasFAQ: false,
            hasHowTo: false,
            contentFormats: nil,
            hasDetailedDescriptions: false,
            hasUniqueContent: true,
            hasStatistics: false,
            hasOriginalResearch: false,
            hasExpertQuotes: false
        )

        let geoScore = geoOptimizer.calculateReadinessScore(for: contentData)
        let influenceAnalysis = geoOptimizer.detectInfluenceBots(in: contentData)

        let response: [String: Any] = [
            "geoScore": [
                "overall": geoScore.overall,
                "grade": geoScore.grade,
                "readinessLevel": geoScore.readinessLevel
            ],
            "influenceAnalysis": [
                "score": influenceAnalysis.influenceScore,
                "classification": influenceAnalysis.classification,
                "shouldFlag": influenceAnalysis.shouldFlag,
                "shouldRemove": influenceAnalysis.shouldRemove
            ],
            "userRecommendations": generateUserRecommendations(geoScore, influenceAnalysis)
        ]

        let responseItem = NSExtensionItem()
        responseItem.userInfo = [SFExtensionMessageKey: response]
        context.completeRequest(returningItems: [responseItem])
    }

    // MARK: - Tracker Detection

    private func detectTrackers(_ message: [String: Any], context: NSExtensionContext) {
        guard let requestData = extractRequestData(from: message) else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -3))
            return
        }

        let analysis = geoOptimizer.detectTrackers(in: requestData)

        let response: [String: Any] = [
            "isTracker": analysis.shouldBlock || analysis.shouldChallenge,
            "threatScore": analysis.threatScore,
            "classification": analysis.classification,
            "riskLevel": analysis.riskLevel,
            "action": analysis.shouldBlock ? "block" : (analysis.shouldChallenge ? "challenge" : "allow"),
            "recommendations": analysis.recommendations
        ]

        // Log detected trackers for user visibility
        if analysis.shouldBlock {
            logTrackerBlocked(message["url"] as? String ?? "unknown", analysis)
        }

        let responseItem = NSExtensionItem()
        responseItem.userInfo = [SFExtensionMessageKey: response]
        context.completeRequest(returningItems: [responseItem])
    }

    // MARK: - Influence Bot Detection

    private func checkInfluenceBots(_ message: [String: Any], context: NSExtensionContext) {
        guard let content = message["content"] as? String else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -4))
            return
        }

        let contentData = ContentData(text: content)
        let analysis = geoOptimizer.detectInfluenceBots(in: contentData)

        let response: [String: Any] = [
            "isInfluenceBot": analysis.shouldFlag || analysis.shouldRemove,
            "influenceScore": analysis.influenceScore,
            "classification": analysis.classification,
            "threatLevel": analysis.threatLevel,
            "authenticityConfidence": analysis.authenticityConfidence,
            "action": analysis.shouldRemove ? "remove" : (analysis.shouldFlag ? "flag" : "allow"),
            "recommendations": analysis.recommendations
        ]

        // Show warning to user if needed
        if analysis.shouldFlag {
            showInfluenceBotWarning(analysis)
        }

        let responseItem = NSExtensionItem()
        responseItem.userInfo = [SFExtensionMessageKey: response]
        context.completeRequest(returningItems: [responseItem])
    }

    // MARK: - Protection Status

    private func getProtectionStatus(context: NSExtensionContext) {
        let status: [String: Any] = [
            "trackerDetection": trackerDetectionEnabled,
            "influenceBotDetection": influenceBotDetectionEnabled,
            "contentBlocking": contentBlockingEnabled,
            "version": "1.0.0",
            "features": [
                "Tracker Detection",
                "Influence Bot Detection",
                "Content Quality Analysis",
                "GEO Readiness Scoring"
            ]
        ]

        let responseItem = NSExtensionItem()
        responseItem.userInfo = [SFExtensionMessageKey: status]
        context.completeRequest(returningItems: [responseItem])
    }

    // MARK: - Helper Methods

    private func extractRequestData(from message: [String: Any]) -> RequestData? {
        guard let timestamps = message["timestamps"] as? [TimeInterval] else {
            return nil
        }

        return RequestData(
            timestamps: timestamps,
            interactionData: message["interactionData"] as? [String: Double],
            accessPattern: message["accessPattern"] as? [String: Int],
            geographicData: message["geographicData"] as? [String: Any],
            fingerprintData: message["fingerprintData"] as? [String: Bool],
            behaviorData: message["behaviorData"] as? [String: Any]
        )
    }

    private func generateUserRecommendations(
        _ geoScore: GEOScore,
        _ influenceAnalysis: InfluenceAnalysis
    ) -> [String] {
        var recommendations: [String] = []

        if influenceAnalysis.shouldFlag {
            recommendations.append("⚠️ This content may be part of an influence campaign")
        }

        if influenceAnalysis.authenticityConfidence < 60 {
            recommendations.append("🤖 This content may be AI-generated or bot-created")
        }

        if geoScore.overall < 40 {
            recommendations.append("📊 This website has poor AI visibility optimization")
        }

        if geoScore.breakdown.authoritySignals < 50 {
            recommendations.append("🔍 Verify claims independently - low authority signals detected")
        }

        return recommendations
    }

    private func logTrackerBlocked(_ url: String, _ analysis: ThreatAnalysis) {
        // Log to console for debugging
        print("🛡️ GEO Extension: Blocked tracker from \(url)")
        print("   Risk Level: \(analysis.riskLevel)")
        print("   Threat Score: \(analysis.threatScore)")
        print("   Classification: \(analysis.classification)")

        // In production, this would update a dashboard or notification center
    }

    private func showInfluenceBotWarning(_ analysis: InfluenceAnalysis) {
        // Log warning
        print("⚠️ GEO Extension: Influence bot detected")
        print("   Threat Level: \(analysis.threatLevel)")
        print("   Authenticity: \(analysis.authenticityConfidence)%")

        // In production, this would show a user notification in Safari
    }
}

// MARK: - Content Blocker Extension

/// Content blocker rules for Safari
/// Based on: https://developer.apple.com/documentation/safariservices/creating_a_content_blocker
@available(macOS 10.12, iOS 9.0, *)
public class GEOContentBlocker {

    /// Generate content blocker rules for known trackers and malicious domains
    public static func generateBlockerRules() -> [[String: Any]] {
        return [
            // Block known tracking domains
            [
                "trigger": [
                    "url-filter": ".*",
                    "if-domain": [
                        "*doubleclick.net",
                        "*googlesyndication.com",
                        "*facebook.com/tr",
                        "*analytics.google.com"
                    ]
                ],
                "action": [
                    "type": "block"
                ]
            ],

            // Block fingerprinting scripts
            [
                "trigger": [
                    "url-filter": ".*fingerprint.*",
                    "resource-type": ["script"]
                ],
                "action": [
                    "type": "block"
                ]
            ],

            // Block crypto miners
            [
                "trigger": [
                    "url-filter": ".*coinhive.*|.*cryptoloot.*|.*crypto-loot.*",
                    "resource-type": ["script"]
                ],
                "action": [
                    "type": "block"
                ]
            ],

            // Hide cookie consent banners (cosmetic blocking)
            [
                "trigger": [
                    "url-filter": ".*"
                ],
                "action": [
                    "type": "css-display-none",
                    "selector": ".cookie-banner, #cookie-notice, .gdpr-banner"
                ]
            ]
        ]
    }

    /// Write blocker rules to JSON file for Safari extension
    public static func writeBlockerRulesToFile(at path: URL) throws {
        let rules = generateBlockerRules()
        let data = try JSONSerialization.data(withJSONObject: rules, options: [.prettyPrinted])
        try data.write(to: path)
    }
}

// MARK: - Safari Extension JavaScript Bridge

/// JavaScript code injected into web pages for GEO analysis
public class GEOSafariJavaScript {

    /// Generate JavaScript code for content injection
    public static func generateInjectionScript() -> String {
        return """
        // GEO Safari Extension - Content Analysis
        (function() {
            'use strict';

            // Extract page content for GEO analysis
            function extractPageContent() {
                const content = {
                    text: document.body.innerText,
                    hasSchema: !!document.querySelector('script[type="application/ld+json"]'),
                    schemaTypes: extractSchemaTypes(),
                    isHTTPS: window.location.protocol === 'https:',
                    url: window.location.href,
                    title: document.title,
                    meta: extractMetadata()
                };
                return content;
            }

            // Extract Schema.org types from JSON-LD
            function extractSchemaTypes() {
                const scripts = document.querySelectorAll('script[type="application/ld+json"]');
                const types = [];

                scripts.forEach(script => {
                    try {
                        const data = JSON.parse(script.textContent);
                        if (data['@type']) {
                            types.push(data['@type']);
                        }
                    } catch (e) {
                        console.error('GEO Extension: Failed to parse schema', e);
                    }
                });

                return types;
            }

            // Extract metadata
            function extractMetadata() {
                const meta = {};
                document.querySelectorAll('meta').forEach(tag => {
                    const name = tag.getAttribute('name') || tag.getAttribute('property');
                    const content = tag.getAttribute('content');
                    if (name && content) {
                        meta[name] = content;
                    }
                });
                return meta;
            }

            // Track user interactions for tracker detection
            let interactionTimestamps = [];
            let mouseMovements = [];
            let scrollEvents = [];

            function trackInteractions() {
                interactionTimestamps.push(Date.now() / 1000);

                document.addEventListener('mousemove', (e) => {
                    mouseMovements.push({ x: e.clientX, y: e.clientY, time: Date.now() });
                });

                document.addEventListener('scroll', () => {
                    scrollEvents.push({ position: window.scrollY, time: Date.now() });
                });
            }

            // Calculate interaction metrics
            function calculateInteractionMetrics() {
                const now = Date.now();
                const recentMovements = mouseMovements.filter(m => now - m.time < 5000);
                const recentScrolls = scrollEvents.filter(s => now - s.time < 5000);

                return {
                    hover_duration: recentMovements.length > 0 ? 1 : 0,
                    scroll_depth: scrollEvents.length > 0 ? window.scrollY / document.body.scrollHeight : 0,
                    mouse_movement_entropy: calculateEntropy(recentMovements),
                    reading_time_correlation: scrollEvents.length / (document.body.innerText.length / 200),
                    click_precision: 0.8 // Simplified
                };
            }

            function calculateEntropy(movements) {
                if (movements.length < 2) return 0;

                const distances = [];
                for (let i = 1; i < movements.length; i++) {
                    const dx = movements[i].x - movements[i-1].x;
                    const dy = movements[i].y - movements[i-1].y;
                    distances.push(Math.sqrt(dx*dx + dy*dy));
                }

                const avg = distances.reduce((a, b) => a + b, 0) / distances.length;
                const variance = distances.reduce((sum, d) => sum + Math.pow(d - avg, 2), 0) / distances.length;

                return Math.sqrt(variance) / 100; // Normalized
            }

            // Send analysis to extension
            function sendToExtension(action, data) {
                safari.extension.dispatchMessage(action, data);
            }

            // Initialize
            trackInteractions();

            // Analyze page on load
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const pageContent = extractPageContent();
                    const interactionData = calculateInteractionMetrics();

                    sendToExtension('analyzeContent', {
                        content: pageContent,
                        interactions: interactionData,
                        timestamps: interactionTimestamps
                    });
                }, 2000); // Wait 2s for page to fully load
            });

            // Monitor for new content (influence bots often inject content dynamically)
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1) { // Element node
                                const content = node.innerText || '';
                                if (content.length > 100) {
                                    sendToExtension('checkInfluenceBots', {
                                        content: content,
                                        context: 'dynamic_injection'
                                    });
                                }
                            }
                        });
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Display GEO protection badge
            function showProtectionBadge(status) {
                const badge = document.createElement('div');
                badge.id = 'geo-protection-badge';
                badge.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 12px 16px;
                    border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    font-size: 13px;
                    z-index: 999999;
                    cursor: pointer;
                    transition: all 0.3s ease;
                `;

                badge.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">🛡️</span>
                        <div>
                            <div style="font-weight: 600;">GEO Protected</div>
                            <div style="font-size: 11px; opacity: 0.9;">Trackers blocked: ${status.blockCount || 0}</div>
                        </div>
                    </div>
                `;

                badge.addEventListener('click', () => {
                    // Show detailed protection info
                    alert(`GEO Extension Active\\n\\nTrackers Blocked: ${status.blockCount || 0}\\nInfluence Bots Detected: ${status.botCount || 0}\\nContent Authenticity: ${status.authenticity || 'Unknown'}`);
                });

                document.body.appendChild(badge);
            }

            // Request protection status
            sendToExtension('getProtectionStatus', {});

            console.log('🛡️ GEO Safari Extension initialized');
        })();
        """
    }
}
