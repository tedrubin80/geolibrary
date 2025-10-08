// GEOOptimizer for iOS/macOS
// Based on Apple Developer Documentation:
// - MapKit: https://developer.apple.com/documentation/mapkit/
// - MapKit for SwiftUI: https://developer.apple.com/documentation/mapkit/mapkit-for-swiftui
// - MKLocalSearch: https://developer.apple.com/documentation/mapkit/mklocalsearch
// - WWDC 2025: https://developer.apple.com/videos/play/wwdc2025/204/

import Foundation
import MapKit
import SwiftUI

/// GEO Readiness Score Calculator for iOS/macOS
/// Evaluates content for AI search engine optimization
@available(iOS 17.0, macOS 14.0, *)
public class GEOOptimizer {

    /// Score weights for different factors
    private struct Weights {
        static let contentQuality: Double = 0.25
        static let structuredData: Double = 0.20
        static let authoritySignals: Double = 0.15
        static let technicalOptimization: Double = 0.15
        static let freshness: Double = 0.10
        static let comprehensiveness: Double = 0.10
        static let citations: Double = 0.05
    }

    /// Tracker detection system
    private let trackerDetector = TrackerDetector()

    /// Influence bot detection system
    private let influenceBotDetector = InfluenceBotDetector()

    // MARK: - GEO Readiness Score

    /// Calculate comprehensive GEO readiness score
    public func calculateReadinessScore(for content: ContentData) -> GEOScore {
        let scores = ScoreBreakdown(
            contentQuality: assessContentQuality(content),
            structuredData: assessStructuredData(content),
            authoritySignals: assessAuthoritySignals(content),
            technicalOptimization: assessTechnicalOptimization(content),
            freshness: assessFreshness(content),
            comprehensiveness: assessComprehensiveness(content),
            citations: assessCitations(content)
        )

        let overallScore = calculateWeightedScore(scores)

        return GEOScore(
            overall: overallScore,
            grade: getGrade(overallScore),
            breakdown: scores,
            readinessLevel: getReadinessLevel(overallScore),
            estimatedVisibility: estimateVisibility(overallScore),
            recommendations: generateRecommendations(scores),
            timestamp: Date()
        )
    }

    // MARK: - Security Features

    /// Detect tracker and scraper patterns
    public func detectTrackers(in request: RequestData) -> ThreatAnalysis {
        return trackerDetector.analyze(request)
    }

    /// Detect influence bots and manipulation campaigns
    public func detectInfluenceBots(in content: ContentData) -> InfluenceAnalysis {
        return influenceBotDetector.analyze(content)
    }

    // MARK: - Private Assessment Methods

    private func assessContentQuality(_ content: ContentData) -> Double {
        var score: Double = 0.0

        // Check for llms.txt presence
        if content.hasLLMSTxt {
            score += 20
        }

        // Analyze readability
        if let text = content.text {
            let readability = calculateReadability(text)
            score += min(30, max(0, readability - 30))

            // Word count
            let wordCount = text.split(separator: " ").count
            if wordCount >= 500 {
                score += min(25, Double(wordCount) / 100 * 2)
            }
        }

        return min(100, score)
    }

    private func assessStructuredData(_ content: ContentData) -> Double {
        var score: Double = 0.0

        if content.hasSchema {
            score += 40

            if let schemaTypes = content.schemaTypes {
                score += min(30, Double(schemaTypes.count) * 10)
            }
        }

        return min(100, score)
    }

    private func assessAuthoritySignals(_ content: ContentData) -> Double {
        var score: Double = 0.0

        if content.isVerifiedBusiness {
            score += 25
        }

        if content.hasReviews {
            score += 15
            if content.averageRating >= 4.0 {
                score += 10
            }
        }

        if let citations = content.externalCitations {
            score += min(25, Double(citations.count) * 5)
        }

        return min(100, score)
    }

