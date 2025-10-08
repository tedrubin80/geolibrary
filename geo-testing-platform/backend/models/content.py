"""Content models for baseline and optimized content"""

from sqlalchemy import Column, Integer, String, Text, JSON, ForeignKey, Enum
from sqlalchemy.orm import relationship
from .base import Base, TimestampMixin
import enum


class GEOMethod(str, enum.Enum):
    """GEO optimization methods"""
    BASELINE = "baseline"  # Unoptimized content
    STATISTICS = "statistics"  # Statistics Addition
    CITATION = "citation"  # Citation Addition
    AUTHORITATIVE = "authoritative"  # Authoritative Tone
    QUOTATION = "quotation"  # Quotation Addition
    FLUENCY = "fluency"  # Fluency Optimization
    TECHNICAL = "technical"  # Technical Terms
    EASY = "easy"  # Easy-to-Understand
    KEYWORD = "keyword"  # Keyword Optimization (for comparison)
    UNIQUE = "unique"  # Unique Words


class Content(Base, TimestampMixin):
    """Base content for queries"""

    __tablename__ = "content"

    id = Column(Integer, primary_key=True, index=True)
    query_id = Column(Integer, ForeignKey("queries.id"), nullable=False, index=True)

    # Baseline content
    title = Column(String(500), nullable=False)
    baseline_text = Column(Text, nullable=False)
    word_count = Column(Integer, nullable=False)

    # Metadata
    author = Column(String(255))
    source_url = Column(String(1000))
    metadata = Column(JSON, default=dict)

    # Relationships
    versions = relationship("ContentVersion", back_populates="content", cascade="all, delete-orphan")

    def __repr__(self):
        return f"<Content(id={self.id}, title='{self.title[:30]}...', query_id={self.query_id})>"


class ContentVersion(Base, TimestampMixin):
    """Optimized versions of content with different GEO methods"""

    __tablename__ = "content_versions"

    id = Column(Integer, primary_key=True, index=True)
    content_id = Column(Integer, ForeignKey("content.id"), nullable=False, index=True)

    # GEO method applied
    geo_method = Column(Enum(GEOMethod), nullable=False)

    # Optimized content
    optimized_text = Column(Text, nullable=False)
    optimization_details = Column(JSON, default=dict)  # What was changed

    # Generation metadata
    model_used = Column(String(255))  # e.g., "mistral:7b-instruct"
    generation_time = Column(Integer)  # milliseconds
    prompt_used = Column(Text)  # The prompt used to generate this

    # Relationships
    content = relationship("Content", back_populates="versions")

    def __repr__(self):
        return f"<ContentVersion(id={self.id}, method={self.geo_method}, content_id={self.content_id})>"
