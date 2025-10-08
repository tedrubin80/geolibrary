# GEO Optimizer - Apple Ecosystem Integration Guide

**Version**: 1.0.0
**Date**: October 8, 2025
**Platform**: iOS 17+, macOS 14+
**Author**: GEOLibrary Team

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [MapKit Integration](#mapkit-integration)
4. [Safari Extension](#safari-extension)
5. [Security Features](#security-features)
6. [Implementation Steps](#implementation-steps)
7. [API Reference](#api-reference)
8. [Best Practices](#best-practices)

---

## Overview

This document provides comprehensive implementation guidance for integrating the GEO Optimizer library into the Apple ecosystem, including:

- **MapKit Integration**: Display businesses with GEO scores on Apple Maps
- **Safari Extension**: Consumer protection against trackers and influence bots
- **iOS/macOS Native Library**: Swift implementation of GEO optimization
- **Security Features**: Tracker detection and bot identification

### Key Features

- ✅ Native Swift implementation (no PHP dependencies)
- ✅ MapKit business search with GEO scoring
- ✅ Safari content blocker for tracker protection
- ✅ Real-time influence bot detection
- ✅ SwiftUI map views with custom annotations
- ✅ Full support for iOS 17+ and macOS 14+

---

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────┐
│                  GEO Optimizer                       │
│                  (Core Library)                      │
│  - GEO Readiness Scoring                            │
│  - Tracker Detection                                 │
│  - Influence Bot Detection                           │
└─────────────────────────────────────────────────────┘
              │                           │
              │                           │
      ┌───────▼───────┐           ┌──────▼──────┐
      │   MapKit      │           │   Safari    │
      │  Integration  │           │  Extension  │
      │               │           │             │
      │ - MKLocalSearch│          │ - Content   │
      │ - Annotations │           │   Blocker   │
      │ - SwiftUI Maps│           │ - JS Bridge │
      └───────────────┘           └─────────────┘
```

### Data Flow

1. **Content Analysis**:
   ```
   User Input → Content Data → GEO Optimizer → GEO Score + Recommendations
   ```

2. **Map Search**:
   ```
   Search Query → MKLocalSearch → Business Results → GEO Scoring → Annotated Map
   ```

3. **Safari Protection**:
   ```
   Web Page → Content Extraction → Security Analysis → Block/Allow Decision
   ```

---

## MapKit Integration

### Official Documentation References

All MapKit integration is based on official Apple Developer documentation:

- **MapKit Framework**: https://developer.apple.com/documentation/mapkit/
- **MapKit for SwiftUI**: https://developer.apple.com/documentation/mapkit/mapkit-for-swiftui
- **MKLocalSearch**: https://developer.apple.com/documentation/mapkit/mklocalsearch
- **MapKit Annotations**: https://developer.apple.com/documentation/mapkit/mapkit-annotations
- **WWDC 2024 Session**: "Unlock the power of places with MapKit"
- **WWDC 2025 Session**: "Go further with MapKit" (Geocoding API, Address Representations API)

### Implementation

#### 1. Basic Setup

```swift
import SwiftUI
import MapKit

@main
struct GEOMapApp: App {
    var body: some Scene {
        WindowGroup {
            GEOBusinessMapView()
        }
    }
}
```

#### 2. Business Search with GEO Scoring

```swift
let integrator = GEOMapKitIntegrator()

// Search for businesses
let businesses = try await integrator.searchBusinessesWithGEOScores(
    query: "restaurants",
    in: MKCoordinateRegion(
        center: CLLocationCoordinate2D(latitude: 40.7128, longitude: -74.0060),
        span: MKCoordinateSpan(latitudeDelta: 0.1, longitudeDelta: 0.1)
    )
)

// Results are sorted by GEO score (highest first)
for business in businesses {
    print("\(business.name): GEO Score \(business.geoScore.overall)")
}
```

#### 3. SwiftUI Map View

```swift
struct GEOBusinessMapView: View {
    @State private var businesses: [GEOBusinessResult] = []
    @State private var position: MapCameraPosition = .automatic
    @State private var selectedBusiness: GEOBusinessResult?

    var body: some View {
        Map(position: $position, selection: $selectedBusiness) {
            ForEach(businesses) { business in
                Annotation(
                    business.name,
                    coordinate: business.coordinate
                ) {
                    GEOScoreAnnotationView(business: business)
                }
                .tag(business)
            }
        }
        .mapStyle(.standard(elevation: .realistic))
        .mapControls {
            MapUserLocationButton()
            MapCompass()
            MapScaleView()
        }
    }
}
```

#### 4. Custom Annotations

```swift
struct GEOScoreAnnotationView: View {
    let business: GEOBusinessResult

    var body: some View {
        VStack(spacing: 2) {
            // Score badge with color coding
            Text(business.geoScore.grade)
                .font(.system(size: 12, weight: .bold))
                .foregroundColor(.white)
                .padding(6)
                .background(gradeColor)
                .clipShape(Circle())

            // Category icon
            Image(systemName: categoryIcon)
                .font(.system(size: 14))
                .foregroundColor(gradeColor)
        }
    }

    private var gradeColor: Color {
        switch business.geoScore.overall {
        case 80...: return .green    // Excellent
        case 60..<80: return .blue   // Good
        case 40..<60: return .orange // Fair
        default: return .red         // Poor
        }
    }
}
```

### MKLocalSearch Integration

#### Search Request Configuration

Based on Apple documentation:
https://developer.apple.com/documentation/mapkit/mklocalsearch/request

```swift
let searchRequest = MKLocalSearch.Request()
searchRequest.naturalLanguageQuery = "pizza restaurants"
searchRequest.region = mapRegion
searchRequest.resultTypes = [.pointOfInterest]

let search = MKLocalSearch(request: searchRequest)
let response = try await search.start()

// Process results
for mapItem in response.mapItems {
    let geoScore = await calculateGEOScore(for: mapItem)
    // Display on map with GEO score annotation
}
```

#### Schema Type Mapping

Map MKPointOfInterestCategory to Schema.org types:

| MKPointOfInterestCategory | Schema.org Type |
|--------------------------|-----------------|
| `.restaurant` | Restaurant, LocalBusiness |
| `.hotel` | Hotel, LodgingBusiness |
| `.cafe` | CafeOrCoffeeShop, FoodEstablishment |
| `.store` | Store, LocalBusiness |
| `.hospital` | Hospital, MedicalOrganization |
| `.school` | School, EducationalOrganization |
| `.museum` | Museum, TouristAttraction |
| `.gasStation` | GasStation, AutomotiveBusiness |
| `.bank` | BankOrCreditUnion, FinancialService |

### Performance Optimization

#### Annotation Clustering

Based on Apple documentation:
https://developer.apple.com/documentation/MapKit/decluttering-a-map-with-mapkit-annotation-clustering

```swift
Map(position: $position) {
    ForEach(businesses) { business in
        Annotation(business.name, coordinate: business.coordinate) {
            GEOScoreAnnotationView(business: business)
        }
        .annotationTitles(.hidden)
        .tag(business)
    }
}
.mapClusterAnnotation { cluster in
    ClusterAnnotationView(count: cluster.count)
}
```

---

## Safari Extension

### Official Documentation References

Safari extension implementation based on:

- **Safari App Extensions**: https://developer.apple.com/documentation/safariservices/safari_app_extensions
- **Safari Web Extensions**: https://developer.apple.com/documentation/safariservices/safari_web_extensions
- **Content Blocking**: https://developer.apple.com/documentation/safariservices/creating_a_content_blocker

### Architecture

```
┌──────────────────────────────────────────────┐
│         Safari Browser (macOS/iOS)           │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │  Web Page                              │ │
│  │  ┌──────────────────────────────────┐ │ │
│  │  │  Injected JavaScript             │ │ │
│  │  │  - Extract content               │ │ │
│  │  │  - Track interactions            │ │ │
│  │  │  - Monitor mutations             │ │ │
│  │  └──────────────────────────────────┘ │ │
│  └────────────────────────────────────────┘ │
│                    │                         │
│                    ▼                         │
│  ┌────────────────────────────────────────┐ │
│  │  Safari Extension                      │ │
│  │  - GEO Analysis                        │ │
│  │  - Tracker Detection                   │ │
│  │  - Bot Detection                       │ │
│  └────────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────┐
        │  Content Blocker       │
        │  - Block trackers      │
        │  - Hide elements       │
        └────────────────────────┘
```

### Implementation

#### 1. Extension Setup

**Info.plist Configuration**:

```xml
<key>NSExtension</key>
<dict>
    <key>NSExtensionPointIdentifier</key>
    <string>com.apple.Safari.extension</string>
    <key>NSExtensionPrincipalClass</key>
    <string>$(PRODUCT_MODULE_NAME).GEOSafariExtension</string>
    <key>SFSafariContentScript</key>
    <array>
        <dict>
            <key>Script</key>
            <string>script.js</string>
        </dict>
    </array>
</dict>
```

#### 2. Message Handling

```swift
public class GEOSafariExtension: NSObject, NSExtensionRequestHandling {

    public func beginRequest(with context: NSExtensionContext) {
        let item = context.inputItems[0] as! NSExtensionItem
        let message = item.userInfo?[SFExtensionMessageKey]

        guard let messageDictionary = message as? [String: Any],
              let action = messageDictionary["action"] as? String else {
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -1))
            return
        }

        switch action {
        case "analyzeContent":
            analyzePageContent(messageDictionary, context: context)
        case "detectTrackers":
            detectTrackers(messageDictionary, context: context)
        case "checkInfluenceBots":
            checkInfluenceBots(messageDictionary, context: context)
        default:
            context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -1))
        }
    }
}
```

#### 3. Content Analysis

```swift
private func analyzePageContent(_ message: [String: Any], context: NSExtensionContext) {
    guard let content = message["content"] as? String else {
        context.cancelRequest(withError: NSError(domain: "GEOExtension", code: -2))
        return
    }

    let contentData = ContentData(
        text: content,
        hasSchema: message["hasSchema"] as? Bool ?? false,
        usesHTTPS: message["isHTTPS"] as? Bool ?? false
    )

    let geoScore = geoOptimizer.calculateReadinessScore(for: contentData)
    let influenceAnalysis = geoOptimizer.detectInfluenceBots(in: contentData)

    let response: [String: Any] = [
        "geoScore": [
            "overall": geoScore.overall,
            "grade": geoScore.grade
        ],
        "influenceAnalysis": [
            "score": influenceAnalysis.influenceScore,
            "shouldFlag": influenceAnalysis.shouldFlag
        ]
    ]

    let responseItem = NSExtensionItem()
    responseItem.userInfo = [SFExtensionMessageKey: response]
    context.completeRequest(returningItems: [responseItem])
}
```

#### 4. Content Blocker Rules

```swift
public class GEOContentBlocker {

    public static func generateBlockerRules() -> [[String: Any]] {
        return [
            // Block known tracking domains
            [
                "trigger": [
                    "url-filter": ".*",
                    "if-domain": [
                        "*doubleclick.net",
                        "*googlesyndication.com",
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

            // Hide cookie banners
            [
                "trigger": [
                    "url-filter": ".*"
                ],
                "action": [
                    "type": "css-display-none",
                    "selector": ".cookie-banner, #cookie-notice"
                ]
            ]
        ]
    }
}
```

#### 5. JavaScript Content Script

```javascript
// Extract page content for GEO analysis
function extractPageContent() {
    return {
        text: document.body.innerText,
        hasSchema: !!document.querySelector('script[type="application/ld+json"]'),
        schemaTypes: extractSchemaTypes(),
        isHTTPS: window.location.protocol === 'https:',
        url: window.location.href,
        title: document.title
    };
}

// Send analysis request to extension
function sendToExtension(action, data) {
    safari.extension.dispatchMessage(action, data);
}

// Analyze page on load
window.addEventListener('load', () => {
    setTimeout(() => {
        const pageContent = extractPageContent();
        sendToExtension('analyzeContent', { content: pageContent });
    }, 2000);
});
```

### Consumer Protection Features

#### Tracker Detection

```swift
let analysis = geoOptimizer.detectTrackers(in: requestData)

if analysis.shouldBlock {
    // Block the request
    print("🛡️ Blocked tracker: \(analysis.classification)")
} else if analysis.shouldChallenge {
    // Show CAPTCHA or warning
    print("⚠️ Suspicious activity detected")
}
```

#### Influence Bot Detection

```swift
let analysis = geoOptimizer.detectInfluenceBots(in: contentData)

if analysis.shouldRemove {
    // Flag content as likely bot-generated
    showWarning("This content may be part of an influence campaign")
} else if analysis.shouldFlag {
    // Add warning label
    showWarning("⚠️ Verify claims independently - AI-generated content detected")
}
```

#### Protection Badge

The extension displays a floating badge showing protection status:

```
🛡️ GEO Protected
Trackers blocked: 12
```

Clicking the badge shows detailed information:
- Trackers Blocked: 12
- Influence Bots Detected: 2
- Content Authenticity: 85%

---

## Security Features

### Tracker Detection System

Based on `/src/Security/TrackerDetector.php`

#### Detection Patterns

1. **Rapid Sequential Access** (25% weight)
   - Detects automated scraping (>10 requests/second)
   - Triggers: Block if detected

2. **Missing GEO Signals** (20% weight)
   - Detects non-human interaction patterns
   - Metrics: Hover duration, scroll depth, mouse movement entropy

3. **Metadata Harvesting** (15% weight)
   - Detects scripts accessing structured data without content
   - Triggers: Block if >80% metadata-only access

4. **Geographic Anomaly** (15% weight)
   - Detects VPN/proxy usage
   - Indicators: IP/timezone mismatch, datacenter IPs

5. **Fingerprint Resistance** (15% weight)
   - Detects privacy tools and anti-fingerprinting
   - Indicators: Canvas poisoning, WebGL randomization

6. **Behavioral Inconsistency** (10% weight)
   - Detects bot-like navigation
   - Indicators: Perfect timing, no typos, instant form fills

#### Implementation

```swift
let requestData = RequestData(
    timestamps: [timestamp1, timestamp2, timestamp3],
    interactionData: [
        "hover_duration": 0.8,
        "scroll_depth": 0.6,
        "mouse_movement_entropy": 0.7
    ],
    accessPattern: [
        "metadata_requests": 5,
        "content_requests": 20
    ]
)

let analysis = geoOptimizer.detectTrackers(in: requestData)

switch analysis.riskLevel {
case "CRITICAL", "HIGH":
    // Block request
    blockRequest()
case "MODERATE":
    // Challenge with CAPTCHA
    showCAPTCHA()
default:
    // Allow
    allowRequest()
}
```

### Influence Bot Detection System

Based on `/src/Security/InfluenceBotDetector.php`

#### Detection Patterns

1. **Coordinated Posting** (25% weight)
   - Detects multiple accounts posting similar content simultaneously
   - Metrics: Content similarity score, temporal clustering

2. **Synthetic Content** (20% weight)
   - Detects AI-generated text
   - Indicators: Perfect grammar, repetitive structure, generic phrasing

3. **Authority Manipulation** (20% weight)
   - Detects fake reviews and credentials
   - Indicators: Review bursts, overly positive sentiment, new accounts

4. **Sentiment Anomaly** (15% weight)
   - Detects emotional manipulation
   - Indicators: Extreme sentiment, manipulation keywords

5. **Network Clustering** (12% weight)
   - Detects bot networks
   - Indicators: Tight clustering, low diversity, shared fingerprints

6. **Temporal Pattern** (8% weight)
   - Detects automated posting schedules
   - Indicators: Regular intervals, 24/7 posting

#### Implementation

```swift
let contentData = ContentData(text: reviewText)
let analysis = geoOptimizer.detectInfluenceBots(in: contentData)

if analysis.influenceScore >= 80 {
    // Remove content
    removeContent()
    showWarning("Coordinated influence campaign detected")
} else if analysis.influenceScore >= 60 {
    // Flag content
    addWarningLabel("⚠️ This content may be AI-generated")
}

// Show authenticity score
displayAuthenticityScore(analysis.authenticityConfidence)
```

---

## Implementation Steps

### Step 1: Create Xcode Project

```bash
# Create new iOS/macOS app
# File → New → Project → Multiplatform → App

# Project settings:
# - Product Name: GEOOptimizer
# - Interface: SwiftUI
# - Language: Swift
# - Minimum iOS: 17.0
# - Minimum macOS: 14.0
```

### Step 2: Add Swift Files

Copy the following files to your Xcode project:

1. `GEOOptimizer.swift` - Core library
2. `MapKitIntegration.swift` - MapKit integration
3. `SafariExtension/GEOSafariExtension.swift` - Safari extension

### Step 3: Configure Capabilities

**Target → Signing & Capabilities**:

- ✅ Maps
- ✅ Location When In Use
- ✅ App Groups (for extension communication)

**For Safari Extension Target**:

- ✅ Safari Extensions
- ✅ App Groups (same as main app)

### Step 4: Add Frameworks

**Link the following frameworks**:

- MapKit.framework
- SafariServices.framework
- CoreLocation.framework

### Step 5: Configure Info.plist

**Main App**:

```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>GEO Optimizer uses your location to find nearby businesses and display GEO scores</string>

<key>Privacy - Location When In Use Usage Description</key>
<string>Show nearby businesses with GEO optimization scores</string>
```

**Safari Extension**:

```xml
<key>NSExtension</key>
<dict>
    <key>NSExtensionPointIdentifier</key>
    <string>com.apple.Safari.extension</string>
    <key>NSExtensionPrincipalClass</key>
    <string>$(PRODUCT_MODULE_NAME).GEOSafariExtension</string>
</dict>
```

### Step 6: Build and Run

```bash
# Build for iOS Simulator
# Product → Destination → iPhone 15 Pro
# Product → Run (⌘R)

# Build for macOS
# Product → Destination → My Mac
# Product → Run (⌘R)
```

### Step 7: Enable Safari Extension

**macOS**:

1. Safari → Preferences → Extensions
2. Enable "GEO Optimizer Extension"
3. Visit any website to see protection badge

---

## API Reference

### GEOOptimizer

#### calculateReadinessScore(for:)

Calculate comprehensive GEO readiness score for content.

```swift
func calculateReadinessScore(for content: ContentData) -> GEOScore
```

**Parameters**:
- `content`: ContentData structure with text, metadata, and signals

**Returns**: GEOScore with overall score, grade, breakdown, and recommendations

**Example**:

```swift
let content = ContentData(
    text: "Best Italian restaurant in NYC...",
    hasSchema: true,
    hasReviews: true,
    averageRating: 4.8
)

let score = optimizer.calculateReadinessScore(for: content)
print("Score: \(score.overall), Grade: \(score.grade)")
// Output: Score: 87.3, Grade: A
```

#### detectTrackers(in:)

Detect tracker and scraper patterns in request data.

```swift
func detectTrackers(in request: RequestData) -> ThreatAnalysis
```

**Parameters**:
- `request`: RequestData with timestamps, interactions, and behavioral data

**Returns**: ThreatAnalysis with threat score, classification, and recommendations

**Example**:

```swift
let request = RequestData(timestamps: [t1, t2, t3])
let analysis = optimizer.detectTrackers(in: request)

if analysis.shouldBlock {
    print("Threat detected: \(analysis.classification)")
}
```

#### detectInfluenceBots(in:)

Detect influence bots and manipulation campaigns.

```swift
func detectInfluenceBots(in content: ContentData) -> InfluenceAnalysis
```

**Parameters**:
- `content`: ContentData with text to analyze

**Returns**: InfluenceAnalysis with influence score, authenticity confidence, and recommendations

**Example**:

```swift
let content = ContentData(text: reviewText)
let analysis = optimizer.detectInfluenceBots(in: content)

print("Authenticity: \(analysis.authenticityConfidence)%")
if analysis.shouldFlag {
    print("Warning: Potential influence campaign")
}
```

### GEOMapKitIntegrator

#### searchBusinessesWithGEOScores(query:in:)

Search for businesses using MKLocalSearch and calculate GEO scores.

```swift
func searchBusinessesWithGEOScores(
    query: String,
    in region: MKCoordinateRegion? = nil
) async throws -> [GEOBusinessResult]
```

**Parameters**:
- `query`: Natural language search query (e.g., "pizza restaurants")
- `region`: Optional map region to search within

**Returns**: Array of GEOBusinessResult sorted by GEO score (highest first)

**Example**:

```swift
let businesses = try await integrator.searchBusinessesWithGEOScores(
    query: "restaurants",
    in: MKCoordinateRegion(
        center: CLLocationCoordinate2D(latitude: 40.7128, longitude: -74.0060),
        span: MKCoordinateSpan(latitudeDelta: 0.1, longitudeDelta: 0.1)
    )
)

for business in businesses {
    print("\(business.name): \(business.geoScore.grade)")
}
```

---

## Best Practices

### Performance

1. **Cache GEO Scores**
   ```swift
   private var scoreCache: [String: GEOScore] = [:]

   func getCachedScore(for businessID: String) -> GEOScore? {
       return scoreCache[businessID]
   }
   ```

2. **Limit Search Results**
   ```swift
   let businesses = try await integrator
       .searchBusinessesWithGEOScores(query: query)
       .prefix(20) // Limit to top 20 results
   ```

3. **Background Processing**
   ```swift
   Task.detached {
       let score = await optimizer.calculateReadinessScore(for: content)
       await MainActor.run {
           updateUI(with: score)
       }
   }
   ```

### Security

1. **Validate Input**
   ```swift
   guard !query.isEmpty, query.count <= 100 else {
       throw GEOError.invalidInput
   }
   ```

2. **Rate Limiting**
   ```swift
   private var lastRequestTime: Date?
   private let minimumRequestInterval: TimeInterval = 1.0

   func checkRateLimit() throws {
       if let lastTime = lastRequestTime,
          Date().timeIntervalSince(lastTime) < minimumRequestInterval {
           throw GEOError.rateLimitExceeded
       }
       lastRequestTime = Date()
   }
   ```

3. **Sanitize Content**
   ```swift
   func sanitizeContent(_ text: String) -> String {
       return text
           .trimmingCharacters(in: .whitespacesAndNewlines)
           .replacingOccurrences(of: "<script>", with: "")
           .replacingOccurrences(of: "</script>", with: "")
   }
   ```

### User Experience

1. **Loading States**
   ```swift
   if isLoading {
       ProgressView("Searching businesses...")
   }
   ```

2. **Error Handling**
   ```swift
   do {
       let businesses = try await search()
   } catch {
       showAlert("Search failed: \(error.localizedDescription)")
   }
   ```

3. **Accessibility**
   ```swift
   Text(business.geoScore.grade)
       .accessibilityLabel("GEO Score: \(business.geoScore.grade)")
       .accessibilityHint("Score indicates AI visibility optimization")
   ```

---

## Troubleshooting

### Common Issues

**Issue**: MapKit searches return no results

**Solution**:
- Check location permissions
- Verify search region is valid
- Ensure network connectivity

```swift
// Debug logging
print("Search query: \(query)")
print("Region: \(region)")
print("Results: \(response.mapItems.count)")
```

**Issue**: Safari extension not loading

**Solution**:
- Enable extension in Safari preferences
- Check Info.plist configuration
- Verify code signing

**Issue**: GEO scores always returning 0

**Solution**:
- Check ContentData initialization
- Verify all required fields are populated
- Enable debug logging in GEOOptimizer

---

## Additional Resources

### Apple Developer Documentation

- **MapKit**: https://developer.apple.com/documentation/mapkit/
- **Safari Extensions**: https://developer.apple.com/documentation/safariservices/safari_app_extensions
- **WWDC Videos**: https://developer.apple.com/videos/

### GEO Research

- **KDD '24 Paper**: "GEO: Generative Engine Optimization"
- **Patent Specifications**: See `PATENT_TECHNICAL_SPECIFICATION.md`

### Support

- **GitHub Issues**: https://github.com/tedrubin80/geolibrary/issues
- **Email**: support@geolibrary.com

---

**Document Version**: 1.0.0
**Last Updated**: October 8, 2025
**Platform**: iOS 17+, macOS 14+, Safari 17+
