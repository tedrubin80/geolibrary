"""Query model for test queries"""

from sqlalchemy import Column, Integer, String, Text, JSON, Enum
from .base import Base, TimestampMixin
import enum


class QueryDomain(str, enum.Enum):
    """Domain categories for queries"""
    TECHNOLOGY = "technology"
    HEALTH = "health"
    FINANCE = "finance"
    ECOMMERCE = "ecommerce"
    FOOD = "food"
    TRAVEL = "travel"
    EDUCATION = "education"


class QueryIntent(str, enum.Enum):
    """Query intent types"""
    INFORMATIONAL = "informational"
    TRANSACTIONAL = "transactional"
    NAVIGATIONAL = "navigational"


class QueryDifficulty(str, enum.Enum):
    """Query difficulty levels"""
    SIMPLE = "simple"
    MODERATE = "moderate"
    COMPLEX = "complex"


class Query(Base, TimestampMixin):
    """Test query model"""

    __tablename__ = "queries"

    id = Column(Integer, primary_key=True, index=True)
    text = Column(Text, nullable=False)
    domain = Column(Enum(QueryDomain), nullable=False)
    intent = Column(Enum(QueryIntent), nullable=False, default=QueryIntent.INFORMATIONAL)
    difficulty = Column(Enum(QueryDifficulty), nullable=False, default=QueryDifficulty.MODERATE)

    # Metadata
    tags = Column(JSON, default=list)  # Additional tags
    description = Column(Text)  # Description of what makes this query interesting
    source = Column(String(255))  # Where this query came from (GEO-bench, manual, etc.)

    # Competing sources for this query
    competing_sources = Column(JSON, default=list)  # List of URLs or source descriptions

    def __repr__(self):
        return f"<Query(id={self.id}, text='{self.text[:50]}...', domain={self.domain})>"
