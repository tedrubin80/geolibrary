"""GEO Metrics Calculator
Based on formulas from KDD '24 paper: "GEO: Generative Engine Optimization"
"""

import math
import re
from typing import Dict, List, Tuple, Optional
from loguru import logger


class GEOMetricsCalculator:
    """Calculate GEO performance metrics"""

    @staticmethod
    def extract_citations(response_text: str, source_titles: List[str]) -> Dict[str, any]:
        """
        Extract which sources were cited in the response

        Args:
            response_text: The generated response
            source_titles: List of source titles to look for

        Returns:
            Dict with citation information:
                - cited_sources: List of source titles that were cited
                - citation_positions: Dict mapping source -> first position cited
                - citation_frequency: Dict mapping source -> count of citations
        """
        cited_sources = []
        citation_positions = {}
        citation_frequency = {}

        # Split into sentences for position tracking
        sentences = re.split(r'[.!?]+', response_text)
        sentences = [s.strip() for s in sentences if s.strip()]

        for source_title in source_titles:
            # Check if source is mentioned (case-insensitive, partial match)
            source_lower = source_title.lower()
            found_positions = []

            for i, sentence in enumerate(sentences, 1):
                if source_lower in sentence.lower():
                    found_positions.append(i)

            if found_positions:
                cited_sources.append(source_title)
                citation_positions[source_title] = min(found_positions)  # First position
                citation_frequency[source_title] = len(found_positions)

        return {
            "cited_sources": cited_sources,
            "citation_positions": citation_positions,
            "citation_frequency": citation_frequency,
            "total_citations": sum(citation_frequency.values())
        }

    @staticmethod
    def calculate_word_count(text: str) -> int:
        """Calculate word count of text"""
        words = text.split()
        return len(words)

    @staticmethod
    def calculate_position_score(
        response_text: str,
        target_source: str,
        source_titles: List[str]
    ) -> Tuple[float, Optional[int]]:
        """
        Calculate Position Score (PS)

        Formula: PS = 1 / position_of_target_source

        Args:
            response_text: Generated response
            target_source: The source we're optimizing for
            source_titles: All source titles

        Returns:
            Tuple of (position_score, position)
            - position_score: 1/position or 0 if not cited
            - position: Position where target source first appears (1-indexed), None if not cited
        """
        citation_info = GEOMetricsCalculator.extract_citations(response_text, source_titles)

        if target_source in citation_info["citation_positions"]:
            position = citation_info["citation_positions"][target_source]
            position_score = 1.0 / position
            return position_score, position
        else:
            return 0.0, None

    @staticmethod
    def calculate_pawc(
        response_text: str,
        target_source: str,
        source_titles: List[str]
    ) -> Tuple[float, Dict[str, any]]:
        """
        Calculate Position-Adjusted Word Count (PAWC)

        Formula from paper:
        PAWC = Σ (word_count_i × (1 / sqrt(position_i)))

        Where:
        - word_count_i = number of words from target source in position i
        - position_i = sentence position (1-indexed)

        This is the PRIMARY metric in the GEO paper for measuring visibility.

        Args:
            response_text: Generated response
            target_source: The source we're optimizing for
            source_titles: All source titles

        Returns:
            Tuple of (pawc_score, breakdown_dict)
        """
        # Split into sentences
        sentences = re.split(r'[.!?]+', response_text)
        sentences = [s.strip() for s in sentences if s.strip()]

        target_lower = target_source.lower()
        pawc_score = 0.0
        position_breakdown = {}

        for position, sentence in enumerate(sentences, 1):
            # Check if this sentence cites the target source
            if target_lower in sentence.lower():
                # Count words in this sentence
                word_count = len(sentence.split())

                # Calculate position weight: 1 / sqrt(position)
                position_weight = 1.0 / math.sqrt(position)

                # Add to PAWC score
                contribution = word_count * position_weight
                pawc_score += contribution

                position_breakdown[position] = {
                    "word_count": word_count,
                    "position_weight": position_weight,
                    "contribution": contribution
                }

        return pawc_score, position_breakdown

    @staticmethod
    def calculate_all_metrics(
        response_text: str,
        target_source: str,
        source_titles: List[str],
        baseline_metrics: Optional[Dict] = None
    ) -> Dict[str, any]:
        """
        Calculate all GEO metrics for a response

        Args:
            response_text: Generated response text
            target_source: The source being optimized (our content)
            source_titles: List of all competing source titles
            baseline_metrics: Optional baseline metrics for comparison

        Returns:
            Dict with all calculated metrics
        """
        # Word Count (WC)
        word_count = GEOMetricsCalculator.calculate_word_count(response_text)

        # Position Score (PS)
        position_score, citation_position = GEOMetricsCalculator.calculate_position_score(
            response_text, target_source, source_titles
        )

        # Position-Adjusted Word Count (PAWC) - PRIMARY METRIC
        pawc, position_breakdown = GEOMetricsCalculator.calculate_pawc(
            response_text, target_source, source_titles
        )

        # Citation information
        citation_info = GEOMetricsCalculator.extract_citations(response_text, source_titles)

        # Was target source cited?
        cited = 1 if target_source in citation_info["cited_sources"] else 0

        # Citation frequency for target
        citation_frequency = citation_info["citation_frequency"].get(target_source, 0)

        # Calculate improvements vs baseline if provided
        improvements = {}
        if baseline_metrics:
            if baseline_metrics.get("pawc", 0) > 0:
                improvements["pawc"] = ((pawc - baseline_metrics["pawc"]) / baseline_metrics["pawc"]) * 100
            if baseline_metrics.get("word_count", 0) > 0:
                improvements["word_count"] = ((word_count - baseline_metrics["word_count"]) / baseline_metrics["word_count"]) * 100
            if baseline_metrics.get("position_score", 0) > 0:
                improvements["position_score"] = ((position_score - baseline_metrics["position_score"]) / baseline_metrics["position_score"]) * 100

        return {
            # Core metrics
            "word_count": word_count,
            "position_score": position_score,
            "pawc": pawc,

            # Citation metrics
            "cited": cited,
            "citation_position": citation_position,
            "citation_frequency": citation_frequency,

            # Detailed breakdown
            "position_breakdown": position_breakdown,
            "citation_info": citation_info,

            # Improvements vs baseline
            "improvements": improvements
        }

    @staticmethod
    def format_metrics_report(metrics: Dict) -> str:
        """Format metrics as human-readable report"""
        report = []
        report.append("=" * 60)
        report.append("GEO METRICS REPORT")
        report.append("=" * 60)

        report.append(f"\n📊 CORE METRICS:")
        report.append(f"   • Position-Adjusted Word Count (PAWC): {metrics['pawc']:.2f}")
        report.append(f"   • Word Count (WC): {metrics['word_count']}")
        report.append(f"   • Position Score (PS): {metrics['position_score']:.4f}")

        report.append(f"\n🎯 CITATION METRICS:")
        report.append(f"   • Cited: {'✓ Yes' if metrics['cited'] else '✗ No'}")
        report.append(f"   • Citation Position: {metrics['citation_position'] if metrics['citation_position'] else 'N/A'}")
        report.append(f"   • Citation Frequency: {metrics['citation_frequency']}")

        if metrics.get("improvements"):
            report.append(f"\n📈 IMPROVEMENTS vs BASELINE:")
            for key, value in metrics["improvements"].items():
                symbol = "🔼" if value > 0 else "🔽"
                report.append(f"   • {key.upper()}: {symbol} {value:+.1f}%")

        if metrics.get("position_breakdown"):
            report.append(f"\n📍 POSITION BREAKDOWN:")
            for pos, data in sorted(metrics["position_breakdown"].items()):
                report.append(f"   Position {pos}: {data['word_count']} words × {data['position_weight']:.3f} weight = {data['contribution']:.2f}")

        report.append("=" * 60)

        return "\n".join(report)


# Example usage and testing
if __name__ == "__main__":
    # Sample data
    response = """
    According to Joe's Pizza, the best Italian restaurants in New York offer authentic cuisine.
    The restaurant industry shows that customer satisfaction is key.
    Pizza Palace mentions that fresh ingredients matter most.
    Joe's Pizza has been serving customers since 1975, with over 10,000 satisfied diners.
    Many experts agree that quality ingredients are essential.
    """

    target = "Joe's Pizza"
    sources = ["Joe's Pizza", "Pizza Palace", "Italian Cuisine Guide"]

    # Calculate metrics
    calc = GEOMetricsCalculator()
    metrics = calc.calculate_all_metrics(response, target, sources)

    # Print report
    print(calc.format_metrics_report(metrics))

    # Test with baseline comparison
    baseline_metrics = {
        "pawc": 10.0,
        "word_count": 50,
        "position_score": 0.5
    }

    metrics_with_baseline = calc.calculate_all_metrics(
        response, target, sources, baseline_metrics
    )

    print("\n\nWITH BASELINE COMPARISON:")
    print(calc.format_metrics_report(metrics_with_baseline))
