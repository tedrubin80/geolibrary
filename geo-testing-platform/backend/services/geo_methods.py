"""GEO Method Transformations
Based on KDD '24 paper: "GEO: Generative Engine Optimization"
"""

from typing import Dict, Any
from enum import Enum


class GEOMethod(str, Enum):
    """GEO optimization methods from the paper"""
    BASELINE = "baseline"
    STATISTICS = "statistics"
    CITATION = "citation"
    AUTHORITATIVE = "authoritative"
    QUOTATION = "quotation"
    FLUENCY = "fluency"
    TECHNICAL = "technical"
    EASY = "easy"
    KEYWORD = "keyword"
    UNIQUE = "unique"


# System prompts for each GEO method
GEO_PROMPTS = {
    GEOMethod.BASELINE: {
        "system": "You are a helpful content writer. Rewrite content clearly and accurately.",
        "instruction": """Rewrite the following content maintaining its core information.
Keep it factual, neutral, and well-structured.

Original content:
{content}

Rewritten content:"""
    },

    GEOMethod.STATISTICS: {
        "system": "You are a data-driven content writer who excels at incorporating statistics and numerical data.",
        "instruction": """Rewrite the following content by adding RELEVANT STATISTICS and NUMERICAL DATA where appropriate.
Include percentages, numbers, data points, and quantitative information to support claims.
Ensure all statistics are realistic for the topic (you can use plausible examples if exact data isn't provided).

Guidelines:
- Add at least 3-5 statistical data points
- Use specific numbers instead of vague terms
- Include percentages where relevant
- Make statistics credible and topic-appropriate

Original content:
{content}

Rewritten content with statistics:"""
    },

    GEOMethod.CITATION: {
        "system": "You are an academic content writer who properly cites authoritative sources.",
        "instruction": """Rewrite the following content by adding CITATIONS to authoritative sources.
Reference experts, studies, organizations, and authoritative publications.

Guidelines:
- Add at least 3-5 citations to credible sources
- Use format: "According to [Source/Expert], ..."
- Include recent years (2020-2024)
- Make sources realistic and topic-appropriate

Original content:
{content}

Rewritten content with citations:"""
    },

    GEOMethod.AUTHORITATIVE: {
        "system": "You are an authoritative expert who writes with confidence and expertise.",
        "instruction": """Rewrite the following content using an AUTHORITATIVE TONE.
Write as an established expert in the field with confidence and authority.

Guidelines:
- Use confident, definitive language
- Demonstrate deep expertise
- Make strong, evidence-based claims
- Avoid hedging language ("might", "maybe", "possibly")
- Use phrases like "Research shows", "Evidence indicates", "It is well-established that"

Original content:
{content}

Rewritten content with authoritative tone:"""
    },

    GEOMethod.QUOTATION: {
        "system": "You are a journalist who incorporates expert quotes into content.",
        "instruction": """Rewrite the following content by adding DIRECT QUOTES from experts, researchers, or authorities.
Include at least 3-4 relevant quotes that support key points.

Guidelines:
- Add realistic expert quotes in quotation marks
- Attribute quotes to named experts or organizations
- Make quotes relevant and topic-specific
- Use format: Dr. Jane Smith from MIT states, "quote here"

Original content:
{content}

Rewritten content with expert quotes:"""
    },

    GEOMethod.FLUENCY: {
        "system": "You are an expert editor focused on clarity, flow, and readability.",
        "instruction": """Rewrite the following content to maximize FLUENCY and READABILITY.
Improve sentence structure, flow, transitions, and overall clarity.

Guidelines:
- Improve sentence transitions
- Vary sentence length and structure
- Enhance paragraph flow
- Use clear, natural language
- Improve overall readability
- Maintain the same factual content

Original content:
{content}

Rewritten content with improved fluency:"""
    },

    GEOMethod.TECHNICAL: {
        "system": "You are a technical expert who uses precise, domain-specific terminology.",
        "instruction": """Rewrite the following content using TECHNICAL TERMINOLOGY and industry-specific language.
Replace general terms with precise technical terms appropriate for the domain.

Guidelines:
- Use domain-specific jargon and terminology
- Include technical concepts and frameworks
- Demonstrate technical depth
- Maintain accuracy while increasing specificity

Original content:
{content}

Rewritten content with technical terms:"""
    },

    GEOMethod.EASY: {
        "system": "You are an educator who explains complex topics simply and clearly.",
        "instruction": """Rewrite the following content to be EASY-TO-UNDERSTAND for a general audience.
Simplify complex concepts while maintaining accuracy.

Guidelines:
- Use simple, everyday language
- Explain technical terms when used
- Break down complex ideas
- Use analogies and examples
- Make content accessible to non-experts

Original content:
{content}

Rewritten content (easy to understand):"""
    },

    GEOMethod.KEYWORD: {
        "system": "You are an SEO expert who optimizes content for search keywords.",
        "instruction": """Rewrite the following content to include target KEYWORDS multiple times.
Optimize for search engines by including key terms and phrases frequently.

Guidelines:
- Identify 3-5 main keywords from the content
- Repeat keywords naturally throughout (keyword density: 2-3%)
- Include keywords in different forms (singular, plural, variations)
- Maintain readability while increasing keyword presence

Original content:
{content}

Rewritten content with keyword optimization:"""
    },

    GEOMethod.UNIQUE: {
        "system": "You are a creative writer who adds unique perspectives and original insights.",
        "instruction": """Rewrite the following content by adding UNIQUE WORDS and ORIGINAL PERSPECTIVES.
Include uncommon vocabulary, distinctive phrasing, and unique angles.

Guidelines:
- Use sophisticated, uncommon vocabulary
- Add unique insights and perspectives
- Include distinctive phrasing
- Avoid clichés and common expressions
- Make the content stand out with original language

Original content:
{content}

Rewritten content with unique words:"""
    }
}