    private func assessTechnicalOptimization(_ content: ContentData) -> Double {
        var score: Double = 0.0

        if content.isMobileFriendly {
            score += 25
        }

        if let pageSpeed = content.pageSpeedScore {
            score += min(25, pageSpeed / 4)
        }

        if content.usesHTTPS {
            score += 20
        }

        if content.hasSitemap {
            score += 15
        }

        return min(100, score)
    }

    private func assessFreshness(_ content: ContentData) -> Double {
        guard let lastUpdated = content.lastUpdated else {
            return 0
        }

        let daysSinceUpdate = Date().timeIntervalSince(lastUpdated) / 86400

        switch daysSinceUpdate {
        case 0...7: return 100
        case 8...30: return 80
        case 31...90: return 60
        case 91...180: return 40
        case 181...365: return 20
        default: return 0
        }
    }

    private func assessComprehensiveness(_ content: ContentData) -> Double {
        var score: Double = 0.0

        if content.hasFAQ { score += 20 }
        if content.hasHowTo { score += 20 }

        if let formats = content.contentFormats {
            score += min(30, Double(formats.count) * 10)
        }

        if content.hasDetailedDescriptions { score += 30 }

        return min(100, score)
    }

    private func assessCitations(_ content: ContentData) -> Double {
        var score: Double = 0.0

        if content.hasUniqueContent { score += 30 }
        if content.hasStatistics { score += 25 }
        if content.hasOriginalResearch { score += 25 }
        if content.hasExpertQuotes { score += 20 }

        return min(100, score)
    }

    private func calculateWeightedScore(_ scores: ScoreBreakdown) -> Double {
        let weighted =
            scores.contentQuality * Weights.contentQuality +
            scores.structuredData * Weights.structuredData +
            scores.authoritySignals * Weights.authoritySignals +
            scores.technicalOptimization * Weights.technicalOptimization +
            scores.freshness * Weights.freshness +
            scores.comprehensiveness * Weights.comprehensiveness +
            scores.citations * Weights.citations

        return (weighted / 1.0).rounded(toPlaces: 1)
    }

    private func calculateReadability(_ text: String) -> Double {
        // Simplified Flesch Reading Ease calculation
        let sentences = text.components(separatedBy: CharacterSet(charactersIn: ".!?")).filter { !$0.isEmpty }
        let words = text.split(separator: " ")
        let syllables = words.map { countSyllables(String($0)) }.reduce(0, +)

        guard !sentences.isEmpty, !words.isEmpty else { return 0 }

        let avgWordsPerSentence = Double(words.count) / Double(sentences.count)
        let avgSyllablesPerWord = Double(syllables) / Double(words.count)

        let score = 206.835 - 1.015 * avgWordsPerSentence - 84.6 * avgSyllablesPerWord
        return max(0, min(100, score))
    }

    private func countSyllables(_ word: String) -> Int {
        let vowels = CharacterSet(charactersIn: "aeiouAEIOU")
        var count = 0
        var previousWasVowel = false

        for char in word.unicodeScalars {
            let isVowel = vowels.contains(char)
            if isVowel && !previousWasVowel {
                count += 1
            }
            previousWasVowel = isVowel
        }

        return max(1, count)
    }

    private func getGrade(_ score: Double) -> String {
        switch score {
        case 90...: return "A+"
        case 85..<90: return "A"
        case 80..<85: return "A-"
        case 77..<80: return "B+"
        case 73..<77: return "B"
        case 70..<73: return "B-"
        case 67..<70: return "C+"
        case 63..<67: return "C"
        case 60..<63: return "C-"
        case 50..<60: return "D"
        default: return "F"
        }
    }

    private func getReadinessLevel(_ score: Double) -> String {
        switch score {
        case 80...: return "Excellent - Highly optimized for AI discovery"
        case 60..<80: return "Good - Well-positioned for AI citations"
        case 40..<60: return "Fair - Some optimization needed"
        case 20..<40: return "Poor - Significant improvements required"
        default: return "Critical - Urgent optimization needed"
        }
    }

