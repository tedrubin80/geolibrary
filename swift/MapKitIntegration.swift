// MapKit Integration for GEO Optimizer
// Based on official Apple Developer Documentation:
// - MapKit for SwiftUI: https://developer.apple.com/documentation/mapkit/mapkit-for-swiftui
// - MKLocalSearch: https://developer.apple.com/documentation/mapkit/mklocalsearch
// - MapKit Annotations: https://developer.apple.com/documentation/mapkit/mapkit-annotations
// - WWDC 2024 "Unlock the power of places with MapKit": https://developer.apple.com/videos/play/wwdc2024/10097/
// - WWDC 2025 "Go further with MapKit": https://developer.apple.com/videos/play/wwdc2025/204/

import Foundation
import MapKit
import SwiftUI
import CoreLocation

/// MapKit integration for displaying business GEO scores on maps
/// Uses official Apple MapKit APIs for business search and annotation
@available(iOS 17.0, macOS 14.0, *)
public class GEOMapKitIntegrator {

    private let geoOptimizer = GEOOptimizer()

    // MARK: - Business Search with GEO Scoring

    /// Search for businesses using MKLocalSearch and calculate their GEO scores
    /// - Parameters:
    ///   - query: Natural language search query (e.g., "restaurants", "hotels")
    ///   - region: Optional map region to search within
    /// - Returns: Array of businesses with GEO scores
    public func searchBusinessesWithGEOScores(
        query: String,
        in region: MKCoordinateRegion? = nil
    ) async throws -> [GEOBusinessResult] {

        // Create MKLocalSearch.Request as per Apple documentation
        // https://developer.apple.com/documentation/mapkit/mklocalsearch/request
        let searchRequest = MKLocalSearch.Request()
        searchRequest.naturalLanguageQuery = query

        if let region = region {
            searchRequest.region = region
        }

        // Perform search using MKLocalSearch
        // https://developer.apple.com/documentation/mapkit/mklocalsearch
        let search = MKLocalSearch(request: searchRequest)
        let response = try await search.start()

        // Process results and calculate GEO scores
        var results: [GEOBusinessResult] = []

        for mapItem in response.mapItems {
            let geoScore = await calculateGEOScoreForMapItem(mapItem)

            let business = GEOBusinessResult(
                mapItem: mapItem,
                geoScore: geoScore,
                name: mapItem.name ?? "Unknown Business",
                category: mapItem.pointOfInterestCategory?.rawValue ?? "Business",
                coordinate: mapItem.placemark.coordinate,
                address: formatAddress(mapItem.placemark)
            )

            results.append(business)
        }

        // Sort by GEO score (highest first)
        return results.sorted { $0.geoScore.overall > $1.geoScore.overall }
    }

    /// Calculate GEO score for an MKMapItem
    private func calculateGEOScoreForMapItem(_ mapItem: MKMapItem) async -> GEOScore {

        // Extract data from MKMapItem
        let contentData = ContentData(
            text: mapItem.name,
            hasLLMSTxt: false, // Most businesses don't have this yet
            hasSchema: mapItem.url != nil, // Presence of URL suggests structured data
            schemaTypes: extractSchemaTypes(from: mapItem),
            isVerifiedBusiness: mapItem.phoneNumber != nil, // Phone number = verified
            hasReviews: false, // MKMapItem doesn't expose review data directly
            averageRating: 0,
            externalCitations: nil,
            isMobileFriendly: true, // Apple Maps businesses are mobile-optimized
            pageSpeedScore: nil,
            usesHTTPS: mapItem.url?.scheme == "https",
            hasSitemap: false,
            lastUpdated: nil,
            hasFAQ: false,
            hasHowTo: false,
            contentFormats: ["map", "location"],
            hasDetailedDescriptions: mapItem.pointOfInterestCategory != nil,
            hasUniqueContent: true,
            hasStatistics: false,
            hasOriginalResearch: false,
            hasExpertQuotes: false
        )

        return geoOptimizer.calculateReadinessScore(for: contentData)
    }