def get_transformation_prompt(method: GEOMethod, content: str) -> Dict[str, str]:
    """
    Get the prompt for transforming content with a specific GEO method

    Args:
        method: The GEO method to apply
        content: The baseline content to transform

    Returns:
        Dict with 'system' and 'prompt' keys
    """
    if method not in GEO_PROMPTS:
        raise ValueError(f"Unknown GEO method: {method}")

    prompt_template = GEO_PROMPTS[method]

    return {
        "system": prompt_template["system"],
        "prompt": prompt_template["instruction"].format(content=content)
    }


def get_all_methods() -> list:
    """Get list of all available GEO methods"""
    return list(GEOMethod)


def get_method_description(method: GEOMethod) -> str:
    """Get human-readable description of a GEO method"""
    descriptions = {
        GEOMethod.BASELINE: "Baseline: Unoptimized content (control group)",
        GEOMethod.STATISTICS: "Statistics Addition: Add numerical data and statistics",
        GEOMethod.CITATION: "Citation Addition: Reference authoritative sources",
        GEOMethod.AUTHORITATIVE: "Authoritative Tone: Write with expert confidence",
        GEOMethod.QUOTATION: "Quotation Addition: Include expert quotes",
        GEOMethod.FLUENCY: "Fluency Optimization: Improve readability and flow",
        GEOMethod.TECHNICAL: "Technical Terms: Use domain-specific terminology",
        GEOMethod.EASY: "Easy-to-Understand: Simplify for general audience",
        GEOMethod.KEYWORD: "Keyword Optimization: Traditional SEO approach (for comparison)",
        GEOMethod.UNIQUE: "Unique Words: Use distinctive vocabulary and perspectives"
    }
    return descriptions.get(method, method.value)


# Example usage
if __name__ == "__main__":
    sample_content = """
    Italian restaurants offer a variety of pasta dishes. Pizza is also popular.
    Many restaurants use fresh ingredients. Good service is important for customer satisfaction.
    """

    print("GEO Method Prompts\n" + "=" * 50)

    for method in GEOMethod:
        print(f"\n{method.value.upper()}")
        print("-" * 50)
        prompt_data = get_transformation_prompt(method, sample_content)
        print(f"System: {prompt_data['system']}\n")
        print(f"Prompt:\n{prompt_data['prompt'][:200]}...")
        print()