    private func estimateVisibility(_ score: Double) -> String {
        switch score {
        case 80...: return "High - Likely to appear in top AI responses"
        case 60..<80: return "Moderate - May appear in detailed AI responses"
        case 40..<60: return "Low - Occasionally cited by AI"
        case 20..<40: return "Minimal - Rarely referenced by AI"
        default: return "Very Low - Unlikely to be discovered by AI"
        }
    }

    private func generateRecommendations(_ scores: ScoreBreakdown) -> [String] {
        var recommendations: [String] = []

        if scores.contentQuality < 70 {
            recommendations.append("Create an llms.txt file")
            recommendations.append("Improve content readability")
        }

        if scores.structuredData < 70 {
            recommendations.append("Implement Schema.org markup")
        }

        if scores.authoritySignals < 70 {
            recommendations.append("Collect customer reviews")
        }

        if scores.technicalOptimization < 70 {
            recommendations.append("Improve page load speed")
        }

        if scores.freshness < 70 {
            recommendations.append("Update content more frequently")
        }

        return Array(recommendations.prefix(5))
    }
}

// MARK: - Data Models

/// Content data for GEO analysis
public struct ContentData {
    public let text: String?
    public let hasLLMSTxt: Bool
    public let hasSchema: Bool
    public let schemaTypes: [String]?
    public let isVerifiedBusiness: Bool
    public let hasReviews: Bool
    public let averageRating: Double
    public let externalCitations: [String]?
    public let isMobileFriendly: Bool
    public let pageSpeedScore: Double?
    public let usesHTTPS: Bool
    public let hasSitemap: Bool
    public let lastUpdated: Date?
    public let hasFAQ: Bool
    public let hasHowTo: Bool
    public let contentFormats: [String]?
    public let hasDetailedDescriptions: Bool
    public let hasUniqueContent: Bool
    public let hasStatistics: Bool
    public let hasOriginalResearch: Bool
    public let hasExpertQuotes: Bool

    public init(
        text: String? = nil,
        hasLLMSTxt: Bool = false,
        hasSchema: Bool = false,
        schemaTypes: [String]? = nil,
        isVerifiedBusiness: Bool = false,
        hasReviews: Bool = false,
        averageRating: Double = 0,
        externalCitations: [String]? = nil,
        isMobileFriendly: Bool = true,
        pageSpeedScore: Double? = nil,
        usesHTTPS: Bool = true,
        hasSitemap: Bool = false,
        lastUpdated: Date? = nil,
        hasFAQ: Bool = false,
        hasHowTo: Bool = false,
        contentFormats: [String]? = nil,
        hasDetailedDescriptions: Bool = false,
        hasUniqueContent: Bool = false,
        hasStatistics: Bool = false,
        hasOriginalResearch: Bool = false,
        hasExpertQuotes: Bool = false
    ) {
        self.text = text
        self.hasLLMSTxt = hasLLMSTxt
        self.hasSchema = hasSchema
        self.schemaTypes = schemaTypes
        self.isVerifiedBusiness = isVerifiedBusiness
        self.hasReviews = hasReviews
        self.averageRating = averageRating
        self.externalCitations = externalCitations
        self.isMobileFriendly = isMobileFriendly
        self.pageSpeedScore = pageSpeedScore
        self.usesHTTPS = usesHTTPS
        self.hasSitemap = hasSitemap
        self.lastUpdated = lastUpdated
        self.hasFAQ = hasFAQ
        self.hasHowTo = hasHowTo
        self.contentFormats = contentFormats
        self.hasDetailedDescriptions = hasDetailedDescriptions
        self.hasUniqueContent = hasUniqueContent
        self.hasStatistics = hasStatistics
        self.hasOriginalResearch = hasOriginalResearch
        self.hasExpertQuotes = hasExpertQuotes
    }
}

/// Score breakdown by category
public struct ScoreBreakdown {
    public let contentQuality: Double
    public let structuredData: Double
    public let authoritySignals: Double
    public let technicalOptimization: Double
    public let freshness: Double
    public let comprehensiveness: Double
    public let citations: Double
}