    private func extractSchemaTypes(from mapItem: MKMapItem) -> [String]? {
        guard let category = mapItem.pointOfInterestCategory else { return nil }

        // Map MKPointOfInterestCategory to Schema.org types
        switch category {
        case .restaurant: return ["Restaurant", "LocalBusiness"]
        case .hotel: return ["Hotel", "LodgingBusiness"]
        case .cafe: return ["CafeOrCoffeeShop", "FoodEstablishment"]
        case .bakery: return ["Bakery", "FoodEstablishment"]
        case .pharmacy: return ["Pharmacy", "LocalBusiness"]
        case .hospital: return ["Hospital", "MedicalOrganization"]
        case .school: return ["School", "EducationalOrganization"]
        case .library: return ["Library", "LocalBusiness"]
        case .museum: return ["Museum", "TouristAttraction"]
        case .store: return ["Store", "LocalBusiness"]
        case .carRental: return ["AutoRental", "AutomotiveBusiness"]
        case .evCharger: return ["AutoRepair", "AutomotiveBusiness"]
        case .gasStation: return ["GasStation", "AutomotiveBusiness"]
        case .atm: return ["BankOrCreditUnion", "FinancialService"]
        case .bank: return ["BankOrCreditUnion", "FinancialService"]
        default: return ["LocalBusiness"]
        }
    }

    private func formatAddress(_ placemark: MKPlacemark) -> String {
        let components = [
            placemark.thoroughfare,
            placemark.locality,
            placemark.administrativeArea,
            placemark.postalCode
        ].compactMap { $0 }

        return components.joined(separator: ", ")
    }
}

// MARK: - SwiftUI Map View with GEO Annotations

/// SwiftUI Map view displaying businesses with GEO score annotations
/// Uses MapKit for SwiftUI as documented at:
/// https://developer.apple.com/documentation/mapkit/mapkit-for-swiftui
@available(iOS 17.0, macOS 14.0, *)
public struct GEOBusinessMapView: View {

    @State private var position: MapCameraPosition = .automatic
    @State private var selectedBusiness: GEOBusinessResult?
    @State private var businesses: [GEOBusinessResult] = []
    @State private var searchQuery: String = ""
    @State private var isLoading: Bool = false
    @State private var errorMessage: String?

    private let integrator = GEOMapKitIntegrator()

    public init() {}

    public var body: some View {
        VStack(spacing: 0) {
            // Search bar
            searchBar

            // Map view using MapKit for SwiftUI
            // https://developer.apple.com/documentation/mapkit/mapkit-for-swiftui
            Map(position: $position, selection: $selectedBusiness) {
                ForEach(businesses) { business in
                    // Use MapAnnotation for custom annotations
                    // https://developer.apple.com/documentation/mapkit/mapannotation
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

            // Selected business details
            if let selected = selectedBusiness {
                BusinessDetailView(business: selected)
                    .transition(.move(edge: .bottom))
            }

            // Loading/error states
            if isLoading {
                ProgressView("Searching businesses...")
                    .padding()
            }

            if let error = errorMessage {
                Text(error)
                    .foregroundColor(.red)
                    .padding()
            }
        }
    }

    private var searchBar: some View {
        HStack {
            TextField("Search businesses (e.g., restaurants, hotels)", text: $searchQuery)
                .textFieldStyle(.roundedBorder)
                .onSubmit {
                    Task {
                        await performSearch()
                    }
                }

            Button("Search") {
                Task {
                    await performSearch()
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(searchQuery.isEmpty || isLoading)
        }
        .padding()
    }

    private func performSearch() async {
        isLoading = true
        errorMessage = nil

        do {
            businesses = try await integrator.searchBusinessesWithGEOScores(query: searchQuery)

            // Update camera to show results
            if let firstBusiness = businesses.first {
                position = .region(MKCoordinateRegion(
                    center: firstBusiness.coordinate,
                    span: MKCoordinateSpan(latitudeDelta: 0.1, longitudeDelta: 0.1)
                ))
            }

        } catch {
            errorMessage = "Search failed: \(error.localizedDescription)"
        }

        isLoading = false
    }
}

// MARK: - Custom Annotation View

/// Custom annotation view showing GEO score with color coding
@available(iOS 17.0, macOS 14.0, *)
struct GEOScoreAnnotationView: View {
    let business: GEOBusinessResult

    var body: some View {
        VStack(spacing: 2) {
            // Score badge
            Text(business.geoScore.grade)
                .font(.system(size: 12, weight: .bold))
                .foregroundColor(.white)
                .padding(6)
                .background(gradeColor)
                .clipShape(Circle())
                .shadow(radius: 2)

            // Business category icon
            Image(systemName: categoryIcon)
                .font(.system(size: 14))
                .foregroundColor(gradeColor)
        }
    }

    private var gradeColor: Color {
        let score = business.geoScore.overall
        switch score {
        case 80...: return .green
        case 60..<80: return .blue
        case 40..<60: return .orange
        default: return .red
        }
    }

    private var categoryIcon: String {
        switch business.category {
        case _ where business.category.contains("Restaurant"): return "fork.knife"
        case _ where business.category.contains("Hotel"): return "bed.double"
        case _ where business.category.contains("Cafe"): return "cup.and.saucer"
        case _ where business.category.contains("Store"): return "cart"
        case _ where business.category.contains("Hospital"): return "cross.case"
        case _ where business.category.contains("School"): return "graduationcap"
        default: return "mappin.circle"
        }
    }
}

// MARK: - Business Detail View

/// Detail view for selected business
@available(iOS 17.0, macOS 14.0, *)
struct BusinessDetailView: View {
    let business: GEOBusinessResult

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                VStack(alignment: .leading) {
                    Text(business.name)
                        .font(.headline)

                    Text(business.address)
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                }

                Spacer()

                // GEO Score badge
                VStack {
                    Text("\(Int(business.geoScore.overall))")
                        .font(.title2)
                        .fontWeight(.bold)

                    Text(business.geoScore.grade)
                        .font(.caption)
                }
                .padding(8)
                .background(gradeColor.opacity(0.2))
                .cornerRadius(8)
            }

            // Readiness level
            Text(business.geoScore.readinessLevel)
                .font(.caption)
                .foregroundColor(.secondary)

            // Recommendations
            if !business.geoScore.recommendations.isEmpty {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Recommendations:")
                        .font(.caption)
                        .fontWeight(.semibold)

                    ForEach(business.geoScore.recommendations, id: \.self) { recommendation in
                        Text("• \(recommendation)")
                            .font(.caption2)
                            .foregroundColor(.secondary)
                    }
                }
            }

            // Actions
            HStack {
                if let url = business.mapItem.url {
                    Link("Website", destination: url)
                        .buttonStyle(.bordered)
                }

                if let phone = business.mapItem.phoneNumber {
                    Link("Call", destination: URL(string: "tel:\(phone)")!)
                        .buttonStyle(.bordered)
                }

                Button("Directions") {
                    business.mapItem.openInMaps()
                }
                .buttonStyle(.borderedProminent)
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(12)
        .shadow(radius: 4)
        .padding()
    }

    private var gradeColor: Color {
        let score = business.geoScore.overall
        switch score {
        case 80...: return .green
        case 60..<80: return .blue
        case 40..<60: return .orange
        default: return .red
        }
    }
}

// MARK: - Data Models

/// Business search result with GEO score
@available(iOS 17.0, macOS 14.0, *)
public struct GEOBusinessResult: Identifiable, Hashable {
    public let id = UUID()
    public let mapItem: MKMapItem
    public let geoScore: GEOScore
    public let name: String
    public let category: String
    public let coordinate: CLLocationCoordinate2D
    public let address: String

    public static func == (lhs: GEOBusinessResult, rhs: GEOBusinessResult) -> Bool {
        lhs.id == rhs.id
    }

    public func hash(into hasher: inout Hasher) {
        hasher.combine(id)
    }
}

// Make GEOScore Hashable for SwiftUI
extension GEOScore: Hashable {
    public static func == (lhs: GEOScore, rhs: GEOScore) -> Bool {
        lhs.overall == rhs.overall && lhs.grade == rhs.grade
    }

    public func hash(into hasher: inout Hasher) {
        hasher.combine(overall)
        hasher.combine(grade)
    }
}

// Make CLLocationCoordinate2D Hashable
extension CLLocationCoordinate2D: @retroactive Hashable {
    public static func == (lhs: CLLocationCoordinate2D, rhs: CLLocationCoordinate2D) -> Bool {
        lhs.latitude == rhs.latitude && lhs.longitude == rhs.longitude
    }

    public func hash(into hasher: inout Hasher) {
        hasher.combine(latitude)
        hasher.combine(longitude)
    }
}