/// Overall GEO score result
public struct GEOScore {
    public let overall: Double
    public let grade: String
    public let breakdown: ScoreBreakdown
    public let readinessLevel: String
    public let estimatedVisibility: String
    public let recommendations: [String]
    public let timestamp: Date
}

/// Request data for tracker detection
public struct RequestData {
    public let timestamps: [TimeInterval]
    public let interactionData: [String: Double]?
    public let accessPattern: [String: Int]?
    public let geographicData: [String: Any]?
    public let fingerprintData: [String: Bool]?
    public let behaviorData: [String: Any]?

    public init(
        timestamps: [TimeInterval],
        interactionData: [String: Double]? = nil,
        accessPattern: [String: Int]? = nil,
        geographicData: [String: Any]? = nil,
        fingerprintData: [String: Bool]? = nil,
        behaviorData: [String: Any]? = nil
    ) {
        self.timestamps = timestamps
        self.interactionData = interactionData
        self.accessPattern = accessPattern
        self.geographicData = geographicData
        self.fingerprintData = fingerprintData
        self.behaviorData = behaviorData
    }
}

/// Threat analysis result
public struct ThreatAnalysis {
    public let threatScore: Double
    public let classification: String
    public let riskLevel: String
    public let shouldBlock: Bool
    public let shouldChallenge: Bool
    public let recommendations: [String]
}

/// Influence analysis result
public struct InfluenceAnalysis {
    public let influenceScore: Double
    public let classification: String
    public let threatLevel: String
    public let authenticityConfidence: Double
    public let shouldFlag: Bool
    public let shouldRemove: Bool
    public let recommendations: [String]
}

// MARK: - Helper Extensions

extension Double {
    func rounded(toPlaces places: Int) -> Double {
        let divisor = pow(10.0, Double(places))
        return (self * divisor).rounded() / divisor
    }
}

// MARK: - Tracker Detector

private class TrackerDetector {
    func analyze(_ request: RequestData) -> ThreatAnalysis {
        // Simplified implementation - full version matches PHP TrackerDetector
        let rapidAccessScore = detectRapidAccess(request.timestamps)
        let threatScore = rapidAccessScore * 0.25 // Weighted

        return ThreatAnalysis(
            threatScore: threatScore,
            classification: threatScore >= 70 ? "malicious_tracker" : "legitimate_user",
            riskLevel: threatScore >= 70 ? "HIGH" : "LOW",
            shouldBlock: threatScore >= 70,
            shouldChallenge: threatScore >= 50 && threatScore < 70,
            recommendations: threatScore >= 70 ? ["BLOCK: Malicious tracker detected"] : []
        )
    }

    private func detectRapidAccess(_ timestamps: [TimeInterval]) -> Double {
        guard timestamps.count >= 2 else { return 0 }

        let timespan = timestamps.max()! - timestamps.min()!
        guard timespan > 0 else { return 100 }

        let requestsPerSecond = Double(timestamps.count) / timespan
        return min(100, (requestsPerSecond / 10.0) * 100)
    }
}

// MARK: - Influence Bot Detector

private class InfluenceBotDetector {
    func analyze(_ content: ContentData) -> InfluenceAnalysis {
        // Simplified implementation - full version matches PHP InfluenceBotDetector
        var score: Double = 0

        // Check for synthetic content indicators
        if let text = content.text {
            if text.range(of: "furthermore|moreover|consequently", options: .regularExpression) != nil {
                score += 20
            }
        }

        return InfluenceAnalysis(
            influenceScore: score,
            classification: score >= 80 ? "coordinated_influence_campaign" : "authentic_content",
            threatLevel: score >= 80 ? "CRITICAL" : "MINIMAL",
            authenticityConfidence: 100 - score,
            shouldFlag: score >= 60,
            shouldRemove: score >= 80,
            recommendations: score >= 80 ? ["REMOVE: Coordinated campaign detected"] : []
        )
    }
}
